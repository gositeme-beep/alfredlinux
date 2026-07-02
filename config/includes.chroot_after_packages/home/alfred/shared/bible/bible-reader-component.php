<?php
/**
 * AKJV Bible — Shared Reader Component
 * ═════════════════════════════════════
 * Renders the Bible reading interface with sidebar, chapter selector,
 * verse display, and navigation. Domain-agnostic.
 *
 * Usage:
 *   require_once '/home/gositeme/shared/bible/bible-data.php';
 *   require_once '/home/gositeme/shared/bible/bible-styles.php';
 *   require_once '/home/gositeme/shared/bible/bible-reader-component.php';
 *   
 *   $ctx = akjv_reader_context($requestedBook, $requestedChapter);
 *   // Output styles: <style><?= akjv_styles_reader() ?></style>
 *   // Output HTML: akjv_render_reader($ctx, '/bible');
 */

/**
 * Build the reader context (book, chapter, verses, navigation).
 * @param string $bookName  The requested book name
 * @param int    $chapter   The requested chapter
 * @return array   Context array with all rendering data
 */
function akjv_reader_context(string $bookName, int $chapter = 1, string $lang = 'en'): array {
    $lang = akjv_lang($lang);
    $allBooks = akjv_all_books();
    $testamentLabels = akjv_testament_labels($lang);

    // Find the requested book
    $currentBook = akjv_find_book($bookName, $allBooks);
    if (!$currentBook) {
        $currentBook = $allBooks[0]; // Fallback to Genesis
        $chapter = 1;
    }

    // Clamp chapter
    $chapter = max(1, min($chapter, $currentBook['total_chapters']));

    // Load verses
    $verses = akjv_verses($currentBook['id'], $chapter);

    // Navigation
    $prevChapter = $chapter > 1 ? $chapter - 1 : null;
    $nextChapter = $chapter < $currentBook['total_chapters'] ? $chapter + 1 : null;

    $prevBook = null;
    $nextBook = null;
    foreach ($allBooks as $idx => $b) {
        if ($b['id'] === $currentBook['id']) {
            if ($idx > 0) $prevBook = $allBooks[$idx - 1];
            if ($idx < count($allBooks) - 1) $nextBook = $allBooks[$idx + 1];
            break;
        }
    }

    // Build prev/next URLs (relative — caller prefixes domain path)
    $prevUrl = null;
    $nextUrl = null;
    if (!$prevChapter && $prevBook) {
        $prevUrl = '/read/' . urlencode($prevBook['book_name']) . '/' . $prevBook['total_chapters'];
    } elseif ($prevChapter) {
        $prevUrl = '/read/' . urlencode($currentBook['book_name']) . '/' . $prevChapter;
    }
    if (!$nextChapter && $nextBook) {
        $nextUrl = '/read/' . urlencode($nextBook['book_name']) . '/1';
    } elseif ($nextChapter) {
        $nextUrl = '/read/' . urlencode($currentBook['book_name']) . '/' . $nextChapter;
    }

    return compact('allBooks', 'currentBook', 'chapter', 'verses', 'prevUrl', 'nextUrl', 'testamentLabels', 'lang');
}

/**
 * Render the Bible reader HTML.
 * @param array  $ctx       Context from akjv_reader_context()
 * @param string $basePath  URL base path for Bible links (e.g., '/bible' or '/akjv')
 */
