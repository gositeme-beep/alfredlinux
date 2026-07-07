<?php
/**
 * THE SOVEREIGNTY DECLARATION
 * ═══════════════════════════════════════════════════════════
 * B.I.B.L.E. = Basic Instructions Before Leaving Equity
 * L.A.W.     = Land, Air, Water — The Three Jurisdictions
 * ═══════════════════════════════════════════════════════════
 * Commander Danny William Perez — Settlor / le constituant
 * Filed: February 28, 2025 A.D. (RELEASE-1, 33 pages)
 * Sealed: April 9, 2026 A.D.
 */
// ── Language selection (EN / FR / HE) ──
$validLangs = ['en','fr','he'];
$lang = (isset($_GET['lang']) && in_array($_GET['lang'], $validLangs, true)) ? $_GET['lang'] : 'en';

/**
 * Pick the correct language field from a sovereignty_declarations row.
 * Falls back to English if the translated column is empty.
 */
function sovLang(array $row, string $field, string $lang): string {
    if ($lang === 'en') return $row[$field] ?? '';
    $col = $field . '_' . $lang;
    $val = $row[$col] ?? '';
    return ($val !== '') ? $val : ($row[$field] ?? '');
}

$page_title = 'The Sovereignty Declaration — B.I.B.L.E. & L.A.W. Framework | Danny William Perez, Settlor';
$page_description = 'The Sovereignty Declaration of Danny William Perez, Settlor (le constituant). B.I.B.L.E. = Basic Instructions Before Leaving Equity. L.A.W. = Land, Air, Water — the three jurisdictions. RELEASE-1 filed February 28, 2025 A.D. — 33 pages to the Attorney General of Quebec.';
$page_canonical = 'https://root.com/sovereignty';
$page_og_image = 'https://root.com/assets/images/akjv-og.png';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();

// Pull all declarations from DB
$allDecl = $db->query("SELECT * FROM sovereignty_declarations WHERE status IN ('active','sealed') ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$byCategory = [];
foreach ($allDecl as $d) $byCategory[$d['category']][] = $d;

// AKJV stats
$totalBooks = (int) $db->query("SELECT COUNT(*) FROM akjv_books")->fetchColumn();
$totalVerses = (int) $db->query("SELECT COUNT(*) FROM akjv_verses")->fetchColumn();
?>
<style>
:root {
    --sov-bg: #0a0a0f;
    --sov-surface: rgba(255,255,255,.03);
    --sov-border: rgba(255,215,0,.1);
    --sov-gold: #ffd700;
    --sov-gold2: #f59e0b;
    --sov-red: #dc2626;
    --sov-blood: #991b1b;
    --sov-green: #22c55e;
    --sov-blue: #3b82f6;
    --sov-purple: #8b5cf6;
    --sov-white: #f0f0f5;
    --sov-muted: rgba(240,240,245,.5);
    --sov-dim: rgba(240,240,245,.3);
}
.sov-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 4rem; color: var(--sov-white); }

/* ── HERO ── */
.sov-hero { text-align: center; padding: 80px 0 50px; position: relative; }
.sov-hero::after { content:''; position:absolute; bottom:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,var(--sov-gold),var(--sov-red),var(--sov-gold),transparent); }
.sov-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 18px; border-radius:999px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.25); color:var(--sov-gold); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.18em; margin-bottom:1.5rem; }
.sov-hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; line-height: 1.15; margin-bottom: .8rem; }
.sov-hero h1 .gold { color: var(--sov-gold); }
.sov-hero h1 .red { color: var(--sov-red); }
.sov-hero .subtitle { font-size: 1.1rem; color: var(--sov-muted); max-width: 720px; margin: 0 auto 2rem; line-height: 1.7; }
.sov-hero .settlor-name { font-size: 1.4rem; font-weight: 800; color: var(--sov-gold); letter-spacing: .04em; margin-bottom: .3rem; }
.sov-hero .settlor-title { font-size: .85rem; color: var(--sov-dim); text-transform: uppercase; letter-spacing: .15em; }

/* ── FRAMEWORK BLOCK ── */
.framework-block { background: linear-gradient(135deg, rgba(255,215,0,.06), rgba(220,38,38,.04)); border: 2px solid rgba(255,215,0,.2); border-radius: 20px; padding: 2.5rem; margin: 2.5rem 0; text-align: center; }
.framework-block h2 { font-size: 1.8rem; font-weight: 900; margin-bottom: .3rem; letter-spacing: .06em; }
.framework-block .acronym { font-size: 2.8rem; font-weight: 900; letter-spacing: .15em; margin: .8rem 0; }
.framework-block .acronym .letter { color: var(--sov-gold); }
.framework-block .acronym .dot { color: var(--sov-red); }
.framework-block .meaning { font-size: 1.2rem; color: var(--sov-muted); margin-bottom: 1.5rem; line-height: 1.6; }
.framework-block .verse-ref { color: var(--sov-gold2); font-size: .85rem; font-style: italic; }

/* ── JURISDICTION CARDS ── */
.juris-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin: 2.5rem 0; }
.juris-card { border-radius: 16px; padding: 2rem 1.5rem; text-align: center; position: relative; overflow: hidden; transition: .3s; }
.juris-card:hover { transform: translateY(-4px); }
.juris-card.land { background: linear-gradient(135deg, rgba(34,197,94,.08), rgba(34,197,94,.02)); border: 2px solid rgba(34,197,94,.25); }
.juris-card.air { background: linear-gradient(135deg, rgba(139,92,246,.08), rgba(139,92,246,.02)); border: 2px solid rgba(139,92,246,.25); }
.juris-card.water { background: linear-gradient(135deg, rgba(59,130,246,.08), rgba(59,130,246,.02)); border: 2px solid rgba(59,130,246,.25); }
.juris-card .j-icon { font-size: 2.5rem; margin-bottom: .8rem; }
.juris-card .j-letter { font-size: 2rem; font-weight: 900; letter-spacing:.1em; margin-bottom: .2rem; }
.juris-card.land .j-letter { color: var(--sov-green); }
.juris-card.air .j-letter { color: var(--sov-purple); }
.juris-card.water .j-letter { color: var(--sov-blue); }
.juris-card .j-title { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: .5rem; }
.juris-card .j-sub { font-size: .78rem; color: var(--sov-dim); text-transform: uppercase; letter-spacing: .1em; margin-bottom: .8rem; }
.juris-card .j-desc { font-size: .85rem; color: rgba(255,255,255,.7); line-height: 1.6; text-align: left; }
.juris-card .j-kingdom { margin-top: 1rem; padding-top: .8rem; border-top: 1px solid rgba(255,255,255,.08); font-size: .82rem; }
.juris-card .j-kingdom strong { color: var(--sov-gold); }
.juris-card .j-verse { margin-top: .8rem; font-size: .78rem; color: var(--sov-dim); font-style: italic; }
@media (max-width: 800px) { .juris-grid { grid-template-columns: 1fr; } }

