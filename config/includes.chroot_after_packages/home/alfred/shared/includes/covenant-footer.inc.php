<?php
/**
 * Kingdom Covenant Footer — DAILY VERSE + 4 CORNERS + 8 PILLARS
 * ═══════════════════════════════════════════════════════════════
 * The covenant key for every page across every domain.
 * Drop-in include. Self-contained CSS. Zero dependencies beyond bible-data.php.
 *
 * Usage (any PHP page on any domain):
 *   require_once '/home/gositeme/shared/bible/bible-data.php'; // optional, gracefully degrades
 *   include '/home/gositeme/shared/includes/covenant-footer.inc.php';
 *
 * The verse rotates DAILY (deterministic by date, same verse for all visitors that day).
 * Falls back to a hardcoded covenant verse if AKJV DB unreachable.
 */

// ── Daily verse (deterministic, cached per day) ──────────────
$cv_verse = null;
$cv_ref = '';
if (function_exists('akjv_random_verse')) {
    try { $cv_verse = akjv_random_verse(true); } catch (\Throwable $e) { $cv_verse = null; }
}
if (!$cv_verse) {
    // Covenant fallback — Daniel 5:25-29, the PEREZ verse
    $cv_verse = ['text_akjv' => 'MENE, MENE, TEKEL, UPHARSIN. … And PERES; Thy kingdom is divided, and given to the Medes and Persians.', 'book_name' => 'Daniel', 'chapter' => 5, 'verse' => 25];
}
$cv_ref = ($cv_verse['book_name'] ?? 'Scripture') . ' ' . ($cv_verse['chapter'] ?? '') . ':' . ($cv_verse['verse'] ?? '');
$cv_text = $cv_verse['text_akjv'] ?? '';
?>
<style>
.cov-foot{background:#050a12;border-top:2px solid #ffd700;color:#e8ecf4;font-family:Georgia,'Times New Roman',serif;padding:2.5rem 1rem 1.5rem;margin-top:0;position:relative;z-index:5}
.cov-foot__inner{max-width:1200px;margin:0 auto}
.cov-foot__verse{text-align:center;font-style:italic;font-size:1.05rem;line-height:1.6;color:#ffe44d;max-width:780px;margin:0 auto 1.5rem;padding:1rem 1.5rem;border-left:3px solid #ffd700;border-right:3px solid #ffd700}
.cov-foot__verse-ref{display:block;margin-top:.5rem;font-size:.85rem;color:#a8a8b3;font-style:normal;letter-spacing:.5px}
.cov-foot__title{text-align:center;text-transform:uppercase;letter-spacing:3px;font-size:.75rem;color:#a8a8b3;margin:1.5rem 0 .75rem;font-family:Inter,system-ui,sans-serif}
.cov-foot__corners{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;max-width:900px;margin-left:auto;margin-right:auto}
.cov-foot__corner{display:flex;flex-direction:column;align-items:center;text-align:center;padding:.75rem;border:1px solid rgba(255,215,0,.15);border-radius:6px;text-decoration:none;color:#e8ecf4;background:rgba(12,20,38,.6);transition:all .2s}
.cov-foot__corner:hover{border-color:#ffd700;background:rgba(255,215,0,.05);transform:translateY(-2px)}
.cov-foot__corner-icon{font-size:1.4rem;margin-bottom:.3rem;filter:grayscale(0)}
.cov-foot__corner-name{font-size:.8rem;font-weight:600;letter-spacing:.5px}
.cov-foot__corner-role{font-size:.7rem;color:#a8a8b3;margin-top:.15rem}
.cov-foot__pillars{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-bottom:1.5rem;max-width:900px;margin-left:auto;margin-right:auto}
.cov-foot__pillar{display:block;text-align:center;padding:.5rem .25rem;font-size:.75rem;color:#67e8f9;text-decoration:none;border:1px solid rgba(103,232,249,.15);border-radius:4px;transition:all .15s}
.cov-foot__pillar:hover{color:#fff;border-color:#67e8f9;background:rgba(103,232,249,.06)}
.cov-foot__pillar-icon{display:block;font-size:1rem;margin-bottom:.2rem}
.cov-foot__sig{text-align:center;font-size:.7rem;color:#666;margin-top:1.5rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,.06);letter-spacing:1px}
.cov-foot__sig a{color:#ffd700;text-decoration:none}
@media (max-width:760px){.cov-foot__corners{grid-template-columns:repeat(2,1fr)}.cov-foot__pillars{grid-template-columns:repeat(3,1fr)}}
</style>
<aside class="cov-foot" role="contentinfo" aria-label="Covenant Footer">
  <div class="cov-foot__inner">

    <blockquote class="cov-foot__verse">
      <?php echo htmlspecialchars($cv_text, ENT_QUOTES, 'UTF-8'); ?>
      <cite class="cov-foot__verse-ref">— <?php echo htmlspecialchars($cv_ref, ENT_QUOTES, 'UTF-8'); ?> · <a href="https://gositeme.com/bible" style="color:#ffd700;text-decoration:none">AKJV</a></cite>
    </blockquote>

    <h3 class="cov-foot__title">The Four Corners of the Kingdom</h3>
    <nav class="cov-foot__corners" aria-label="Four Corners">
      <a class="cov-foot__corner" href="https://gositeme.com/" title="The Web — sovereign hosting & domains">
        <span class="cov-foot__corner-icon">🌐</span>
        <span class="cov-foot__corner-name">GoSiteMe</span>
        <span class="cov-foot__corner-role">The Web</span>
      </a>
      <a class="cov-foot__corner" href="https://alfredlinux.com/" title="The OS — sovereign machine">
        <span class="cov-foot__corner-icon">💻</span>
        <span class="cov-foot__corner-name">Alfred Linux</span>
        <span class="cov-foot__corner-role">The OS</span>
      </a>
      <a class="cov-foot__corner" href="https://lavocat.ca/" title="The Law — sovereign justice">
        <span class="cov-foot__corner-icon">⚖️</span>
        <span class="cov-foot__corner-name">L'Avocat</span>
        <span class="cov-foot__corner-role">The Law</span>
      </a>
      <a class="cov-foot__corner" href="https://meta-dome.com/" title="The Worlds — sovereign VR / metaverse">
        <span class="cov-foot__corner-icon">✝️</span>
        <span class="cov-foot__corner-name">MetaDome</span>
        <span class="cov-foot__corner-role">The Worlds</span>
      </a>
    </nav>

    <h3 class="cov-foot__title">The Nine Pillars · Fruits of the Spirit (Gal 5:22-23)</h3>
    <nav class="cov-foot__pillars" aria-label="Nine Pillars">
      <a class="cov-foot__pillar" href="https://gositeme.com/bible"><span class="cov-foot__pillar-icon">🙏</span>Faith</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/alfred.php"><span class="cov-foot__pillar-icon">⚡</span>AI Ecosystem</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/sovereign-domains"><span class="cov-foot__pillar-icon">👑</span>Identity</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/pay/store.php"><span class="cov-foot__pillar-icon">💰</span>Commerce</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/voice-products.php"><span class="cov-foot__pillar-icon">🎬</span>Creative</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/gohostme/"><span class="cov-foot__pillar-icon">🖥️</span>Infrastructure</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/security.php"><span class="cov-foot__pillar-icon">🛡️</span>Security</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/sovereignty"><span class="cov-foot__pillar-icon">🏛️</span>Sovereignty</a>
      <a class="cov-foot__pillar" href="https://gositeme.com/pay/" title="One Pay — every dollar through one altar"><span class="cov-foot__pillar-icon">⛪</span>Payment Sovereignty</a>
    </nav>

    <p class="cov-foot__sig">
      <a href="https://gositeme.com/bible">AUTHORIZED KING JESUS VERSION</a>
      &nbsp;·&nbsp; PEREZ FAMILY EDITION
      &nbsp;·&nbsp; APRIL 8, 2026 A.D.
    </p>
  </div>
</aside>
