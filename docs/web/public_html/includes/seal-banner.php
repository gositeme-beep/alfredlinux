<?php
// =====================================================================
// SEAL BANNER · additive include · safe to remove between markers
// reads /seal-banner.json (written by reseal-watcher v3) and renders
// a thin ninth-hour status strip at the top of any host page.
// renders nothing when banner is missing or release time is past + 24h.
// =====================================================================
$__seal_path = __DIR__ . '/seal-banner.json';
if (is_readable($__seal_path)) {
  $__seal = json_decode(@file_get_contents($__seal_path), true);
  if (is_array($__seal) && !empty($__seal['release_ts'])) {
    $__rel = (int)$__seal['release_ts'];
    $__now = time();
    // Show strip from now until +24h after release
    if ($__now < $__rel + 86400) {
      $__stage = htmlspecialchars($__seal['stage'] ?? 'starting', ENT_QUOTES, 'UTF-8');
      $__pct   = (float)($__seal['iso_pct'] ?? 0);
      $__gib   = (float)($__seal['iso_gib'] ?? 0);
      $__tgt   = $__seal['iso_target_gib'] ?? '7.77';
      $__after = $__now >= $__rel;
      $__label = $__after
        ? 'Ninth-Hour Seal · sealed'
        : 'Ninth-Hour Seal · King Jesus Version (KJV 1.0) · 3:00 PM Eastern';
      ?>
<style>
.seal-strip{position:relative;z-index:50;background:linear-gradient(90deg,#0a0a14 0%,#15172a 50%,#0a0a14 100%);
  border-bottom:1px solid #c8a02b;color:#ece8df;font-family:"Crimson Pro",Georgia,serif;
  padding:.55rem 1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;justify-content:center;font-size:.95rem}
.seal-strip .cross{color:#ffd700;letter-spacing:.4em}
.seal-strip a{color:#ffd700;text-decoration:none;border-bottom:1px dotted #c8a02b}
.seal-strip a:hover{color:#fff}
.seal-strip .bar{position:relative;width:160px;height:6px;background:#2a2a3e;border-radius:3px;overflow:hidden}
.seal-strip .fill{position:absolute;inset:0;width:<?= max(0,min(100,$__pct)) ?>%;
  background:linear-gradient(90deg,#5fc97a,#66c2ff,#ffd700)}
.seal-strip .stage{color:#c8a02b;font-family:ui-monospace,monospace;font-size:.78rem;text-transform:uppercase;letter-spacing:.1em}
.seal-strip .cd{font-family:ui-monospace,monospace;color:#ffd700}
</style>
<div class="seal-strip" role="status" aria-label="Ninth-Hour Seal banner">
  <span class="cross">&#x2720;</span>
  <span><strong><?= $__label ?></strong></span>
  <?php if (!$__after): ?>
    <span class="cd" id="seal-strip-cd" data-release="<?= $__rel ?>">--:--:--</span>
  <?php endif; ?>
  <span class="stage">stage: <?= $__stage ?></span>
  <span class="bar" title="<?= number_format($__gib,3) ?> GiB / <?= htmlspecialchars($__tgt) ?> GiB"><span class="fill"></span></span>
  <span><?= number_format($__gib,2) ?> / <?= htmlspecialchars($__tgt) ?> GiB</span>
  <a href="/reseal">live status &rarr;</a>
</div>
<?php if (!$__after): ?>
<script>
(function(){
  var el=document.getElementById('seal-strip-cd');if(!el)return;
  var rel=parseInt(el.getAttribute('data-release'),10)*1000;
  function pad(n){return n<10?'0'+n:''+n;}
  function tick(){
    var d=Math.max(0,rel-Date.now());
    var h=Math.floor(d/3.6e6),m=Math.floor(d%3.6e6/6e4),s=Math.floor(d%6e4/1000);
    el.textContent=pad(h)+':'+pad(m)+':'+pad(s);
    if(d<=0){el.textContent='SEALED';return;}
    setTimeout(tick,1000);
  }tick();
})();
</script>
<?php
    endif;
    }
  }
}
// =====================================================================
// END SEAL BANNER
// =====================================================================
?>