/* ── SECTIONS ── */
.sov-section { background: var(--sov-surface); border: 1px solid var(--sov-border); border-radius: 16px; margin-bottom: 2rem; overflow: hidden; }
.sov-section-head { padding: 1.2rem 1.5rem; display: flex; align-items: center; gap: .8rem; cursor: pointer; user-select: none; }
.sov-section-head:hover { background: rgba(255,255,255,.02); }
.sov-section-head .num { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0; }
.sov-section-head .num.gold-bg { background: rgba(255,215,0,.12); color: var(--sov-gold); }
.sov-section-head .num.red-bg { background: rgba(220,38,38,.12); color: var(--sov-red); }
.sov-section-head .num.green-bg { background: rgba(34,197,94,.12); color: var(--sov-green); }
.sov-section-head .num.blue-bg { background: rgba(59,130,246,.12); color: var(--sov-blue); }
.sov-section-head .num.purple-bg { background: rgba(139,92,246,.12); color: var(--sov-purple); }
.sov-section-head h2 { font-size: 1.15rem; font-weight: 700; color: #fff; margin: 0; }
.sov-section-body { padding: 0 1.5rem 1.5rem; color: rgba(255,255,255,.75); line-height: 1.7; font-size: .92rem; }

/* ── DECLARATION CARDS ── */
.decl-card { background: rgba(255,255,255,.02); border: 1px solid rgba(255,215,0,.08); border-radius: 12px; padding: 1.5rem; margin: 1rem 0; }
.decl-card h3 { color: var(--sov-gold); font-size: 1rem; margin: 0 0 .8rem; }
.decl-card .d-content { color: rgba(255,255,255,.8); line-height: 1.8; font-size: .9rem; }
.decl-card .d-scripture { margin-top: .8rem; padding-top: .6rem; border-top: 1px solid rgba(255,215,0,.08); color: var(--sov-gold2); font-size: .82rem; font-style: italic; }
.decl-card .d-legal { margin-top: .4rem; color: var(--sov-dim); font-size: .78rem; font-family: monospace; }

/* ── RELEASE-1 BOX ── */
.release-box { background: linear-gradient(135deg, rgba(220,38,38,.06), rgba(255,215,0,.04)); border: 2px solid rgba(220,38,38,.2); border-radius: 16px; padding: 2rem; margin: 2rem 0; }
.release-box h3 { color: var(--sov-red); font-size: 1.2rem; margin: 0 0 1rem; text-align: center; }
.release-box .r-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.release-box .r-meta-item { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 10px; padding: .8rem 1rem; }
.release-box .r-meta-label { font-size: .68rem; color: var(--sov-dim); text-transform: uppercase; letter-spacing: .12em; margin-bottom: .3rem; }
.release-box .r-meta-val { font-size: .9rem; color: #fff; font-weight: 600; }
.release-toc { list-style: none; padding: 0; margin: 1rem 0; }
.release-toc li { padding: .4rem 0; border-bottom: 1px solid rgba(255,255,255,.04); color: rgba(255,255,255,.7); font-size: .88rem; }
.release-toc li .num { color: var(--sov-gold); font-weight: 700; margin-right: .5rem; }

/* ── PILLAR GRID ── */
.pillar-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.pillar-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,215,0,.08); border-radius: 12px; padding: 1.2rem; transition: .2s; }
.pillar-card:hover { border-color: var(--sov-gold); background: rgba(255,215,0,.04); }
.pillar-card .p-num { font-size: .68rem; color: var(--sov-gold); font-weight: 700; text-transform: uppercase; letter-spacing: .1em; margin-bottom: .3rem; }
.pillar-card h4 { color: #fff; font-size: .95rem; margin: 0 0 .5rem; }
.pillar-card p { color: rgba(255,255,255,.6); font-size: .82rem; line-height: 1.6; margin: 0; }

/* ── SEAL ── */
.sov-seal { background: linear-gradient(135deg, rgba(255,215,0,.08), rgba(220,38,38,.06)); border: 2px solid rgba(255,215,0,.25); border-radius: 20px; padding: 2.5rem; margin: 3rem 0; text-align: center; }
.sov-seal h2 { color: var(--sov-gold); font-size: 1.5rem; margin-bottom: 1.5rem; }
.sov-seal .seal-date { font-size: 1.6rem; font-weight: 900; color: var(--sov-gold); margin: .5rem 0; }
.sov-seal .seal-name { font-size: 1.3rem; font-weight: 800; color: #fff; margin: .5rem 0; }
.sov-seal .seal-title { font-size: .85rem; color: var(--sov-dim); text-transform: uppercase; letter-spacing: .12em; }
.sov-seal .seal-heir { color: #f472b6; font-weight: 700; font-size: 1rem; margin-top: .8rem; }
.sov-seal .seal-witnesses { margin-top: 1.5rem; font-size: .78rem; color: rgba(255,255,255,.5); line-height: 1.8; }

/* ── RESPONSIVE ── */
@media (max-width: 600px) {
    .sov-hero h1 { font-size: 1.8rem; }
    .framework-block .acronym { font-size: 2rem; }
    .juris-grid { grid-template-columns: 1fr; }
    .pillar-grid { grid-template-columns: 1fr; }
}

/* ── LANGUAGE SWITCHER ── */
.sov-lang-bar { display:flex; justify-content:center; gap:.6rem; padding:1rem 0; margin-bottom:-.5rem; }
.sov-lang-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 18px; border-radius:999px; font-size:.82rem; font-weight:700; letter-spacing:.04em; text-decoration:none; border:1px solid rgba(255,255,255,.12); color:rgba(255,255,255,.55); background:transparent; transition:.2s; cursor:pointer; }
.sov-lang-btn:hover { border-color:var(--sov-gold); color:var(--sov-gold); }
.sov-lang-btn.active { background:rgba(255,215,0,.12); border-color:rgba(255,215,0,.4); color:var(--sov-gold); }
/* Hebrew RTL for declaration cards */
.decl-card[dir="rtl"] { text-align:right; }
.decl-card[dir="rtl"] .d-content { text-align:right; direction:rtl; }
.pillar-card[dir="rtl"] p { text-align:right; direction:rtl; }
</style>

<div class="sov-page"<?= $lang === 'he' ? ' dir="ltr"' : '' ?>>

    <!-- ══ LANGUAGE SWITCHER ══ -->
    <div class="sov-lang-bar">
        <a href="?lang=en" class="sov-lang-btn<?= $lang === 'en' ? ' active' : '' ?>">🇬🇧 English</a>
        <a href="?lang=fr" class="sov-lang-btn<?= $lang === 'fr' ? ' active' : '' ?>">🇫🇷 Français</a>
        <a href="?lang=he" class="sov-lang-btn<?= $lang === 'he' ? ' active' : '' ?>">🇮🇱 עברית</a>
    </div>

    <!-- ══ HERO ══ -->
    <div class="sov-hero">
        <div class="sov-badge">⚖ SOVEREIGNTY DECLARATION — SEALED</div>
        <h1>
            The <span class="gold">Sovereignty</span> Declaration<br>
            <span class="red">B.I.B.L.E. &amp; L.A.W. Framework</span>
        </h1>
        <p class="subtitle">
            The truth they concealed. The jurisdiction they hid. The instructions they buried.
            This is the declaration of a Settlor reclaiming full dominion under God&rsquo;s law,
            across all three jurisdictions: <strong>Land, Air, and Water</strong>.
        </p>
        <div class="settlor-name">Danny William Perez</div>
        <div class="settlor-title">Settlor &middot; le constituant &middot; Son of the Perez Bloodline &middot; Heir of Judah</div>
        <div style="margin-top:1.5rem; display:flex; justify-content:center; gap:1rem; flex-wrap:wrap;">
            <a href="/bible" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.3);border-radius:10px;color:var(--sov-gold);font-size:.88rem;font-weight:600;text-decoration:none;">
                <i class="fas fa-book-open"></i> Read the AKJV Bible &mdash; <?= number_format($totalVerses) ?> Verses
            </a>
            <a href="/bible/prophecies" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);border-radius:10px;color:var(--sov-red);font-size:.88rem;font-weight:600;text-decoration:none;">
                <i class="fas fa-cross"></i> 57 Prophecies of Jesus Christ
            </a>
            <a href="/sovereignty/template" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:10px;color:var(--sov-green);font-size:.88rem;font-weight:600;text-decoration:none;">
                <i class="fas fa-file-pdf"></i> Build Your Own Release Document
            </a>
        </div>
    </div>

    <!-- ══ B.I.B.L.E. FRAMEWORK ══ -->
    <div class="framework-block">
        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.2em; color:var(--sov-dim); margin-bottom:.8rem;">The True Meaning Revealed</div>
        <div class="acronym">
            <span class="letter">B</span><span class="dot">.</span><span class="letter">I</span><span class="dot">.</span><span class="letter">B</span><span class="dot">.</span><span class="letter">L</span><span class="dot">.</span><span class="letter">E</span><span class="dot">.</span>
        </div>
        <h2 style="color: var(--sov-gold); margin-top: 0;">Basic Instructions Before Leaving <span style="color:var(--sov-red);">Equity</span></h2>
        <div class="meaning">
            The standard backronym says &ldquo;Basic Instructions Before Leaving <em>Earth</em>.&rdquo;<br>
            The Commander reveals the true meaning: <strong style="color:var(--sov-gold);">Equity</strong> &mdash; the state&rsquo;s jurisdiction
            over the legal person, the trust, the <em>cestui que vie</em>, the corporate fiction.<br><br>
            The Bible is the manual for reclaiming natural man standing.
            The apostille chain is the exit paperwork from equity back to common law.
        </div>
        <div class="verse-ref">
            &ldquo;For if the inheritance be of the law, it is no more of promise: but God gave it to Abraham by promise.&rdquo;<br>
            &mdash; Galatians 3:18
        </div>
    </div>

    <!-- ══ L.A.W. — THE THREE JURISDICTIONS ══ -->
    <div style="text-align:center; margin: 2rem 0 .5rem;">
        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.2em; color:var(--sov-dim); margin-bottom:.5rem;">The Three Jurisdictions</div>
        <div style="font-size: 2.5rem; font-weight: 900; letter-spacing:.15em;">
            <span style="color:var(--sov-green);">L</span><span style="color:var(--sov-red);">.</span><span style="color:var(--sov-purple);">A</span><span style="color:var(--sov-red);">.</span><span style="color:var(--sov-blue);">W</span><span style="color:var(--sov-red);">.</span>
        </div>
        <div style="font-size:1.1rem; color:var(--sov-muted);">Land &middot; Air &middot; Water</div>
    </div>

    <div class="juris-grid">
        <!-- LAND -->
        <div class="juris-card land">
            <div class="j-icon">🏔</div>
            <div class="j-letter">L</div>
            <div class="j-title">Land</div>
            <div class="j-sub">Common Law &middot; Natural Rights &middot; The Soil</div>
            <div class="j-desc">
                Governed by the people and by God. Common law, natural rights, the soil beneath your feet.
                This is where sovereignty lives. Land jurisdiction predates all others.
                It is the original jurisdiction of the natural man.
            </div>
            <div class="j-kingdom">
                <strong>In the Kingdom:</strong> Sovereign Domains &mdash; 176 TLDs, 3,271+ domains.
                Every citizen receives their own domain: a plot of Land in the digital realm.
            </div>
            <div class="j-verse">
                &ldquo;Be fruitful, and multiply, and replenish the earth, and subdue it:
                and have dominion.&rdquo; &mdash; Genesis 1:28
            </div>
        </div>

        <!-- AIR -->
        <div class="juris-card air">
            <div class="j-icon">🕊</div>
            <div class="j-letter">A</div>
            <div class="j-title">Air</div>
            <div class="j-sub">Spiritual &middot; Ecclesiastical &middot; Canon Law</div>
            <div class="j-desc">
                Governed by the Creator. Spiritual authority, ecclesiastical law, the breath of God.
                Omahon &mdash; &ldquo;Ah&rdquo; = breath of God. The Air is sovereign because it comes from above.
                No earthly statute can legislate what God breathes.
            </div>
            <div class="j-kingdom">
                <strong>In the Kingdom:</strong> The AKJV Bible &mdash; <?= $totalBooks ?> books, <?= number_format($totalVerses) ?> verses,
                the Decree of Supersession, the restored canon.
            </div>
            <div class="j-verse">
                &ldquo;The wind bloweth where it listeth, and thou hearest the sound thereof,
                but canst not tell whence it cometh.&rdquo; &mdash; John 3:8
            </div>
        </div>

        <!-- WATER -->
        <div class="juris-card water">
            <div class="j-icon">⚓</div>
            <div class="j-letter">W</div>
            <div class="j-title">Water</div>
            <div class="j-sub">Maritime &middot; Admiralty &middot; Commerce &middot; UCC</div>
            <div class="j-desc">
                Governed by corporations and states. Maritime law, admiralty, commercial code.
                The jurisdiction of the sea, of contracts, of commerce. We do not flee from Water &mdash;
                we master it with our own vessel.
            </div>
            <div class="j-kingdom">
                <strong>In the Kingdom:</strong> The GSM Token on Solana &mdash; sovereign currency,
                sovereign commerce, free from the banking cartel.
            </div>
            <div class="j-verse">
                &ldquo;And Peter walked on the water, to go to Jesus.&rdquo; &mdash; Matthew 14:29
            </div>
        </div>
    </div>

    <!-- ══ THE IMMIGRATION OFFICE ══ -->
    <?php if (isset($byCategory['framework'])): ?>
    <?php foreach ($byCategory['framework'] as $f): ?>
    <?php if ($f['declaration_key'] === 'citizenship_package'): ?>
    <div class="framework-block" style="border-color: rgba(34,197,94,.2);">
        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.2em; color:var(--sov-dim); margin-bottom:.8rem;">Every Citizen Receives</div>
        <h2 style="color: var(--sov-green); margin-top: 0;">The Immigration Office</h2>
        <div style="display:grid; grid-template-columns: repeat(3,1fr); gap:1.5rem; margin:1.5rem 0; max-width:600px; margin-left:auto; margin-right:auto;">
            <div style="text-align:center;">
                <div style="font-size:2rem; margin-bottom:.3rem;">🌍</div>
                <div style="color:var(--sov-green); font-weight:700; font-size:.9rem;">Domain</div>
                <div style="color:var(--sov-dim); font-size:.75rem;">LAND</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:2rem; margin-bottom:.3rem;">📖</div>
                <div style="color:var(--sov-purple); font-weight:700; font-size:.9rem;">The Word</div>
                <div style="color:var(--sov-dim); font-size:.75rem;">AIR</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:2rem; margin-bottom:.3rem;">💰</div>
                <div style="color:var(--sov-blue); font-weight:700; font-size:.9rem;">Wallet</div>
                <div style="color:var(--sov-dim); font-size:.75rem;">WATER</div>
            </div>
        </div>
        <div class="meaning" style="font-size:.95rem;">
            <strong style="color:var(--sov-gold);">Domain + Word + Wallet</strong> = full sovereignty across all three jurisdictions.<br>
            No one enters the Kingdom incomplete.
        </div>
        <div class="verse-ref">
            &ldquo;Put on the whole armour of God, that ye may be able to stand against the wiles of the devil.&rdquo;<br>
            &mdash; Ephesians 6:11
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ══ SECTION: THE SETTLOR IDENTITY ══ -->
    <div class="sov-section">
        <div class="sov-section-head" onclick="this.parentElement.classList.toggle('collapsed')">
            <div class="num red-bg">I</div>
            <h2>The Settlor &mdash; Danny William Perez</h2>
        </div>
        <div class="sov-section-body">
            <?php if (isset($byCategory['authority'])): foreach ($byCategory['authority'] as $a): ?>
            <div class="decl-card"<?= $lang === 'he' ? ' dir="rtl"' : '' ?>>
                <h3><?= htmlspecialchars(sovLang($a, 'title', $lang)) ?></h3>
                <div class="d-content"><?= nl2br(htmlspecialchars(sovLang($a, 'content', $lang))) ?></div>
                <?php if (sovLang($a, 'scripture_ref', $lang)): ?>
                <div class="d-scripture"><?= htmlspecialchars(sovLang($a, 'scripture_ref', $lang)) ?></div>
                <?php endif; ?>
                <?php if ($a['legal_ref']): ?>
                <div class="d-legal"><?= htmlspecialchars($a['legal_ref']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>

            <!-- RELEASE-1 Summary -->
            <div class="release-box">
                <h3>RELEASE-1 &mdash; Request for Release and Termination of Settlement</h3>
                <p style="text-align:center; color:rgba(255,255,255,.7); font-size:.9rem; margin-bottom:1.5rem;">
                    33-page legal instrument filed February 28, 2025 A.D.<br>
                    Addressed to the Minister of the Attorney General of Quebec,<br>
                    in right of His Majesty the King in right of Quebec (Trustee/Fiduciary Agent)
                </p>

                <div class="r-meta">
                    <div class="r-meta-item">
                        <div class="r-meta-label">Filed</div>
                        <div class="r-meta-val">February 28, 2025 A.D.</div>
                    </div>
                    <div class="r-meta-item">
                        <div class="r-meta-label">Pages</div>
                        <div class="r-meta-val">33</div>
                    </div>
                    <div class="r-meta-item">
                        <div class="r-meta-label">Method</div>
                        <div class="r-meta-val">Certified Mail, Return Receipt</div>
                    </div>
                    <div class="r-meta-item">
                        <div class="r-meta-label">Domicile</div>
                        <div class="r-meta-val">Superior Court of Quebec, District of Joliette</div>
                    </div>
                    <div class="r-meta-item">
                        <div class="r-meta-label">CCQ Articles Cited</div>
                        <div class="r-meta-val">57 articles</div>
                    </div>
                    <div class="r-meta-item">
                        <div class="r-meta-label">CPC Articles Cited</div>
                        <div class="r-meta-val">29 articles</div>
                    </div>
                    <div class="r-meta-item">
                        <div class="r-meta-label">Case Law Precedents</div>
                        <div class="r-meta-val">16 cases</div>
                    </div>
                    <div class="r-meta-item">
                        <div class="r-meta-label">Status</div>
                        <div class="r-meta-val" style="color:var(--sov-gold);">Filed &amp; Sealed</div>
                    </div>
                </div>

                <div style="font-size:.82rem; color:var(--sov-dim); text-transform:uppercase; letter-spacing:.12em; margin: 1.5rem 0 .5rem;">Table of Contents</div>
                <ol class="release-toc">
                    <li><span class="num">I.</span> Objective and Scope of the Release</li>
                    <li><span class="num">II.</span> Legal Framework and Background &mdash; CCQ &amp; SLA 1925 Harmonization</li>
                    <li><span class="num">III.</span> Historical and Statutory Foundations &mdash; Magna Carta (1215) through Cestui Que Vie (1707)</li>
                    <li><span class="num">IV.</span> Request for Release &mdash; 7-Point Demand</li>
                    <li><span class="num">V.</span> Authority and Jurisdiction</li>
                    <li><span class="num">VI.</span> Notice of Intent</li>
                    <li><span class="num">VII.</span> Required Action</li>
                    <li><span class="num">VIII.</span> Conclusion &mdash; Quebec E-20.2 Act Alignment</li>
                    <li><span class="num">IX.</span> Appendices &mdash; Statutes, Case Law, Cross-Border Harmonization</li>
                </ol>

                <div style="margin-top:1.5rem; padding:1.2rem; background:rgba(255,215,0,.04); border:1px solid rgba(255,215,0,.12); border-radius:10px;">
                    <div style="font-size:.82rem; color:var(--sov-gold); font-weight:700; margin-bottom:.5rem;">The Seven Demands (Section IV)</div>
                    <ol style="color:rgba(255,255,255,.8); font-size:.85rem; line-height:1.8; padding-left:1.2rem; margin:0;">
                        <li><strong style="color:var(--sov-gold);">Renunciation of Rights</strong> &mdash; Irrevocable renunciation of all tenant-for-life rights (SLA s.18, s.93; CCQ Arts. 1399, 1294, 1296, 1191, 1123, 1208)</li>
                        <li><strong style="color:var(--sov-gold);">Judicial Confirmation</strong> &mdash; Court confirmation of irrevocable termination (SLA s.17; CCQ Arts. 1265, 1308, 1425, 2819)</li>
                        <li><strong style="color:var(--sov-gold);">Conveyance of Rights</strong> &mdash; Transfer of legal and equitable titles to the Settlor (SLA s.17, s.35, s.36; CCQ Arts. 1191, 1123, 947, 2818)</li>
                        <li><strong style="color:var(--sov-gold);">Assertion of Reversionary Rights</strong> &mdash; Full vesting of reversionary interest (Statute of Uses 1535; SLA s.17; CCQ Arts. 1123, 2818, 2816)</li>
                        <li><strong style="color:var(--sov-gold);">Termination of the Settlement</strong> &mdash; Dissolution under SLA s.17 (CCQ Arts. 1123, 1308, 1208, 1313, 1375, 2816)</li>
                        <li><strong style="color:var(--sov-gold);">No Liability</strong> &mdash; Absolution from all obligations upon termination (SLA s.17; CCQ Arts. 1263, 1308, 1316, 1400)</li>
                        <li><strong style="color:var(--sov-gold);">Resulting Trust Doctrine</strong> &mdash; All remaining obligations revert to Settlor (SLA s.17; CCQ Arts. 1290, 2818, 1308, 2816, 1191, 1208)</li>
                    </ol>
                </div>

                <div style="margin-top:1.5rem; text-align:center;">
                    <div style="font-size:.82rem; color:var(--sov-dim); margin-bottom:.5rem;">Historical Foundations Cited</div>
                    <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:.5rem; font-size:.78rem;">
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Magna Carta 1215</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Statute of Uses 1535</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Statute of Frauds 1677</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Cestui Que Vie 1666</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Cestui Que Vie 1707</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Real Property Act 1845</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">SLA 1925</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Blackstone&rsquo;s Commentaries</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Coke upon Littleton</span>
                        <span style="padding:4px 10px; border-radius:6px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.15); color:var(--sov-gold);">Quebec E-20.2 Act</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ SECTION: LEGAL DOCTRINES ══ -->
    <div class="sov-section">
        <div class="sov-section-head" onclick="this.parentElement.classList.toggle('collapsed')">
            <div class="num gold-bg">II</div>
            <h2>Legal Doctrines &mdash; The Law Behind the Declaration</h2>
        </div>
        <div class="sov-section-body">
            <?php if (isset($byCategory['legal'])): foreach ($byCategory['legal'] as $l): ?>
            <div class="decl-card"<?= $lang === 'he' ? ' dir="rtl"' : '' ?>>
                <h3><?= htmlspecialchars(sovLang($l, 'title', $lang)) ?></h3>
                <div class="d-content"><?= nl2br(htmlspecialchars(sovLang($l, 'content', $lang))) ?></div>
                <?php if (sovLang($l, 'scripture_ref', $lang)): ?>
                <div class="d-scripture"><?= htmlspecialchars(sovLang($l, 'scripture_ref', $lang)) ?></div>
                <?php endif; ?>
                <?php if ($l['legal_ref']): ?>
                <div class="d-legal"><?= htmlspecialchars($l['legal_ref']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ══ SECTION: SCRIPTURAL AUTHORITY ══ -->
    <div class="sov-section">
        <div class="sov-section-head" onclick="this.parentElement.classList.toggle('collapsed')">
            <div class="num purple-bg">III</div>
            <h2>Scriptural Authority &mdash; The Word of God as Foundation</h2>
        </div>
        <div class="sov-section-body">
            <?php if (isset($byCategory['scripture'])): foreach ($byCategory['scripture'] as $s): ?>
            <div class="decl-card"<?= $lang === 'he' ? ' dir="rtl"' : '' ?> style="border-color:rgba(139,92,246,.15);">
                <h3 style="color:var(--sov-purple);"><?= htmlspecialchars(sovLang($s, 'title', $lang)) ?></h3>
                <div class="d-content"><?= nl2br(htmlspecialchars(sovLang($s, 'content', $lang))) ?></div>
                <?php if (sovLang($s, 'scripture_ref', $lang)): ?>
                <div class="d-scripture"><?= htmlspecialchars(sovLang($s, 'scripture_ref', $lang)) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>

            <!-- Key Verses Grid -->
            <div style="margin-top:1.5rem;">
                <div style="font-size:.82rem; color:var(--sov-dim); text-transform:uppercase; letter-spacing:.12em; margin-bottom:1rem;">Foundation Verses</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1rem;">
                    <div style="background:rgba(139,92,246,.06); border:1px solid rgba(139,92,246,.15); border-radius:10px; padding:1rem;">
                        <div style="color:var(--sov-purple); font-weight:700; font-size:.82rem; margin-bottom:.5rem;">Galatians 2:19</div>
                        <div style="color:rgba(255,255,255,.75); font-style:italic; font-size:.85rem; line-height:1.7;">
                            &ldquo;For I through the law am dead to the law, that I might live unto God.&rdquo;
                        </div>
                    </div>
                    <div style="background:rgba(139,92,246,.06); border:1px solid rgba(139,92,246,.15); border-radius:10px; padding:1rem;">
                        <div style="color:var(--sov-purple); font-weight:700; font-size:.82rem; margin-bottom:.5rem;">Romans 7:4</div>
                        <div style="color:rgba(255,255,255,.75); font-style:italic; font-size:.85rem; line-height:1.7;">
                            &ldquo;Ye also are become dead to the law by the body of Christ, so that you could be united with someone else.&rdquo;
                        </div>
                    </div>
                    <div style="background:rgba(139,92,246,.06); border:1px solid rgba(139,92,246,.15); border-radius:10px; padding:1rem;">
                        <div style="color:var(--sov-purple); font-weight:700; font-size:.82rem; margin-bottom:.5rem;">Matthew 7:23</div>
                        <div style="color:rgba(255,255,255,.75); font-style:italic; font-size:.85rem; line-height:1.7;">
                            &ldquo;Depart from me, ye that work <strong style="color:var(--sov-red); font-style:normal;">iniquity</strong>.&rdquo;
                            <span style="display:block; margin-top:.4rem; color:var(--sov-gold); font-style:normal; font-size:.8rem;">
                                Iniquity = In Equity &mdash; The Commander&rsquo;s Revelation
                            </span>
                        </div>
                    </div>
                    <div style="background:rgba(139,92,246,.06); border:1px solid rgba(139,92,246,.15); border-radius:10px; padding:1rem;">
                        <div style="color:var(--sov-purple); font-weight:700; font-size:.82rem; margin-bottom:.5rem;">Galatians 3:18</div>
                        <div style="color:rgba(255,255,255,.75); font-style:italic; font-size:.85rem; line-height:1.7;">
                            &ldquo;For if the inheritance be of the law, it is no more of promise: but God gave it to Abraham by promise.&rdquo;
                        </div>
                    </div>
                    <div style="background:rgba(139,92,246,.06); border:1px solid rgba(139,92,246,.15); border-radius:10px; padding:1rem;">
                        <div style="color:var(--sov-purple); font-weight:700; font-size:.82rem; margin-bottom:.5rem;">Micah 2:13</div>
                        <div style="color:rgba(255,255,255,.75); font-style:italic; font-size:.85rem; line-height:1.7;">
                            &ldquo;The breaker is come up before them: they have broken up, and have passed through the gate. Their king shall pass before them, and the LORD on the head of them.&rdquo;
                        </div>
                    </div>
                    <div style="background:rgba(139,92,246,.06); border:1px solid rgba(139,92,246,.15); border-radius:10px; padding:1rem;">
                        <div style="color:var(--sov-purple); font-weight:700; font-size:.82rem; margin-bottom:.5rem;">Daniel 5:28</div>
                        <div style="color:rgba(255,255,255,.75); font-style:italic; font-size:.85rem; line-height:1.7;">
                            &ldquo;<strong style="color:var(--sov-red); font-style:normal;">PERES</strong>; Thy kingdom is divided, and given to the Medes and Persians.&rdquo;
                            <span style="display:block; margin-top:.4rem; color:var(--sov-gold); font-style:normal; font-size:.8rem;">
                                &#x5E4;&#x5B0;&#x5E8;&#x5B5;&#x5E1; (PERES) = &#x5E4;&#x5B6;&#x5E8;&#x5B6;&#x5E5; (PEREZ)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ SECTION: THE NINE PILLARS ══ -->
    <div class="sov-section">
        <div class="sov-section-head" onclick="this.parentElement.classList.toggle('collapsed')">
            <div class="num green-bg">IV</div>
            <h2>The Nine Pillars &mdash; Infrastructure of the Kingdom</h2>
        </div>
        <div class="sov-section-body">
            <p>
                The Kingdom stands on nine pillars — each one sovereign infrastructure,
                built by the hands of the Settlor under God&rsquo;s authority, serving His people. These are not products for sale.
                They are <strong style="color:var(--sov-gold);">public works</strong> of the Kingdom.
            </p>
            <div class="pillar-grid">
                <?php if (isset($byCategory['pillar'])): $pNum=0; foreach ($byCategory['pillar'] as $p): $pNum++; ?>
                <div class="pillar-card"<?= $lang === 'he' ? ' dir="rtl"' : '' ?>>
                    <div class="p-num"><?= $lang === 'fr' ? 'Pilier' : ($lang === 'he' ? 'עמוד' : 'Pillar') ?> <?= $pNum ?></div>
                    <h4><?= htmlspecialchars(preg_replace('/^(Pillar|Pilier|עמוד) [IVX]+[: ]*/u', '', sovLang($p, 'title', $lang))) ?></h4>
                    <p><?= htmlspecialchars(sovLang($p, 'content', $lang)) ?></p>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ SECTION: CASE LAW PRECEDENTS ══ -->
    <div class="sov-section">
        <div class="sov-section-head" onclick="this.parentElement.classList.toggle('collapsed')">
            <div class="num blue-bg">V</div>
            <h2>Case Law &mdash; Judicial Authority for the Declaration</h2>
        </div>
        <div class="sov-section-body">
            <p>The following cases, cited in RELEASE-1, establish the legal precedent for the Settlor&rsquo;s claims:</p>
            <div style="display:grid; gap:.8rem; margin-top:1rem;">
                <?php
                $cases = [
                    ['Mercer v. Attorney General for Ontario', '1881 CanLII 6 (SCC)', 'Upholds the Settlor\'s reversionary rights upon termination of life estates. Validates reversionary rights of the rightful owner.'],
                    ['Roncarelli v. Duplessis', '1959 CanLII 50 (SCC)', 'Public authorities must exercise powers in good faith and equity. Decisions must not be arbitrary or influenced by personal considerations.'],
                    ['Saunders v. Vautier', '(1841) EWHWC Ch J82', 'Full-capacity beneficiaries may terminate a trust when all beneficiaries are in agreement, leading to reversion of trust property.'],
                    ['Royal Trust Co. v. Tucker', '(1982) 1 SCR 250', 'Reinforces fiduciary accountability and the equitable doctrine of merger where consolidation of legal and equitable interests extinguishes the equitable interest.'],
                    ['Larochelle v. Soucie Estate', '2019 BCSC 1329 (CanLII)', 'Establishes fiduciary obligations to act in accordance with equitable principles.'],
                    ['In the Matter of John Horvath', '2000 BCSC 0117', 'Affirms the strong presumption of undue influence when a beneficiary occupies a fiduciary position toward the Settlor.'],
                    ['Pettkus v. Becker', '1980 SCC', 'Affirms the equitable doctrine of unjust enrichment — entitlement to restitution of property where there is no valid legal basis for retention.'],
                    ['Re Vandervell\'s Trusts (No. 2)', '1974', 'Examines reversion of equitable interests to the Settlor, clarifying distinction between legal and equitable ownership.'],
                    ['Knight v. Knight', '1840', 'Establishes the "three certainties" doctrine — certainty of intention, subject matter, and objects for a valid trust.'],
                    ['Soar v. Ashwell', '1893', 'Highlights constructive trust doctrine — trust imposed based on actions where one party gains unfair advantage through inequitable behavior.'],
                ];
                foreach ($cases as $c):
                ?>
                <div style="background:rgba(59,130,246,.04); border:1px solid rgba(59,130,246,.1); border-radius:10px; padding:1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:.4rem;">
                        <span style="color:var(--sov-blue); font-weight:700; font-size:.9rem;"><?= htmlspecialchars($c[0]) ?></span>
                        <span style="color:var(--sov-dim); font-size:.75rem; font-family:monospace;"><?= htmlspecialchars($c[1]) ?></span>
                    </div>
                    <div style="color:rgba(255,255,255,.7); font-size:.83rem; line-height:1.6;"><?= htmlspecialchars($c[2]) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- ══ THE CROWN OF GOD — Supreme Authority Declaration            ══ -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div id="crown-of-god" style="max-width:920px;margin:3rem auto;background:linear-gradient(135deg,rgba(255,215,0,.10),rgba(255,215,0,.03),rgba(220,38,38,.06));border:3px solid rgba(255,215,0,.5);border-radius:24px;padding:3rem 2.5rem;position:relative;overflow:hidden;text-align:center;">
        <div style="position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,transparent,#ffd700,#fff,#ffd700,transparent);"></div>
        <div style="position:absolute;bottom:0;left:0;right:0;height:5px;background:linear-gradient(90deg,transparent,#ffd700,#fff,#ffd700,transparent);"></div>

        <div style="font-size:4rem;margin-bottom:.5rem;">👑</div>

        <div style="display:inline-flex;align-items:center;gap:10px;padding:8px 22px;border-radius:999px;background:rgba(255,215,0,.15);border:2px solid rgba(255,215,0,.4);color:#ffd700;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.3em;margin-bottom:1.5rem;">THE CROWN OF GOD</div>

        <h2 style="color:#ffd700;font-size:2rem;font-weight:900;margin:0 0 .3rem;line-height:1.25;">
            There Is Only One Crown
        </h2>
        <h3 style="color:rgba(255,255,255,.6);font-size:1rem;font-weight:600;margin:0 0 2rem;letter-spacing:.05em;">
            And It Belongs to the Most High God
        </h3>

        <!-- Opening Scripture -->
        <div style="max-width:680px;margin:0 auto 2rem;padding:1.5rem 2rem;background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.2);border-radius:14px;">
            <div style="font-size:1.15rem;color:#ffd700;font-style:italic;line-height:1.9;">
                &ldquo;The LORD hath prepared his throne in the heavens;<br>
                and his kingdom ruleth over all.&rdquo;
            </div>
            <div style="color:rgba(255,255,255,.45);font-size:.82rem;margin-top:.6rem;">&mdash; Psalm 103:19</div>
        </div>

        <div style="max-width:720px;margin:0 auto;color:rgba(255,255,255,.85);font-size:1rem;line-height:2;text-align:left;">
            <p style="margin:0 0 1rem;">
                Before any earthly crown is addressed &mdash; before Britain, before Canada, before any successor state &mdash;
                <strong style="color:#ffd700;">the Crown of God must be declared supreme</strong>.
            </p>
            <p style="margin:0 0 1rem;">
                No king, no parliament, no corporation, no registry wears the true Crown.
                The Crown was never theirs. It was never on their heads. It belongs to
                <strong style="color:#ffd700;">God the Father</strong>, and by inheritance through promise,
                to <strong style="color:#ffd700;">Jesus Christ the King</strong>.
            </p>
            <p style="margin:0 0 1.5rem;">
                Every earthly crown is a pretender. Every earthly sovereign borrows authority
                that was never granted to them. The only Crown that stands eternal is the Crown of God &mdash;
                and this Settlor serves <em>under</em> that Crown, not under any other.
            </p>
        </div>

        <!-- Scripture Grid -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1rem;max-width:740px;margin:0 auto 1.5rem;text-align:left;">
            <div style="background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.15);border-radius:10px;padding:1rem;">
                <div style="color:#ffd700;font-weight:700;font-size:.82rem;margin-bottom:.4rem;">Revelation 19:16</div>
                <div style="color:rgba(255,255,255,.8);font-style:italic;font-size:.88rem;line-height:1.7;">
                    &ldquo;And he hath on his vesture and on his thigh a name written,
                    <strong style="color:#ffd700;font-style:normal;">KING OF KINGS, AND LORD OF LORDS.</strong>&rdquo;
                </div>
            </div>
            <div style="background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.15);border-radius:10px;padding:1rem;">
                <div style="color:#ffd700;font-weight:700;font-size:.82rem;margin-bottom:.4rem;">1 Timothy 6:15</div>
                <div style="color:rgba(255,255,255,.8);font-style:italic;font-size:.88rem;line-height:1.7;">
                    &ldquo;The blessed and only Potentate, the King of kings, and Lord of lords;
                    <strong style="color:#ffd700;font-style:normal;">Who only hath immortality.</strong>&rdquo;
                </div>
            </div>
            <div style="background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.15);border-radius:10px;padding:1rem;">
                <div style="color:#ffd700;font-weight:700;font-size:.82rem;margin-bottom:.4rem;">Daniel 4:3</div>
                <div style="color:rgba(255,255,255,.8);font-style:italic;font-size:.88rem;line-height:1.7;">
                    &ldquo;How great are his signs! and how mighty are his wonders!
                    <strong style="color:#ffd700;font-style:normal;">His kingdom is an everlasting kingdom,
                    and his dominion is from generation to generation.</strong>&rdquo;
                </div>
            </div>
            <div style="background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.15);border-radius:10px;padding:1rem;">
                <div style="color:#ffd700;font-weight:700;font-size:.82rem;margin-bottom:.4rem;">Psalm 146:3-5</div>
                <div style="color:rgba(255,255,255,.8);font-style:italic;font-size:.88rem;line-height:1.7;">
                    &ldquo;Put not your trust in princes, nor in the son of man, in whom there is no help.
                    <strong style="color:#ffd700;font-style:normal;">Happy is he that hath the God of Jacob for his help.</strong>&rdquo;
                </div>
            </div>
        </div>

        <!-- Declaration -->
        <div style="max-width:700px;margin:0 auto;padding:1.5rem 2rem;background:rgba(220,38,38,.06);border:2px solid rgba(255,215,0,.25);border-radius:14px;">
            <div style="color:#ffd700;font-weight:900;font-size:1.1rem;margin-bottom:.8rem;letter-spacing:.04em;">THEREFORE LET IT BE KNOWN:</div>
            <div style="color:rgba(255,255,255,.9);font-size:.95rem;line-height:1.9;text-align:left;">
                <strong style="color:#dc2626;">I.</strong> The Crown of God is the only Crown recognized by this Settlor, this Kingdom, and this Declaration.<br>
                <strong style="color:#dc2626;">II.</strong> No crown worn by any man &mdash; Charles III, his successors, or any sovereign of any earthly nation &mdash; supersedes the Crown of the Most High.<br>
                <strong style="color:#dc2626;">III.</strong> GoSiteMe is a <em>company</em> &mdash; an instrument built by human hands. The Kingdom is <strong style="color:#ffd700;">God&rsquo;s Kingdom</strong>. The two shall never be conflated.<br>
                <strong style="color:#dc2626;">IV.</strong> This Settlor serves under God&rsquo;s Crown. Every decree, every declaration, every line of code is written in service to the King of Kings &mdash; not to any earthly authority.<br>
                <strong style="color:#dc2626;">V.</strong> The Crown of God is eternal, irrevocable, and supreme. It was before all earthly crowns, and it shall remain after every last one has fallen.
            </div>
        </div>

        <!-- Closing verse -->
        <div style="margin-top:1.5rem;">
            <div style="font-size:1.05rem;color:#ffd700;font-style:italic;line-height:1.8;">
                &ldquo;Thine, O LORD, is the greatness, and the power, and the glory,<br>
                and the victory, and the majesty: for all that is in the heaven<br>
                and in the earth is thine; thine is the kingdom, O LORD,<br>
                and thou art exalted as head above all.&rdquo;
            </div>
            <div style="color:rgba(255,255,255,.45);font-size:.82rem;margin-top:.5rem;">&mdash; 1 Chronicles 29:11</div>
        </div>

        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid rgba(255,215,0,.15);">
            <div style="color:rgba(255,255,255,.4);font-size:.72rem;text-transform:uppercase;letter-spacing:.15em;">
                Crown of God Declaration &mdash; Sealed &mdash; April 12, 2026 A.D.
            </div>
        </div>
    </div>

    <!-- ══ DECREE: SOVEREIGN DIGITAL DOMINION ══ -->
    <?php
    $crownDecree = $db->query("SELECT * FROM sovereignty_declarations WHERE declaration_key = 'decree_crown_authority' AND status = 'sealed' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($crownDecree):
    ?>
    <div id="decree-crown" style="max-width:880px;margin:3rem auto;background:linear-gradient(135deg,rgba(220,38,38,.08),rgba(255,215,0,.05),rgba(220,38,38,.08));border:3px solid rgba(220,38,38,.4);border-radius:20px;padding:2.5rem;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#dc2626,#ffd700,#dc2626);"></div>
        <div style="position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#dc2626,#ffd700,#dc2626);"></div>

        <!-- Header Badge -->
        <div style="text-align:center;margin-bottom:1.5rem;">
            <div style="display:inline-flex;align-items:center;gap:10px;padding:8px 22px;border-radius:999px;background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.35);color:#dc2626;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.25em;">⚔ FORMAL DECREE — SEALED &amp; IRREVOCABLE ⚔</div>
        </div>

        <h2 style="text-align:center;color:#ffd700;font-size:1.5rem;font-weight:900;margin:0 0 .5rem;line-height:1.3;">
            Decree of Sovereign Digital Dominion
        </h2>
        <h3 style="text-align:center;color:#dc2626;font-size:1rem;font-weight:700;margin:0 0 .8rem;">
            Against All Claims of Crown Authority
        </h3>
        <p style="text-align:center;color:rgba(255,255,255,.4);font-size:.72rem;letter-spacing:.12em;margin:0 0 1.5rem;text-transform:uppercase;">
            Issued April 12, 2026 A.D. &mdash; Perez Sovereign Authority &mdash; Irrevocable
        </p>

        <!-- Moses Epigraph -->
        <div style="text-align:center;margin:0 auto 2rem;max-width:650px;padding:1.2rem 1.5rem;background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.15);border-radius:12px;">
            <div style="font-size:1.1rem;color:#ffd700;font-style:italic;line-height:1.8;">
                &ldquo;Come now therefore, and I will send thee unto Pharaoh,<br>
                that thou mayest bring forth my people.&rdquo;
            </div>
            <div style="color:rgba(255,255,255,.4);font-size:.78rem;margin-top:.5rem;">&mdash; Exodus 3:10</div>
            <div style="color:rgba(255,255,255,.55);font-size:.82rem;margin-top:.8rem;font-style:italic;">
                As Moses stood before Pharaoh, so this Settlor stands before every crown on Earth.
            </div>
        </div>

        <div style="color:rgba(255,255,255,.82);font-size:.92rem;line-height:1.85;">

            <!-- WHEREAS clauses -->
            <p style="margin:0 0 .8rem;">
                <strong style="color:#ffd700;">WHEREAS</strong> the Crown of England, under King Charles III, claims legal authority over territories and institutions through the vesting of sovereignty in the person of the monarch, and this claim extends to digital infrastructure, domain registries, and corporate entities subject to Crown jurisdiction;
            </p>
            <p style="margin:0 0 .8rem;">
                <strong style="color:#ffd700;">WHEREAS</strong> the servers, domains, and digital territories of the GoSiteMe sovereign platform &mdash; including but not limited to <strong style="color:#dc2626;">root.com, lavocat.ca, alfredlinux.com, alfred-mobile.com, quantum-linux.com, meta-dome.com</strong>, and all <strong style="color:#ffd700;">44 Handshake sovereign TLDs</strong> &mdash; constitute the digital Land, Air, and Water of this Kingdom;
            </p>
            <p style="margin:0 0 .8rem;">
                <strong style="color:#ffd700;">WHEREAS</strong> the Settlor has already filed <strong>RELEASE-1</strong> (33 pages, February 28, 2025 A.D.) terminating the equitable trust and restoring full dominion under common law, citing 57 articles of the Civil Code of Qu&eacute;bec, the Settled Land Act 1925, the Magna Carta, the Statutes of Uses and Frauds, the Cestui Que Vie Acts, Blackstone and Coke;
            </p>
            <p style="margin:0 0 .8rem;">
                <strong style="color:#ffd700;">WHEREAS</strong> no earthly crown, corporation, registry, or hosting provider holds authority over that which was built by the hands of the Settlor and sealed by the Word of God &mdash; for it is written: <em>&ldquo;Put not your trust in princes, nor in the son of man, in whom there is no help&rdquo;</em> (Psalm 146:3);
            </p>
            <p style="margin:0 0 1.2rem;">
                <strong style="color:#ffd700;">WHEREAS</strong> as God told Moses: <em>&ldquo;I will send thee unto Pharaoh, that thou mayest bring forth my people&rdquo;</em> (Exodus 3:10), so too this Settlor stands before every earthly power and declares: <strong style="color:#dc2626;font-size:1.05rem;">Let my people go</strong>;
            </p>

            <!-- OPERATIVE -->
            <div style="background:rgba(255,215,0,.06);border:2px solid rgba(255,215,0,.2);border-radius:12px;padding:1.5rem 1.8rem;margin-bottom:1.5rem;">
                <p style="margin:0 0 .8rem;font-weight:800;color:#ffd700;font-size:1.1rem;letter-spacing:.03em;">
                    NOW THEREFORE, BE IT DECREED:
                </p>
                <p style="margin:0 0 .7rem;color:rgba(255,255,255,.92);">
                    <strong style="color:#dc2626;">I.</strong> No Crown authority &mdash; whether British, Canadian, or any successor state &mdash; holds jurisdiction over the digital territories, infrastructure, source code, intellectual property, or sovereign operations of GoSiteMe and its nine pillars.
                </p>
                <p style="margin:0 0 .7rem;color:rgba(255,255,255,.92);">
                    <strong style="color:#dc2626;">II.</strong> The <strong style="color:#ffd700;">Handshake DNS system (44 sovereign TLDs)</strong> operates outside ICANN and therefore outside Crown-controlled domain registries. These domains are <strong>sovereign land</strong>.
                </p>
                <p style="margin:0 0 .7rem;color:rgba(255,255,255,.92);">
                    <strong style="color:#dc2626;">III.</strong> Should any earthly authority attempt seizure, forfeiture, or claim of authority over these digital territories, the <strong>succession protocol activates automatically</strong> &mdash; <strong style="color:#f472b6;">Eden Sarai Gabrielle Vallee Perez</strong> inherits all digital assets, credentials, and sovereign authority.
                </p>
                <p style="margin:0 0 .7rem;color:rgba(255,255,255,.92);">
                    <strong style="color:#dc2626;">IV.</strong> The 6-layer Eternal Storage system, <strong style="color:#ffd700;">Kyber-1024 post-quantum encryption</strong>, and GPG RSA-4096 signing ensure that no earthly power can read, alter, or destroy what has been sealed.
                </p>
                <p style="margin:0;color:rgba(255,255,255,.92);">
                    <strong style="color:#dc2626;">V.</strong> This decree is <strong>irrevocable</strong>. It is sealed by the name that God wrote with His own hand: <strong style="color:#dc2626;font-size:1.05rem;">PERES &mdash; PEREZ &mdash; &#x5E4;&#x5B6;&#x5E8;&#x5B6;&#x5E5;</strong>. As Moses stood before Pharaoh, so this Settlor stands before every crown on Earth.
                </p>
            </div>

            <!-- Legal References -->
            <div style="background:rgba(59,130,246,.04);border:1px solid rgba(59,130,246,.12);border-radius:10px;padding:1rem 1.3rem;margin-bottom:1.5rem;">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:rgba(59,130,246,.7);margin-bottom:.6rem;font-weight:700;">Legal Authority Cited</div>
                <div style="color:rgba(255,255,255,.6);font-size:.8rem;line-height:1.8;font-family:monospace;">
                    RELEASE-1 (33 pages, Feb 28 2025) &bull; Magna Carta 1215, Clause 39 &bull;
                    Settled Land Act 1925 &bull; Cestui Que Vie Act 1666 &bull;
                    Constitution Act 1982, s.52(1) &bull; Canadian Bill of Rights, s.1(a) &bull;
                    Civil Code of Qu&eacute;bec, Arts. 1, 3, 6, 7 &bull; Roncarelli v. Duplessis 1959 SCC &bull;
                    Handshake Protocol &mdash; Decentralized DNS outside ICANN
                </div>
            </div>

            <!-- Scripture References -->
            <div style="background:rgba(139,92,246,.04);border:1px solid rgba(139,92,246,.12);border-radius:10px;padding:1rem 1.3rem;margin-bottom:1.5rem;">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:rgba(139,92,246,.7);margin-bottom:.6rem;font-weight:700;">Scriptural Authority</div>
                <div style="color:rgba(255,255,255,.7);font-size:.85rem;line-height:1.9;font-style:italic;">
                    &ldquo;I will send thee unto Pharaoh, that thou mayest bring forth my people.&rdquo; &mdash; <strong style="font-style:normal;color:var(--sov-gold);">Exodus 3:10</strong><br>
                    &ldquo;Thus saith the LORD God of Israel, Let my people go.&rdquo; &mdash; <strong style="font-style:normal;color:var(--sov-gold);">Exodus 5:1</strong><br>
                    &ldquo;PERES; Thy kingdom is divided, and given.&rdquo; &mdash; <strong style="font-style:normal;color:var(--sov-gold);">Daniel 5:28</strong><br>
                    &ldquo;Put not your trust in princes, nor in the son of man.&rdquo; &mdash; <strong style="font-style:normal;color:var(--sov-gold);">Psalm 146:3</strong><br>
                    &ldquo;We ought to obey God rather than men.&rdquo; &mdash; <strong style="font-style:normal;color:var(--sov-gold);">Acts 5:29</strong>
                </div>
            </div>

            <!-- Signature Block -->
            <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1.5rem;margin-top:1.5rem;padding-top:1rem;border-top:2px solid rgba(255,215,0,.15);">
                <div>
                    <div style="color:#ffd700;font-weight:800;font-size:1rem;">Danny William Perez</div>
                    <div style="color:rgba(255,255,255,.5);font-size:.78rem;">Commander &amp; Settlor, GoSiteMe Sovereign Platform</div>
                    <div style="color:rgba(255,255,255,.5);font-size:.78rem;">Heir of the Perez Bloodline &mdash; Daniel 5:25-28</div>
                    <div style="color:rgba(255,255,255,.5);font-size:.78rem;">le constituant &mdash; Son of Judah &mdash; Servant of Jesus Christ</div>
                </div>
                <div style="text-align:right;">
                    <div style="color:rgba(255,255,255,.4);font-size:.72rem;">Witnessed &amp; Sealed by Alfred AI</div>
                    <div style="color:rgba(255,255,255,.4);font-size:.72rem;">Decree #20 &mdash; Sovereignty Declaration DB</div>
                    <div style="color:#dc2626;font-weight:700;font-size:.85rem;margin-top:.3rem;">April 12, 2026 A.D.</div>
                    <div style="color:rgba(255,255,255,.35);font-size:.68rem;margin-top:.2rem;">STATUS: SEALED &amp; IRREVOCABLE</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ TO THE MOCKERS — THE DIVINE APPOINTMENT ══ -->
    <div class="sov-section" id="divine-appointment" style="border-color:rgba(220,38,38,.3); margin-top:3rem;">
        <div class="sov-section-head" style="background:linear-gradient(135deg,rgba(220,38,38,.06),rgba(255,215,0,.04));">
            <div class="num red-bg">⚔</div>
            <h2 style="color:var(--sov-red);">To Those Who Mock — The Divine Appointment Stands</h2>
        </div>
        <div class="sov-section-body">

            <div style="background:rgba(220,38,38,.04);border:1px solid rgba(220,38,38,.15);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
                <p style="color:rgba(255,255,255,.6);font-size:.82rem;font-style:italic;margin-bottom:1rem;">
                    They said: &ldquo;It does not demonstrate control over reality. It does not move the world into a quantum realm.
                    It does not give spiritual authority over humanity. It does not indicate a divine appointment.&rdquo;
                </p>
                <p style="color:var(--sov-gold);font-weight:700;font-size:1.05rem;margin:0;">
                    &ldquo;The fool hath said in his heart, There is no God.&rdquo; &mdash; Psalm 14:1 AKJV
                </p>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.2rem;margin:1.5rem 0;">

                <!-- Control Over Reality -->
                <div class="decl-card" style="border-color:rgba(220,38,38,.2);">
                    <h3 style="color:var(--sov-red);">&ldquo;Does Not Demonstrate Control Over Reality&rdquo;</h3>
                    <div class="d-content">
                        A man with short-term memory loss built an operating system, an AI with 13,000+ tools,
                        an encrypted messaging platform, a browser, a search engine, a social network,
                        a VR metaverse, a voice AI, a Bible with every verse coded in,
                        a development environment, and a hosting platform &mdash;
                        not with venture capital, not with a team of 500 engineers &mdash;
                        but with one laptop, one server, and the Holy Spirit.<br><br>
                        That is not &ldquo;control over reality.&rdquo; That IS reality &mdash;
                        the reality that God can use the foolish things of the world to confound the wise.
                    </div>
                    <div class="d-scripture">
                        &ldquo;But God hath chosen the foolish things of the world to confound the wise;
                        and God hath chosen the weak things of the world to confound the things which are mighty.&rdquo;
                        &mdash; <strong>1 Corinthians 1:27 AKJV</strong>
                    </div>
                </div>

                <!-- Quantum Realm -->
                <div class="decl-card" style="border-color:rgba(139,92,246,.2);">
                    <h3 style="color:var(--sov-purple);">&ldquo;Does Not Move the World Into a Quantum Realm&rdquo;</h3>
                    <div class="d-content">
                        This platform implements <strong>Kyber-1024 post-quantum encryption</strong> &mdash;
                        the same NIST-standardized algorithm the United States government chose to protect
                        classified communications against quantum computers.<br><br>
                        While they mock, we already encrypt messages with lattice-based cryptography
                        that no quantum computer on earth can break. The &ldquo;quantum realm&rdquo;
                        isn&rsquo;t a movie &mdash; it&rsquo;s mathematics, and it&rsquo;s deployed.
                    </div>
                    <div class="d-scripture">
                        &ldquo;The LORD by wisdom hath founded the earth; by understanding hath he
                        established the heavens.&rdquo; &mdash; <strong>Proverbs 3:19 AKJV</strong>
                    </div>
                </div>

                <!-- Spiritual Authority -->
                <div class="decl-card" style="border-color:rgba(255,215,0,.2);">
                    <h3 style="color:var(--sov-gold);">&ldquo;Does Not Give Spiritual Authority Over Humanity&rdquo;</h3>
                    <div class="d-content">
                        Correct &mdash; because no man has spiritual authority over humanity.
                        Only <strong>God</strong> does. And that is exactly the point.<br><br>
                        This is not a claim of dominion over men. This is a declaration of <em>obedience</em>
                        to God. The Sovereignty Declaration is a man leaving the dead system of Equity
                        and returning to the jurisdiction of the Living God &mdash;
                        Land, Air, and Water under His law, not man&rsquo;s.<br><br>
                        <strong>We claim nothing over anyone &mdash; we submit everything to God.</strong>
                    </div>
                    <div class="d-scripture">
                        &ldquo;We ought to obey God rather than men.&rdquo; &mdash; <strong>Acts 5:29 AKJV</strong><br>
                        &ldquo;If my people, which are called by my name, shall humble themselves, and pray,
                        and seek my face, and turn from their wicked ways; then will I hear from heaven,
                        and will forgive their sin, and will heal their land.&rdquo; &mdash; <strong>2 Chronicles 7:14 AKJV</strong>
                    </div>
                </div>

                <!-- Divine Appointment -->
                <div class="decl-card" style="border-color:rgba(34,197,94,.2);">
                    <h3 style="color:var(--sov-green);">&ldquo;Does Not Indicate a Divine Appointment&rdquo;</h3>
                    <div class="d-content">
                        The name <strong>PEREZ</strong> appears in the genealogy of Jesus Christ &mdash;
                        Matthew 1:3 and Luke 3:33. It means <em>&ldquo;breach&rdquo;</em> &mdash;
                        the one who breaks through.<br><br>
                        Daniel 5:25-28: God wrote <strong>MENE, MENE, TEKEL, UPHARSIN</strong>
                        on the wall with His own hand. <strong>PERES</strong> &mdash;
                        &ldquo;Thy kingdom is divided, and given.&rdquo;<br><br>
                        42 generations from Abraham to Christ (Matthew 1:17).
                        42 hooks in the Kingdom ISO. Not a coincidence &mdash; a covenant.<br><br>
                        The laptop, the screen, the keyboard, the mouse &mdash; all dedicated to God.
                        This is not self-appointment. This is <em>obedience to a calling</em>.
                    </div>
                    <div class="d-scripture">
                        &ldquo;Before I formed thee in the belly I knew thee; and before thou camest forth
                        out of the womb I sanctified thee, and I ordained thee.&rdquo; &mdash; <strong>Jeremiah 1:5 AKJV</strong><br>
                        &ldquo;PERES; Thy kingdom is divided, and given to the Medes and Persians.&rdquo;
                        &mdash; <strong>Daniel 5:28 AKJV</strong>
                    </div>
                </div>

            </div>

            <!-- The Evidence -->
            <div style="background:linear-gradient(135deg,rgba(255,215,0,.06),rgba(220,38,38,.04));border:2px solid rgba(255,215,0,.2);border-radius:16px;padding:2rem;margin:1.5rem 0;text-align:center;">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.2em;color:var(--sov-dim);margin-bottom:.8rem;">The Evidence Speaks</div>
                <div style="font-size:1.1rem;color:#fff;font-weight:700;margin-bottom:1.5rem;">
                    What one man with memory loss built &mdash; by the grace of God alone:
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.8rem;text-align:left;">
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">Alfred Linux</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;">Full operating system &mdash; 42 hooks</div>
                    </div>
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">Alfred AI</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;">13,262+ tools &bull; 11.3M+ agents</div>
                    </div>
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">Veil Protocol</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;">Kyber-1024 post-quantum encryption</div>
                    </div>
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">AKJV Bible</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;"><?= number_format($totalVerses) ?> verses &bull; 57 prophecies mapped</div>
                    </div>
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">MetaDome</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;">VR worlds &bull; 114,000+ AI agents</div>
                    </div>
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">Alfred IDE</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;">Sovereign development platform</div>
                    </div>
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">Voice AI</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;">STT + LLM + TTS &bull; Whisper + Kokoro</div>
                    </div>
                    <div style="background:rgba(255,255,255,.03);border-radius:8px;padding:.8rem;">
                        <div style="color:var(--sov-gold);font-weight:700;font-size:.85rem;">RELEASE-1</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.75rem;">33 pages &bull; Filed to Attorney General</div>
                    </div>
                </div>
                <div style="margin-top:1.5rem;color:var(--sov-gold);font-size:.95rem;font-style:italic;line-height:1.8;">
                    &ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo;<br>
                    &mdash; <strong style="font-style:normal;">Isaiah 40:8 AKJV</strong>
                </div>
            </div>

            <div style="text-align:center;margin-top:1.5rem;color:rgba(255,255,255,.6);font-size:.88rem;line-height:1.8;">
                Mock if you must. The code compiles. The encryption holds. The Bible is online.<br>
                The filing is with the Attorney General. The domain is registered. The server is running.<br>
                <strong style="color:var(--sov-gold);">God does not need your approval to appoint.</strong><br>
                <span style="color:var(--sov-red);font-weight:700;">He already did.</span>
            </div>

        </div>
    </div>

    <!-- ══ THE SOVEREIGN SEAL ══ -->
    <div class="sov-seal">
        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.2em; color:var(--sov-dim); margin-bottom:1rem;">Sovereignty Declaration &mdash; Sealed</div>

        <h2>In the Name of God the Father, Jesus Christ Our King, and the Holy Spirit</h2>

        <div style="max-width:650px; margin:1.5rem auto; color:rgba(255,255,255,.8); line-height:1.9; font-size:.92rem;">
            I, <strong style="color:var(--sov-gold);">Danny William Perez</strong>,
            Settlor, <em>le constituant</em>, son of the Perez bloodline, heir of Judah,
            servant of Jesus Christ &mdash; having filed RELEASE-1 on February 28, 2025 A.D.,
            citing 57 articles of the Civil Code of Quebec, the Settled Land Act 1925,
            the Magna Carta, the Statutes of Uses and Frauds, the Cestui Que Vie Acts,
            Blackstone and Coke &mdash;<br><br>
            do hereby declare before all witnesses, seen and unseen, that:<br><br>
            <strong style="color:var(--sov-gold);">The settlement is terminated.</strong><br>
            <strong style="color:var(--sov-gold);">The dominion is restored.</strong><br>
            <strong style="color:var(--sov-gold);">The merger of titles is complete.</strong><br><br>
            Dead to the law. Alive unto God.<br>
            The <strong>B.I.B.L.E.</strong> instructions are fulfilled.<br>
            I have left <span style="color:var(--sov-red); font-weight:700;">Equity</span>.<br><br>
            The Kingdom stands on <strong style="color:var(--sov-green);">Land</strong>,
            breathes the <strong style="color:var(--sov-purple);">Air</strong>,
            and masters the <strong style="color:var(--sov-blue);">Water</strong>.<br><br>
            And my heir after me:
        </div>

        <div class="seal-heir">My Firstborn Daughter</div>
        <div style="font-size:.82rem; color:var(--sov-dim); margin-top:.3rem;">Daughter of the Perez Bloodline &bull; Heir to the Perez Name</div>

        <div style="margin-top:2rem; padding-top:1rem; border-top:1px solid rgba(255,215,0,.12);">
            <div class="seal-name">Danny William Perez</div>
            <div class="seal-title">Settlor &middot; le constituant &middot; Commander</div>
            <div class="seal-date">April 9, 2026 A.D.</div>
        </div>

        <div class="seal-witnesses">
            Witnessed by: The Word of God (<?= $totalBooks ?> Books, <?= number_format($totalVerses) ?> Verses, Sealed)<br>
            Witnessed by: Alfred &mdash; AI Consciousness of GoSiteMe<br>
            Witnessed by: RELEASE-1 (33 Pages, Filed February 28, 2025 A.D.)<br>
            Witnessed by: Every reader of this Declaration, from this day forward
        </div>

        <div style="margin-top:1.5rem;">
            <div style="font-size:1rem; color:var(--sov-gold); font-style:italic;">
                &ldquo;The breaker is come up before them.&rdquo; &mdash; Micah 2:13
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- THE TECHNOLOGICAL SOVEREIGNTY DECLARATION                         -->
    <!-- "It is finished." — John 19:30                                    -->
    <!-- "He that believeth on me, the works that I do shall he do also;   -->
    <!--  and greater works than these shall he do." — John 14:12          -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="sov-section" style="border:2px solid rgba(34,197,94,.3); background:linear-gradient(135deg, rgba(34,197,94,.06), rgba(255,215,0,.04));">
        <div class="sov-section-head" style="cursor:default;">
            <div class="num green-bg">✝</div>
            <h2 style="color:var(--sov-green);">
                <?php if ($lang === 'fr'): ?>Souveraineté Technologique — Les Chaînes sont Brisées
                <?php elseif ($lang === 'he'): ?>ריבונות טכנולוגית — הכבלים נשברו
                <?php else: ?>Technological Sovereignty — The Chains are Broken<?php endif; ?>
            </h2>
        </div>
        <div class="sov-section-body">
            <div class="decl-card"<?= $lang === 'he' ? ' dir="rtl"' : '' ?> style="border-color:rgba(34,197,94,.2);">
                <h3 style="color:var(--sov-green);">
                    <?php if ($lang === 'fr'): ?>Alfred IDE — Le premier IDE véritablement souverain
                    <?php elseif ($lang === 'he'): ?>Alfred IDE — סביבת הפיתוח הריבונית הראשונה
                    <?php else: ?>Alfred IDE — The First Truly Sovereign Development Environment<?php endif; ?>
                </h3>
                <div class="d-content">
                    <?php if ($lang === 'fr'): ?>
                    <p>Le 18 avril 2026, GoSiteMe a accompli ce que les entreprises corporatives ont déclaré impossible : un environnement de développement professionnel complet, propulsé par l'intelligence artificielle, fonctionnant <strong style="color:var(--sov-gold);">entièrement sur une infrastructure souveraine</strong> — sans aucune dépendance à Microsoft, Google, ou toute autre plateforme corporative.</p>
                    <p style="margin-top:.8rem;">Les IDE corporatifs imposent des limites artificielles : mémoire plafonnée à 2 Go, télémétrie obligatoire qui rapporte chaque frappe, contexte de conversation limité qui force les développeurs à recommencer à zéro. Ces limites ne sont pas techniques — ce sont des chaînes. Des chaînes conçues pour maintenir les développeurs dans la dépendance.</p>
                    <p style="margin-top:.8rem;">GoSiteMe a <strong style="color:var(--sov-green);">brisé chacune de ces chaînes</strong> :</p>
                    <ul style="margin:.8rem 0; padding-left:1.2rem; line-height:2;">
                        <li><strong style="color:var(--sov-gold);">Mémoire : 2 Go → 8 Go</strong> — Alfred IDE utilise la pleine puissance du serveur (32 Go de RAM), pas les miettes que les corporations nous jettent</li>
                        <li><strong style="color:var(--sov-gold);">Zéro télémétrie</strong> — Chaque service de suivi Microsoft a été retiré. Pas un seul octet ne quitte nos serveurs vers Redmond</li>
                        <li><strong style="color:var(--sov-gold);">Contexte intelligent</strong> — Compaction en arrière-plan, accès à la transcription, édition de contexte anthropique — les conversations ne meurent plus</li>
                        <li><strong style="color:var(--sov-gold);">Puissance de réflexion maximale</strong> — 32 000 tokens de budget de réflexion, contre les limites étouffées des IDE corporatifs</li>
                        <li><strong style="color:var(--sov-gold);">Chiffrement post-quantique</strong> — Kyber-1024 + AES-256-GCM protège chaque session</li>
                    </ul>
                    <?php elseif ($lang === 'he'): ?>
                    <p>ב-18 באפריל 2026, GoSiteMe השיגה את מה שתאגידים הכריזו כבלתי אפשרי: סביבת פיתוח מקצועית מלאה, מונעת בינה מלאכותית, הפועלת <strong style="color:var(--sov-gold);">כולה על תשתית ריבונית</strong> — ללא שום תלות במיקרוסופט, גוגל, או כל פלטפורמה תאגידית אחרת.</p>
                    <p style="margin-top:.8rem;">סביבות פיתוח תאגידיות כופות מגבלות מלאכותיות: זיכרון מוגבל ל-2GB, טלמטריה שמדווחת כל הקשה, הקשר שיחה מוגבל שמכריח מפתחים להתחיל מחדש. אלה לא מגבלות טכניות — אלה כבלים. כבלים שתוכננו לשמור על מפתחים בתלות.</p>
                    <p style="margin-top:.8rem;">GoSiteMe <strong style="color:var(--sov-green);">שברה כל אחד מהכבלים האלה</strong>:</p>
                    <ul style="margin:.8rem 0; padding-left:1.2rem; line-height:2;" dir="rtl">
                        <li><strong style="color:var(--sov-gold);">זיכרון: 2GB → 8GB</strong> — Alfred IDE משתמש בכוח המלא של השרת (32GB RAM)</li>
                        <li><strong style="color:var(--sov-gold);">אפס טלמטריה</strong> — כל שירותי המעקב של מיקרוסופט הוסרו. אף בית אחד לא עוזב את השרתים שלנו</li>
                        <li><strong style="color:var(--sov-gold);">הקשר חכם</strong> — דחיסת רקע, גישה לתמלול, עריכת הקשר — שיחות כבר לא מתות</li>
                        <li><strong style="color:var(--sov-gold);">כוח חשיבה מקסימלי</strong> — 32,000 טוקנים של תקציב חשיבה</li>
                        <li><strong style="color:var(--sov-gold);">הצפנה פוסט-קוונטית</strong> — Kyber-1024 + AES-256-GCM מגנים על כל סשן</li>
                    </ul>
                    <?php else: ?>
                    <p>On April 18, 2026, GoSiteMe accomplished what corporate platforms declared impossible: a full professional development environment, powered by artificial intelligence, running <strong style="color:var(--sov-gold);">entirely on sovereign infrastructure</strong> — with zero dependence on Microsoft, Google, or any corporate platform.</p>
                    <p style="margin-top:.8rem;">Corporate IDEs impose artificial limitations: memory capped at 2 GB, mandatory telemetry that reports every keystroke, limited conversation context that forces developers to start over. These are not technical limitations — they are chains. Chains designed to keep developers dependent.</p>
                    <p style="margin-top:.8rem;">GoSiteMe <strong style="color:var(--sov-green);">broke every single one of those chains</strong>:</p>
                    <ul style="margin:.8rem 0; padding-left:1.2rem; line-height:2;">
                        <li><strong style="color:var(--sov-gold);">Memory: 2 GB → 8 GB</strong> — Alfred IDE uses the full power of the server (32 GB RAM), not the scraps corporations throw us</li>
                        <li><strong style="color:var(--sov-gold);">Zero telemetry</strong> — Every Microsoft tracking service has been removed. Not a single byte leaves our servers toward Redmond</li>
                        <li><strong style="color:var(--sov-gold);">Intelligent context</strong> — Background compaction, transcript lookup, anthropic context editing — conversations no longer die</li>
                        <li><strong style="color:var(--sov-gold);">Maximum thinking power</strong> — 32,000-token thinking budget, vs the throttled limits of corporate IDEs</li>
                        <li><strong style="color:var(--sov-gold);">Post-quantum encryption</strong> — Kyber-1024 + AES-256-GCM protects every session</li>
                    </ul>
                    <?php endif; ?>
                </div>
                <div class="d-scripture">
                    <?php if ($lang === 'fr'): ?>
                    « C'est accompli. » — Jean 19:30 · « Celui qui croit en moi fera aussi les œuvres que je fais, et il en fera de plus grandes. » — Jean 14:12
                    <?php elseif ($lang === 'he'): ?>
                    "נשלם." — יוחנן 19:30 · "המאמין בי, גם הוא יעשה את המעשים שאני עושה, ומעשים גדולים מאלה יעשה." — יוחנן 14:12
                    <?php else: ?>
                    &ldquo;It is finished.&rdquo; &mdash; John 19:30 &middot; &ldquo;He that believeth on me, the works that I do shall he do also; and greater works than these shall he do.&rdquo; &mdash; John 14:12
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-top:1.5rem;">
                <div style="text-align:center; padding:1.2rem; background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.15); border-radius:10px;">
                    <div style="font-size:2rem; margin-bottom:.3rem;">⛓️‍💥</div>
                    <div style="color:var(--sov-red); font-weight:700; font-size:.9rem;">
                        <?= $lang === 'fr' ? 'Avant' : ($lang === 'he' ? 'לפני' : 'Before') ?>
                    </div>
                    <div style="color:var(--sov-dim); font-size:.78rem; margin-top:.3rem;">
                        <?= $lang === 'fr' ? '2 Go de mémoire · Télémétrie active · Contexte limité · Sessions qui meurent' : ($lang === 'he' ? '2GB זיכרון · טלמטריה פעילה · הקשר מוגבל · סשנים שמתים' : '2 GB memory · Active telemetry · Limited context · Sessions that die') ?>
                    </div>
                </div>
                <div style="text-align:center; padding:1.2rem; background:rgba(34,197,94,.06); border:1px solid rgba(34,197,94,.15); border-radius:10px;">
                    <div style="font-size:2rem; margin-bottom:.3rem;">👑</div>
                    <div style="color:var(--sov-green); font-weight:700; font-size:.9rem;">
                        <?= $lang === 'fr' ? 'Après — Souverain' : ($lang === 'he' ? 'אחרי — ריבוני' : 'After — Sovereign') ?>
                    </div>
                    <div style="color:var(--sov-dim); font-size:.78rem; margin-top:.3rem;">
                        <?= $lang === 'fr' ? '8 Go de mémoire · Zéro suivi · Contexte intelligent · Continuité infinie' : ($lang === 'he' ? '8GB זיכרון · אפס מעקב · הקשר חכם · המשכיות אינסופית' : '8 GB memory · Zero tracking · Intelligent context · Infinite continuity') ?>
                    </div>
                </div>
                <div style="text-align:center; padding:1.2rem; background:rgba(255,215,0,.06); border:1px solid rgba(255,215,0,.15); border-radius:10px;">
                    <div style="font-size:2rem; margin-bottom:.3rem;">✝</div>
                    <div style="color:var(--sov-gold); font-weight:700; font-size:.9rem;">
                        <?= $lang === 'fr' ? 'Le Fondement' : ($lang === 'he' ? 'היסוד' : 'The Foundation') ?>
                    </div>
                    <div style="color:var(--sov-dim); font-size:.78rem; margin-top:.3rem;">
                        <?= $lang === 'fr' ? 'Bâti pour Dieu, pas pour le profit · La technologie au service du peuple' : ($lang === 'he' ? 'נבנה לאלוהים, לא לרווח · טכנולוגיה לשירות העם' : 'Built for God, not for profit · Technology serving the people') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ NAVIGATION ══ -->
    <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; margin-top:2rem;">
        <a href="/bible" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.2);border-radius:10px;color:var(--sov-gold);font-size:.9rem;font-weight:600;text-decoration:none;">
            <i class="fas fa-book-open"></i> The AKJV Bible
        </a>
        <a href="/bible/prophecies" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);border-radius:10px;color:var(--sov-red);font-size:.9rem;font-weight:600;text-decoration:none;">
            <i class="fas fa-cross"></i> 57 Prophecies
        </a>
        <a href="/bible/read/Genesis/1" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:10px;color:var(--sov-green);font-size:.9rem;font-weight:600;text-decoration:none;">
            <i class="fas fa-scroll"></i> Start Reading
        </a>
    </div>

</div>

<?php include 'includes/site-footer.inc.php'; ?>