function akjv_render_reader(array $ctx, string $basePath = '/bible'): void {
    extract($ctx);
    $lang = $lang ?? 'en';
    $t = akjv_i18n($lang);
    $bp = rtrim($basePath, '/');
    $dir = $lang === 'he' ? 'rtl' : 'ltr';
    ?>
    <div class="bible-reader" id="bibleReader" dir="<?= $dir ?>">
        <!-- SIDEBAR -->
        <nav class="bible-sidebar" id="bibleSidebar">
            <h3>📖 AKJV Canon — A.D. 2026</h3>
            <div class="sidebar-search">
                <input type="text" id="bookSearch" placeholder="<?= htmlspecialchars($t['search_books']) ?>" oninput="filterBooks(this.value)">
            </div>
            <div style="padding:0 .8rem .6rem">
                <a href="<?= $bp ?>?q=" style="display:flex;align-items:center;gap:6px;padding:6px 10px;background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.15);border-radius:6px;color:var(--akjv-gold);font-size:.75rem;text-decoration:none;font-weight:600">🔍 <?= htmlspecialchars($t['search_all']) ?></a>
            </div>
            <?php
            $lastTestament = '';
            foreach ($allBooks as $b):
                if ($b['testament'] !== $lastTestament):
                    $lastTestament = $b['testament'];
            ?>
            <div class="sidebar-testament"><?= $testamentLabels[$b['testament']] ?? $b['testament'] ?></div>
            <?php endif; ?>
            <a href="<?= $bp ?>/read/<?= urlencode($b['book_name']) ?>/1"
               class="sidebar-book <?= $b['id'] === $currentBook['id'] ? 'active' : '' ?> <?= $b['perez_references'] ? 'has-perez' : '' ?>"
               data-book="<?= htmlspecialchars(strtolower(akjv_book_name($b, $lang))) ?>"
               title="<?= htmlspecialchars(akjv_book_name($b, $lang)) ?> — <?= $b['total_chapters'] ?> <?= $t['chapters'] ?>">
                <span class="sb-num"><?= $b['book_number'] ?></span>
                <?= htmlspecialchars(akjv_book_name($b, $lang)) ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="bible-main">
            <!-- Mobile book selector -->
            <button class="mob-book-toggle" onclick="document.getElementById('bibleSidebar').classList.toggle('open')">
                📖 <?= htmlspecialchars(akjv_book_name($currentBook, $lang)) ?> — <?= $t['search_books'] ?>
            </button>

            <div class="bible-header">
                <h1><?= htmlspecialchars(akjv_book_name($currentBook, $lang)) ?> <?= $chapter ?></h1>
                <div class="book-meta">
                    <?= $currentBook['total_chapters'] ?> <?= $t['chapters'] ?>
                    <span class="testament t-<?= strtolower($currentBook['testament']) ?>"><?= $testamentLabels[$currentBook['testament']] ?? $currentBook['testament'] ?></span>
                    <?php if ($currentBook['perez_references']): ?>
                    <span style="color:var(--akjv-gold); margin-left:8px;">⚡ <?= $t['perez_referenced'] ?></span>
                    <?php endif; ?>
                    <span style="color:var(--akjv-dim); margin-left:12px; font-size:.72rem; letter-spacing:.06em;"><?= $t['authorized_date'] ?></span>
                </div>
                <?php if ($t['translation_note']): ?>
                <div style="color:var(--akjv-gold2); font-size:.78rem; margin-top:.4rem; font-style:italic;"><?= $t['translation_note'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Chapter selector -->
            <div class="chapter-selector">
                <?php for ($c = 1; $c <= $currentBook['total_chapters']; $c++): ?>
                <a href="<?= $bp ?>/read/<?= urlencode($currentBook['book_name']) ?>/<?= $c ?>" class="<?= $c === $chapter ? 'active' : '' ?>"><?= $c ?></a>
                <?php endfor; ?>
            </div>

            <?php if (empty($verses)): ?>
            <div class="no-verses">
                <h2>✝ <?= $t['coming_soon'] ?></h2>
                <p><?= sprintf($t['coming_soon_text'], htmlspecialchars(akjv_book_name($currentBook, $lang))) ?></p>
                <a href="<?= $bp ?>" style="color:var(--akjv-gold);">← <?= $t['back_dashboard'] ?></a>
            </div>
            <?php else: ?>

            <!-- Controls -->
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:1rem;">
                <label class="kjv-toggle" onclick="document.getElementById('bibleReader').classList.toggle('show-kjv')">
                    <input type="checkbox" style="display:none"> <?= $t['show_kjv'] ?>
                </label>
                <a href="<?= $bp ?>" style="color:var(--akjv-dim); font-size:.78rem; text-decoration:none;">← <?= $t['back_dashboard'] ?></a>
            </div>

            <!-- Verses -->
            <div class="verse-container">
                <?php foreach ($verses as $v): ?>
                <div class="verse <?= $v['perez_correction'] ? 'corrected' : '' ?>">
                    <span class="vnum"><?= $v['verse'] ?></span>
                    <?= htmlspecialchars(akjv_verse_text($v, $lang)) ?>
                    <?php if ($v['perez_correction']): ?>
                    <span class="correction-badge" title="<?= htmlspecialchars($v['correction_note'] ?? '') ?>">✝ <?= $t['name_restored'] ?></span>
                    <?php endif; ?>
                    <?php if ($v['perez_correction'] && $v['text_kjv'] !== $v['text_akjv']): ?>
                    <div class="kjv-original">⚠ <?= $t['monarchy_text'] ?>: <?= htmlspecialchars($v['text_kjv']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Navigation -->
            <div class="bible-nav">
                <?php if ($prevUrl): ?>
                <a href="<?= $bp . $prevUrl ?>">← <?= $t['previous'] ?></a>
                <?php else: ?><span></span><?php endif; ?>

                <span style="color:var(--akjv-dim); font-size:.82rem;"><?= htmlspecialchars(akjv_book_name($currentBook, $lang)) ?> <?= $chapter ?> <?= $t['of'] ?> <?= $currentBook['total_chapters'] ?></span>

                <?php if ($nextUrl): ?>
                <a href="<?= $bp . $nextUrl ?>"><?= $t['next'] ?> →</a>
                <?php else: ?><span></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function filterBooks(q) {
        q = q.toLowerCase();
        document.querySelectorAll('.sidebar-book').forEach(function(el) {
            el.style.display = el.dataset.book.includes(q) ? '' : 'none';
        });
    }
    </script>
    <?php
}
