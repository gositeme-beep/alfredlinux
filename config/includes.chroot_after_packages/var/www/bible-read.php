<?php
/**
 * AKJV Bible Reader — Read the Authorized King Jesus Version
 * URL: /bible/read or /bible/read/Genesis/1
 * 
 * Now uses the shared Bible library — One Bible, many altars.
 */

// Load language FIRST — lang.php sets $current_lang from cookie (and redirects on ?lang= switch)
require_once __DIR__ . '/includes/lang.php';

require_once '/home/root/shared/bible/bible-data.php';
require_once '/home/root/shared/bible/bible-styles.php';
require_once '/home/root/shared/bible/bible-reader-component.php';

// Language support — $current_lang is set by lang.php (cookie-based, site-wide)
$lang = akjv_lang($current_lang ?? 'en');

// Flipbook integration: use StPageFlip CDN
// https://www.npmjs.com/package/stpageflip
// CDN: https://cdn.jsdelivr.net/npm/stpageflip@2.0.7/dist/js/pageflip.min.js
// CSS: https://cdn.jsdelivr.net/npm/stpageflip@2.0.7/dist/css/pageflip.min.css

// Parse URL: /bible/read/BookName/Chapter
$path = $_SERVER['REQUEST_URI'] ?? '';
$path = strtok($path, '?');
$parts = explode('/', trim($path, '/'));
$requestedBook = urldecode($parts[2] ?? 'Genesis');
$requestedChapter = max(1, (int)($parts[3] ?? 1));
$requestedBook = preg_replace('/[^a-zA-Z0-9 ()\-]/', '', $requestedBook);

// Build context via shared library
$ctx = akjv_reader_context($requestedBook, $requestedChapter, $lang);

$bookDisplay = akjv_book_name($ctx['currentBook'], $lang);
$page_title = "{$bookDisplay} {$ctx['chapter']} — AKJV Bible · Perez Family Edition · Authorized April 8, 2026 A.D.";
$page_canonical = 'https://root.com/bible/read';

require_once __DIR__ . '/includes/site-header.inc.php';
?>
<style><?= akjv_styles_reader() ?></style>
<?= akjv_lang_switcher_html($lang) ?>
<div style="text-align:right;max-width:900px;margin:0 auto 1.5rem;">
	<button id="flipbookToggle" style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(255,215,0,.08));border:1px solid var(--akjv-gold);color:var(--akjv-gold);font-weight:700;font-size:.95rem;cursor:pointer;">📖 Flipbook Mode</button>
</div>
<div id="flipbookContainer" style="display:none;max-width:900px;margin:0 auto 2rem;"></div>
<div id="bibleReaderNormal">
<?php akjv_render_reader($ctx, '/bible'); ?>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/stpageflip@2.0.7/dist/css/pageflip.min.css">
<script src="https://cdn.jsdelivr.net/npm/stpageflip@2.0.7/dist/js/pageflip.min.js"></script>
<script>
const flipbookBtn = document.getElementById('flipbookToggle');
const flipbookContainer = document.getElementById('flipbookContainer');
const bibleReaderNormal = document.getElementById('bibleReaderNormal');
let flipbookActive = false;
let pageFlip = null;

flipbookBtn.addEventListener('click', function() {
	flipbookActive = !flipbookActive;
	if (flipbookActive) {
		bibleReaderNormal.style.display = 'none';
		flipbookContainer.style.display = '';
		flipbookBtn.textContent = '✖️ Exit Flipbook';
		if (!pageFlip) renderFlipbook();
	} else {
		bibleReaderNormal.style.display = '';
		flipbookContainer.style.display = 'none';
		flipbookBtn.textContent = '📖 Flipbook Mode';
	}
});

function renderFlipbook() {
	// Get verses from PHP context (rendered as JS array)
	const verses = <?php echo json_encode(array_map(function($v) {
		return $v['verse'] . '. ' . $v['text_akjv'];
	}, $ctx['verses'])); ?>;
	const book = <?php echo json_encode($ctx['currentBook']['book_name']); ?>;
	const chapter = <?php echo json_encode($ctx['chapter']); ?>;
	// Build pages: 2 verses per page
	const pages = [];
	for (let i = 0; i < verses.length; i += 2) {
		let html = `<div style='padding:2rem 1.2rem;font-size:1.1rem;line-height:1.7;color:#222;background:#fff;height:100%;box-sizing:border-box;'>`;
		html += `<div style='font-weight:700;font-size:1.2rem;color:#bfa100;margin-bottom:.7rem;'>${book} ${chapter}</div>`;
		html += `<div>${verses[i] || ''}<br>${verses[i+1] || ''}</div>`;
		html += `</div>`;
		pages.push(html);
	}
	flipbookContainer.innerHTML = '<div id="pageFlipBook" style="width:700px;height:500px;margin:0 auto;"></div>';
	pageFlip = new St.PageFlip(
		document.getElementById('pageFlipBook'),
		{ width: 350, height: 500, size: 'fixed', minWidth: 315, minHeight: 420, maxWidth: 1200, maxHeight: 1800, showCover: true, mobileScrollSupport: true }
	);
	pageFlip.loadFromHTML(pages.map((html, idx) => {
		const el = document.createElement('div');
		el.className = 'page';
		el.innerHTML = html;
		return el;
	}));
}
</script>
<?php include 'includes/site-footer.inc.php'; ?>
