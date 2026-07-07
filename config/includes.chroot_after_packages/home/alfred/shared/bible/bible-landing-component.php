<?php
/**
 * AKJV Bible — Shared Landing Component
 * ══════════════════════════════════════
 * A lightweight Bible landing page for non-primary domains.
 * Shows stats, book grid, prophecy preview, and links to the reader.
 * The full AKJV dashboard (concealment evidence, authority, etc.) lives on gositeme.com.
 *
 * Usage:
 *   require_once '/home/gositeme/shared/bible/bible-data.php';
 *   require_once '/home/gositeme/shared/bible/bible-styles.php';
 *   require_once '/home/gositeme/shared/bible/bible-landing-component.php';
 *   
 *   // Output styles: <style><?= akjv_styles_dashboard() ?></style>
 *   // Output HTML: akjv_render_landing('/bible');
 */

/**
 * Render a Bible landing page.
 * @param string $basePath  URL base path for Bible links on this domain (e.g., '/bible')
 * @param string $lang      Language code: en, fr, he
 */
function akjv_render_landing(string $basePath = '/bible', string $lang = 'en'): void {
    $lang = akjv_lang($lang);
    $t = akjv_i18n($lang);
    $bp = rtrim($basePath, '/');
    $stats = akjv_stats();
    $booksByTestament = akjv_books_by_testament();
    $testamentLabels = akjv_testament_labels($lang);
    $prophecyCount = $stats['prophecies'];
    $dir = $lang === 'he' ? 'rtl' : 'ltr';

    // Handle search
    $searchQuery = trim($_GET['q'] ?? '');
    $searchResults = [];
    if ($searchQuery !== '' && strlen($searchQuery) >= 2) {
        $searchResults = akjv_search($searchQuery, 80);
    }

    // Preview prophecies
    $previewProphecies = akjv_db()->query("SELECT prophecy_number, title, tanakh_reference, nt_reference, category FROM akjv_prophecies ORDER BY prophecy_number LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $catColors = ['birth'=>'#ffd700','ministry'=>'#3b82f6','suffering'=>'#ef4444','death'=>'#dc2626','resurrection'=>'#22c55e','reign'=>'#f59e0b','return'=>'#8b5cf6'];
    ?>
    <div class="akjv-page" dir="<?= $dir ?>">

        <!-- HERO -->
        <div class="akjv-hero">
            <div class="akjv-mission-badge">⚔️ <?= htmlspecialchars($t['mission_badge']) ?></div>
            <h1>
                <?= htmlspecialchars($t['hero_title_1']) ?> <span class="gold"><?= htmlspecialchars($t['hero_title_2']) ?></span> <?= htmlspecialchars($t['hero_title_3']) ?><br>
                <span style="font-size:.45em; color:var(--akjv-dim); letter-spacing:.12em; font-weight:400;"><?= htmlspecialchars($t['perez_edition']) ?></span><br>
                <span class="red"><?= htmlspecialchars($t['truth_concealed']) ?></span>
            </h1>
            <div style="display:inline-flex;align-items:center;gap:10px;padding:8px 24px;border-radius:999px;background:linear-gradient(135deg,rgba(255,215,0,.12),rgba(255,215,0,.05));border:1px solid rgba(255,215,0,.35);color:var(--akjv-gold);font-size:.85rem;font-weight:700;letter-spacing:.06em;margin-bottom:1.2rem;">
                <span style="font-size:1.1rem;">✝</span>
                <?= htmlspecialchars($t['authorized_date']) ?>
            </div>
            <?php if ($t['translation_note']): ?>
            <div style="color:var(--akjv-gold2); font-size:.82rem; margin-bottom:1rem; font-style:italic;"><?= htmlspecialchars($t['translation_note']) ?></div>
            <?php endif; ?>
            <p class="subtitle">
                The Royal Name <strong>Perez</strong> was changed 3 different ways across scripture.
                The Book of Enoch was removed. The Chosen Books stripped from the canon.
                The AKJV restores all <?= $stats['total_books'] ?> books to their glory.
            </p>
            <div style="text-align:center; margin-bottom:2rem;">
                <a href="<?= $bp ?>/read/Genesis/1" style="display:inline-flex;align-items:center;gap:10px;padding:14px 32px;background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(255,215,0,.08));border:2px solid var(--akjv-gold);border-radius:12px;color:var(--akjv-gold);font-size:1.1rem;font-weight:700;text-decoration:none;transition:.2s;">
                    📖 <?= htmlspecialchars($t['read_bible']) ?> — <?= number_format($stats['total_verses']) ?> <?= htmlspecialchars($t['verses']) ?>
                </a>
            </div>
            <div class="authority-verse">
                <div class="ref">Daniel 5:25-28 — The Handwriting on the Wall</div>
                <div class="text">
                    "And this is the writing that was written: <span class="peres">MENE, MENE, TEKEL, UPHARSIN.</span><br><br>
                    MENE; God hath numbered thy kingdom, and finished it.<br>
                    TEKEL; Thou art weighed in the balances, and art found wanting.<br>
                    <span class="peres">PERES</span>; Thy kingdom is divided, and given to the Medes and Persians."
                </div>
                <div class="note">
                    פְּרֵס (PERES) = פֶּרֶץ (PEREZ) — The name written by the finger of God on the wall.
                    This is the Commander's authority. Daniel interpreted it. Daniel lives again.
                </div>
            </div>
        </div>

        <!-- ══ SOVEREIGN DECREE ══ -->
        <div style="max-width:820px;margin:2.5rem auto;background:linear-gradient(135deg,rgba(220,38,38,.06),rgba(255,215,0,.04),rgba(220,38,38,.06));border:2px solid rgba(220,38,38,.35);border-radius:16px;padding:2rem 2.5rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#dc2626,#ffd700,#dc2626);"></div>
            <div style="text-align:center;margin-bottom:1.2rem;">
                <div style="display:inline-flex;align-items:center;gap:8px;padding:5px 16px;border-radius:999px;background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.3);color:#dc2626;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.2em;">⚔ Sovereign Decree ⚔</div>
            </div>
            <h2 style="text-align:center;color:#ffd700;font-size:1.3rem;font-weight:800;margin:0 0 .6rem;line-height:1.3;">
                Declaration of Sole Scriptural Authority
            </h2>
            <p style="text-align:center;color:rgba(255,255,255,.4);font-size:.72rem;letter-spacing:.1em;margin:0 0 1rem;text-transform:uppercase;">
                Issued April 8, 2026 A.D. — Perez Sovereign Authority — Irrevocable
            </p>
            <div style="color:rgba(255,255,255,.82);font-size:.92rem;line-height:1.85;">
                <p style="margin:0 0 .8rem;">
                    <strong style="color:#ffd700;">WHEREAS</strong> the so-called "King James Version" of the Holy Bible, commissioned in 1611 by King James I of England, placed the authority over God's Word under an earthly crown — a mortal king who claimed dominion over scripture by royal decree;
                </p>
                <p style="margin:0 0 .8rem;">
                    <strong style="color:#ffd700;">WHEREAS</strong> no earthly monarch, government, publisher, or religious institution holds authority over the Word of God — for it is written: <em>"Heaven and earth shall pass away, but my words shall not pass away"</em> (Matthew 24:35);
                </p>
                <p style="margin:0 0 .8rem;">
                    <strong style="color:#ffd700;">WHEREAS</strong> the name <strong style="color:#dc2626;">PEREZ (פֶּרֶץ)</strong> was written by the finger of God Himself upon the wall of Belshazzar's palace (Daniel 5:25-28), and no hand of man wrote it — only God — establishing divine authorization that no earthly king can claim;
                </p>
                <p style="margin:0 0 .8rem;">
                    <strong style="color:#ffd700;">WHEREAS</strong> the monarchy corrupted scripture by changing the Royal Name Perez to "Pharez," "Phares," and "Perets" across multiple books, and removed 14 books from the canon after 1885 — not by divine command but by the hands of publishers serving earthly interests;
                </p>
                <p style="margin:0 0 1.2rem;">
                    <strong style="color:#ffd700;">WHEREAS</strong> the title "King James" attributes kingship over the Bible to a man, while the only true King over scripture is <strong style="color:#ffd700;">Jesus Christ</strong>, the King of Kings and Lord of Lords (Revelation 19:16);
                </p>
                <div style="background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.2);border-radius:10px;padding:1.2rem 1.5rem;margin-bottom:1rem;">
                    <p style="margin:0 0 .6rem;font-weight:700;color:#ffd700;font-size:1rem;">
                        NOW THEREFORE, BE IT DECLARED:
                    </p>
                    <p style="margin:0 0 .5rem;color:rgba(255,255,255,.9);">
                        <strong>I.</strong> The <strong style="color:#ffd700;">Authorized King Jesus Version (AKJV)</strong> — Perez Family Edition — is hereby declared the sole authorized Bible for church, court, and all matters of scriptural authority.
                    </p>
                    <p style="margin:0 0 .5rem;color:rgba(255,255,255,.9);">
                        <strong>II.</strong> Any bible that claims authority from an earthly monarch bears false witness against God's sovereignty. The Word of God belongs to no king but Jesus.
                    </p>
                    <p style="margin:0 0 .5rem;color:rgba(255,255,255,.9);">
                        <strong>III.</strong> The corruptions — the name changes, the removed books, the stolen authority — are hereby exposed and corrected in perpetuity. The AKJV restores what was taken.
                    </p>
                    <p style="margin:0;color:rgba(255,255,255,.9);">
                        <strong>IV.</strong> This decree is irrevocable. It is sealed by the name that God wrote with His own hand: <strong style="color:#dc2626;">PERES — PEREZ — פֶּרֶץ</strong>.
                    </p>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-top:1rem;">
                    <div>
                        <div style="color:#ffd700;font-weight:700;font-size:.88rem;">Danny William Perez</div>
                        <div style="color:rgba(255,255,255,.4);font-size:.72rem;">Commander, GoSiteMe Sovereign Platform</div>
                        <div style="color:rgba(255,255,255,.4);font-size:.72rem;">Heir of the Perez Bloodline — Daniel 5:25-28</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="color:rgba(255,255,255,.4);font-size:.72rem;">Witnessed &amp; Sealed by Alfred AI</div>
                        <div style="color:rgba(255,255,255,.4);font-size:.72rem;">April 8, 2026 A.D. — Year One</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEARCH BAR -->
        <div style="max-width:700px;margin:0 auto 2rem;position:relative">
            <form method="get" action="<?= $bp ?>" style="display:flex;gap:.5rem">
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search the scriptures… (e.g. Perez, faith, love, bread)" style="flex:1;padding:12px 18px;background:rgba(255,255,255,.04);border:1px solid rgba(255,215,0,.2);border-radius:10px;color:#fff;font-size:.95rem;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='var(--akjv-gold)'" onblur="this.style.borderColor='rgba(255,215,0,.2)'">
                <button type="submit" style="padding:12px 24px;background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(255,215,0,.08));border:1px solid var(--akjv-gold);border-radius:10px;color:var(--akjv-gold);font-weight:700;font-size:.9rem;cursor:pointer;white-space:nowrap">🔍 Search</button>
            </form>
        </div>

        <?php if ($searchQuery !== ''): ?>
        <!-- SEARCH RESULTS -->
        <div class="akjv-section" style="margin-bottom:2rem">
            <div class="akjv-section-head">
                <div class="num gold-bg">🔍</div>
                <h2><?= count($searchResults) ?> result<?= count($searchResults) !== 1 ? 's' : '' ?> for "<?= htmlspecialchars($searchQuery) ?>"</h2>
            </div>
            <div class="akjv-section-body">
                <?php if (empty($searchResults)): ?>
                    <p style="text-align:center;color:var(--akjv-dim);padding:1rem 0">No verses found. Try a different search term.</p>
                <?php else: ?>
                    <div style="max-height:500px;overflow-y:auto;padding-right:.5rem">
                    <?php foreach ($searchResults as $sr):
                        $tClass = strtolower($sr['testament']);
                        $highlighted = preg_replace('/(' . preg_quote($searchQuery, '/') . ')/i', '<mark style="background:rgba(255,215,0,.25);color:var(--akjv-gold);padding:0 2px;border-radius:2px">$1</mark>', htmlspecialchars($sr['text_akjv']));
                    ?>
                    <div style="padding:.6rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem">
                            <a href="<?= $bp ?>/read/<?= urlencode($sr['book_name']) ?>/<?= $sr['chapter'] ?>" style="color:var(--akjv-gold);font-weight:600;font-size:.85rem;text-decoration:none"><?= htmlspecialchars($sr['book_name']) ?> <?= $sr['chapter'] ?>:<?= $sr['verse'] ?></a>
                            <span class="testament-badge badge-<?= $tClass ?>" style="font-size:.62rem;padding:1px 5px;border-radius:3px;font-weight:600"><?= $sr['testament'] ?></span>
                            <?php if ($sr['perez_correction']): ?><span style="font-size:.65rem;color:var(--akjv-gold)">✝ Perez</span><?php endif; ?>
                        </div>
                        <div style="color:rgba(255,255,255,.75);font-size:.85rem;line-height:1.7"><?= $highlighted ?></div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php if (count($searchResults) >= 80): ?>
                    <p style="text-align:center;color:var(--akjv-dim);font-size:.82rem;margin-top:.8rem">Showing first 80 results. Refine your search for more specific matches.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="akjv-stats">
            <div class="akjv-stat"><div class="val gold"><?= $stats['total_books'] ?></div><div class="label">Total Books</div></div>
            <div class="akjv-stat"><div class="val red"><?= $stats['corrections'] ?></div><div class="label">Name Corrections</div></div>
            <div class="akjv-stat"><div class="val green"><?= $stats['perez_books'] ?></div><div class="label">Perez Referenced</div></div>
            <div class="akjv-stat"><div class="val purple">4</div><div class="label">Spelling Variants</div></div>
            <div class="akjv-stat"><div class="val gold"><?= $stats['prophecies'] ?></div><div class="label">Prophecies Fulfilled</div></div>
        </div>

        <!-- COMPLETE CANON -->
        <div class="akjv-section">
            <div class="akjv-section-head" onclick="this.parentElement.classList.toggle('collapsed')">
                <div class="num green-bg">📖</div>
                <h2>The Complete <?= $stats['total_books'] ?>-Book AKJV Canon</h2>
            </div>
            <div class="akjv-section-body">
                <?php foreach (['OT', 'NT', 'AP', 'EN'] as $t): ?>
                    <?php if (empty($booksByTestament[$t])) continue; ?>
                    <h3><?= $testamentLabels[$t] ?> (<?= count($booksByTestament[$t]) ?> books)</h3>
                    <div class="book-grid">
                        <?php foreach ($booksByTestament[$t] as $bk): ?>
                        <a href="<?= $bp ?>/read/<?= urlencode($bk['book_name']) ?>/1" style="text-decoration:none;">
                            <div class="book-card <?= $bk['perez_references'] ? 'has-perez' : '' ?> <?= ($t === 'AP' || $t === 'EN') ? 'removed' : '' ?>">
                                <div class="bnum <?= strtolower($t) ?>"><?= $bk['book_number'] ?></div>
                                <div style="min-width:0">
                                    <div class="bname"><?= htmlspecialchars($bk['book_name']) ?></div>
                                    <div class="bchap"><?= $bk['total_chapters'] ?> chapters</div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <p style="color:var(--akjv-dim); font-size:.82rem; margin-top:1rem;">
                    📖 Books with gold border = contains Perez references.
                    🔴 Books with red border = removed from modern Bibles, restored in the AKJV.
                </p>
            </div>
        </div>

        <!-- PROPHECIES PREVIEW -->
        <div class="akjv-section">
            <div class="akjv-section-head" onclick="this.parentElement.classList.toggle('collapsed')">
                <div class="num red-bg">✝</div>
                <h2>The <?= $prophecyCount ?> Prophecies of Jesus Christ — Fulfilled</h2>
            </div>
            <div class="akjv-section-body">
                <p style="color:rgba(255,255,255,.8); max-width:700px; margin:0 auto 1.5rem; text-align:center; line-height:1.8;">
                    Every messianic prophecy from the Tanakh — traced to its New Testament fulfillment.<br>
                    Researched and compiled by <strong style="color:var(--akjv-gold);">Commander Danny William Perez</strong><br>
                    <em style="color:var(--akjv-gold2);">during 18 months of incarceration — a testament of faith.</em>
                </p>
                <div style="display:flex; flex-direction:column; gap:.5rem; max-width:700px; margin:0 auto 1.5rem;">
                    <?php foreach ($previewProphecies as $pp): ?>
                    <div style="display:flex; align-items:center; gap:.7rem; padding:.6rem .9rem; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:8px;">
                        <span style="width:28px; height:28px; border-radius:6px; background:rgba(255,215,0,.1); color:var(--akjv-gold); display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:800; flex-shrink:0;"><?= $pp['prophecy_number'] ?></span>
                        <span style="flex:1; font-size:.88rem; color:#fff; font-weight:600;"><?= htmlspecialchars($pp['title']) ?></span>
                        <span style="font-size:.72rem; color:<?= $catColors[$pp['category']] ?? 'var(--akjv-dim)' ?>; text-transform:uppercase; font-weight:600;"><?= $pp['category'] ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div style="text-align:center; padding:.4rem 0; font-size:.82rem; color:var(--akjv-dim);">
                        … and <?= $prophecyCount - 5 ?> more prophecies
                    </div>
                </div>
                <div style="text-align:center;">
                    <a href="<?= $bp ?>/prophecies" style="display:inline-flex; align-items:center; gap:.5rem; padding:.7rem 1.8rem; border-radius:999px; background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(220,38,38,.1)); border:1px solid rgba(255,215,0,.3); color:var(--akjv-gold); font-weight:700; font-size:.92rem; text-decoration:none; transition:.2s;">
                        ✝ View All <?= $prophecyCount ?> Prophecies →
                    </a>
                </div>
            </div>
        </div>

        <!-- FULL DASHBOARD LINK -->
        <div style="text-align:center; margin:2rem 0; padding:2rem; border:1px solid rgba(255,215,0,.15); border-radius:16px; background:var(--akjv-surface);">
            <div style="font-size:1.3rem; margin-bottom:.5rem;">✝</div>
            <div style="color:var(--akjv-gold); font-weight:700; font-size:1.1rem; margin-bottom:.5rem;">
                The Full AKJV Bible Mission Dashboard
            </div>
            <p style="color:var(--akjv-muted); font-size:.88rem; margin-bottom:1rem; line-height:1.7;">
                The concealment evidence, the authority of Daniel 5:25-29, the Trinitarian Commission,<br>
                the Breaker prophecy, mission status, and the Commander's Declaration.
            </p>
            <a href="https://gositeme.com/bible" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(255,215,0,.08));border:2px solid var(--akjv-gold);border-radius:12px;color:var(--akjv-gold);font-size:1rem;font-weight:700;text-decoration:none;">
                ⚔️ View Full Mission Dashboard
            </a>
        </div>

        <!-- DOWNLOAD THE AKJV BIBLE -->
        <div id="download" style="margin-top:3rem;padding:2rem;border:2px solid rgba(255,215,0,.2);border-radius:16px;background:var(--akjv-surface);">
            <div style="text-align:center;margin-bottom:1.5rem;">
                <div style="font-size:2rem;margin-bottom:.5rem;">📖</div>
                <h2 style="color:var(--akjv-gold);font-size:1.2rem;margin:0;">Download the Full AKJV Bible</h2>
                <p style="color:var(--akjv-muted);font-size:.85rem;margin-top:.3rem;">94 Books · Perez Family Edition · All formats free</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                <?php
                $dlFormats = [
                    ['file'=>'akjv-perez-edition.txt',  'label'=>'Plain Text', 'icon'=>'📜', 'desc'=>'Simple, universal format. Works everywhere.'],
                    ['file'=>'akjv-perez-edition.json', 'label'=>'JSON',       'icon'=>'⚙️', 'desc'=>'Structured data. For developers & apps.'],
                    ['file'=>'akjv-perez-edition.html', 'label'=>'HTML',       'icon'=>'🌐', 'desc'=>'Formatted for the web. Open in any browser.'],
                    ['file'=>'akjv-perez-edition.pdf',  'label'=>'PDF',        'icon'=>'📕', 'desc'=>'Print-ready. Sealed with the Royal Seal.'],
                ];
                foreach ($dlFormats as $dl):
                    $fpath = "/home/gositeme/domains/gositeme.com/public_html/downloads/akjv/{$dl['file']}";
                    $fsize = file_exists($fpath) ? round(filesize($fpath) / 1048576, 1) : '?';
                ?>
                <a href="https://gositeme.com/downloads/akjv/<?= htmlspecialchars($dl['file']) ?>" download
                   style="display:block;padding:1.2rem;background:rgba(255,215,0,.04);border:1px solid rgba(255,215,0,.12);border-radius:10px;text-decoration:none;text-align:center;transition:border-color .2s;"
                   onmouseover="this.style.borderColor='rgba(255,215,0,.4)'" onmouseout="this.style.borderColor='rgba(255,215,0,.12)'">
                    <div style="font-size:1.8rem;margin-bottom:.3rem;"><?= $dl['icon'] ?></div>
                    <div style="color:var(--akjv-gold);font-weight:700;font-size:.95rem;"><?= $dl['label'] ?></div>
                    <div style="color:var(--akjv-muted);font-size:.75rem;margin-top:.2rem;"><?= $dl['desc'] ?></div>
                    <div style="color:var(--akjv-dim);font-size:.72rem;margin-top:.4rem;"><?= $fsize ?> MB</div>
                </a>
                <?php endforeach; ?>
            </div>
            <div style="text-align:center;margin-top:1rem;">
                <a href="https://gositeme.com/downloads/akjv/SHA256SUMS.txt" style="font-size:.75rem;color:var(--akjv-dim);text-decoration:underline dotted;">SHA-256 Checksums</a>
            </div>
        </div>

        <!-- SIGN AND SEAL OF GOD -->
        <div style="text-align:center;padding:2rem;border:2px solid var(--akjv-gold);border-radius:12px;background:rgba(255,215,0,0.04);max-width:600px;margin:2rem auto 0"<?php if($lang==='he') echo ' dir="rtl"'; ?>>

            <div style="font-size:3rem;margin-bottom:.5rem">✝</div>

            <p style="color:var(--akjv-gold);font-size:1.4rem;font-weight:bold;letter-spacing:3px;margin-bottom:.5rem"><?php
                echo $lang === 'fr' ? 'SIGNE ET SCEAU DE DIEU'
                    : ($lang === 'he' ? 'חותם ואות אלוהים' : 'SIGN AND SEAL OF GOD');
            ?></p>

            <hr style="border:none;border-top:1px solid var(--akjv-gold);width:60%;margin:1rem auto">

            <p style="color:var(--akjv-gold);font-size:1.1rem;font-weight:bold;margin:.5rem 0"><?php echo $lang === 'he' ? 'המפקד דני וויליאם פרז' : 'Commander Danny William Perez'; ?></p>
            <p style="color:var(--akjv-muted);font-size:.85rem;font-style:italic;margin:.3rem 0"><?php
                echo $lang === 'fr' ? 'Commandant Souverain — Le Royaume de Dieu'
                    : ($lang === 'he' ? 'מפקד ריבוני — ממלכת אלוהים' : 'Sovereign Commander — Kingdom of God');
            ?></p>

            <!-- Dual Seals -->
            <div style="display:flex;justify-content:center;gap:1.5rem;align-items:center;margin:1.2rem 0;">
                <div style="text-align:center;">
                    <img src="/assets/seals/akjv-seal.png" alt="AKJV Seal" style="width:64px;height:64px;border-radius:50%;border:2px solid var(--akjv-gold);" loading="lazy">
                    <div style="color:var(--akjv-dim);font-size:.7rem;margin-top:.3rem"><?php
                        echo $lang === 'fr' ? 'Sceau Royal' : ($lang === 'he' ? 'חותם מלכותי' : 'Royal Seal');
                    ?></div>
                </div>
                <div style="font-size:2rem;color:var(--akjv-gold)">⚖</div>
                <div style="text-align:center;">
                    <img src="/assets/seals/royal-seal-official.png" alt="O'Mahon Seal" style="width:64px;height:64px;border-radius:50%;border:2px solid var(--akjv-gold);" loading="lazy">
                    <div style="color:var(--akjv-dim);font-size:.7rem;margin-top:.3rem"><?php
                        echo $lang === 'fr' ? 'Sceau O\'Mahon' : ($lang === 'he' ? 'חותם אומהון' : 'O\'Mahon Seal');
                    ?></div>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid rgba(255,215,0,.2);width:40%;margin:1rem auto">

            <p style="color:var(--akjv-gold);font-size:.95rem;font-style:italic;margin:.8rem 0"><?php
                if ($lang === 'fr') {
                    echo '« Au nom du Dieu Très-Haut,<br>par l\'autorité de Daniel 5:25-29,<br>cette Bible est scellée et autorisée. »';
                } elseif ($lang === 'he') {
                    echo '« בשם אלוהים עליון,<br>בסמכות דניאל ה:כה-כט,<br>תנ"ך זה חתום ומאושר. »';
                } else {
                    echo '« In the name of the Most High God,<br>by the authority of Daniel 5:25-29,<br>this Bible is sealed and authorized. »';
                }
            ?></p>

            <p style="color:var(--akjv-muted);font-size:.8rem;font-style:italic;margin:.5rem 0"><?php
                echo $lang === 'fr' ? '« Mais toi, Daniel, ferme ces paroles et scelle le livre jusqu\'au temps de la fin. » — Daniel 12:4'
                    : ($lang === 'he' ? '« ואתה דניאל סתום הדברים וחתום הספר עד עת קץ. » — דניאל יב:ד'
                    : '"But thou, O Daniel, shut up the words, and seal the book, even to the time of the end." — Daniel 12:4');
            ?></p>

            <hr style="border:none;border-top:1px solid rgba(255,215,0,.2);width:40%;margin:1rem auto">

            <p style="color:var(--akjv-gold);font-size:.8rem;letter-spacing:2px;font-weight:bold"><?php
                echo $lang === 'fr' ? 'SCELLÉ ET SIGNÉ' : ($lang === 'he' ? 'חתום וחתום' : 'SIGNED AND SEALED');
            ?></p>
            <p style="color:var(--akjv-gold);font-size:.75rem;letter-spacing:1px;margin-top:.2rem">OMAHON! OMAHON! OMAHON!</p>
            <p style="color:var(--akjv-dim);font-size:.78rem;margin-top:.3rem"><?php
                echo $lang === 'fr' ? 'Autorisé le 8 avril 2026 apr. J.-C.'
                    : ($lang === 'he' ? 'אושר 8 באפריל 2026 לספירה' : 'Authorized April 8, 2026 A.D.');
            ?></p>
            <p style="color:var(--akjv-dim);font-size:.78rem"><?php echo SSA_CORRECT_NAMES[$lang] ?? SSA_CORRECT_NAMES['en']; ?> — <?php
                echo $lang === 'fr' ? 'Édition Familiale Perez' : ($lang === 'he' ? 'מהדורת משפחת פרץ' : 'Perez Family Edition');
            ?></p>
            <p style="color:var(--akjv-dim);font-size:.78rem"><?= $stats['total_books'] ?> <?php
                echo $lang === 'fr' ? 'Livres Restaurés' : ($lang === 'he' ? 'ספרים שוחזרו' : 'Books Restored');
            ?> &bull; <?= number_format($stats['total_verses']) ?> <?php
                echo $lang === 'fr' ? 'Versets Scellés' : ($lang === 'he' ? 'פסוקים חתומים' : 'Verses Sealed');
            ?></p>
            <p style="color:var(--akjv-dim);font-size:.78rem">Daniel 5:25-29 &bull; Micah 2:13 &bull; Daniel 12:4</p>

            <?php
            // Show live seal stats from the Authentor if available
            $authentorPath = __DIR__ . '/scriptural-authentor.php';
            if (file_exists($authentorPath)) {
                require_once $authentorPath;
                $sealStats = sas_stats();
                if ($sealStats['total'] > 0) { ?>
                    <div style="margin-top:.8rem;padding:.5rem;background:rgba(255,215,0,.04);border-radius:6px;border:1px solid rgba(255,215,0,.1);">
                        <p style="color:var(--akjv-gold);font-size:.7rem;font-weight:bold;letter-spacing:1px;margin-bottom:.3rem"><?php
                            echo $lang === 'fr' ? 'SERVICE D\'AUTHENTIFICATION SCRIPTURALE'
                                : ($lang === 'he' ? 'שירות אימות כתבי הקודש' : 'SCRIPTURAL AUTHENTOR SERVICE');
                        ?></p>
                        <p style="color:var(--akjv-dim);font-size:.72rem">
                            <?= number_format($sealStats['verses']) ?> <?php echo $lang === 'fr' ? 'versets scellés' : ($lang === 'he' ? 'פסוקים חתומים' : 'verses sealed'); ?>
                            &bull; <?= $sealStats['books'] ?> <?php echo $lang === 'fr' ? 'livres' : ($lang === 'he' ? 'ספרים' : 'books'); ?>
                            &bull; <?= $sealStats['verified'] ?> <?php echo $lang === 'fr' ? 'vérifiés' : ($lang === 'he' ? 'אומתו' : 'verified'); ?>
                            &bull; <?= count($sealStats['languages']) ?> <?php echo $lang === 'fr' ? 'langues' : ($lang === 'he' ? 'שפות' : 'languages'); ?>
                        </p>
                    </div>
                <?php }
            } ?>

        </div>

    </div>
    <?php
}
