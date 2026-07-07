<?php
/**
 * /apps-store — KINGDOM APP STORE
 * ════════════════════════════════════════════════════════════════════
 * Apps · Games · AI Agents · Extensions · Robots · Drones (future)
 * ALL purchases route through GoSiteMe Pay (the 9th Pillar).
 *
 * Reads: marketplace_products + marketplace_categories
 * Pays:  /pay/cart.php (single Kingdom altar)
 */

$pageTitle = 'Apps Store — GoSiteMe · Apps, Games, AI Agents';
$pageDesc  = 'Discover apps, games, AI agents, extensions, robots & drones — every purchase through GoSiteMe Pay.';

include __DIR__ . '/includes/site-header.inc.php';

// ── Pull catalog ──────────────────────────────────────────────────
$cats = [];
$apps = [];
$catalogError = '';
try {
    require_once __DIR__ . '/includes/db-config.inc.php';
    $pdo = getSharedDB();
    $cats = $pdo->query("SELECT slug, name, description, icon, agent_count FROM marketplace_categories ORDER BY sort_order")->fetchAll();
    // Tolerate missing optional columns by selecting only what definitely exists
    $apps = $pdo->query("SELECT * FROM marketplace_products WHERE COALESCE(is_active,1)=1 ORDER BY COALESCE(is_featured,0) DESC, COALESCE(download_count,0) DESC, id ASC")->fetchAll();
} catch (Throwable $e) {
    $catalogError = $e->getMessage();
}

$activeCat = isset($_GET['cat']) ? preg_replace('/[^a-z0-9-]/', '', $_GET['cat']) : '';
?>
<style>
:root{--as-bg:#0a0a14;--as-surface:#12121e;--as-surface-2:#1a1a2e;--as-border:rgba(255,255,255,0.08);--as-accent:#6c5ce7;--as-accent-light:#a29bfe;--as-green:#00b894;--as-gold:#ffd700}
.as-hero{background:linear-gradient(135deg,#0a0a14 0%,#1a1a2e 100%);padding:4rem 1.5rem 2rem;text-align:center;border-bottom:2px solid var(--as-gold)}
.as-hero h1{font-size:2.6rem;margin:0;background:linear-gradient(90deg,#ffd700,#a29bfe);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.as-hero .sub{color:#a8a8b3;margin-top:.6rem;font-size:1.1rem}
.as-hero .pay-badge{display:inline-block;margin-top:1rem;background:rgba(255,215,0,.1);border:1px solid var(--as-gold);color:var(--as-gold);padding:.4rem .9rem;border-radius:20px;font-size:.85rem}
.as-wrap{max-width:1280px;margin:2rem auto;padding:0 1.5rem;color:#e8ecf4;font-family:system-ui,-apple-system,sans-serif}
.as-cats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;margin-bottom:2.5rem}
.as-cat{display:block;padding:1rem;background:var(--as-surface);border:1px solid var(--as-border);border-radius:8px;text-align:center;text-decoration:none;color:#e8ecf4;transition:all .2s}
.as-cat:hover,.as-cat.active{border-color:var(--as-accent);background:var(--as-surface-2);transform:translateY(-2px)}
.as-cat__icon{font-size:1.6rem}
.as-cat__name{font-size:.85rem;font-weight:600;margin-top:.4rem}
.as-cat__count{font-size:.7rem;color:#a8a8b3;margin-top:.15rem}
.as-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem}
.as-card{background:var(--as-surface);border:1px solid var(--as-border);border-radius:10px;padding:1.25rem;display:flex;flex-direction:column;transition:all .2s}
.as-card:hover{border-color:var(--as-accent-light);transform:translateY(-3px);box-shadow:0 8px 24px rgba(108,92,231,.15)}
.as-card__head{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
.as-card__icon{width:42px;height:42px;display:flex;align-items:center;justify-content:center;border-radius:10px;font-size:1.4rem;color:#fff}
.as-card__title{font-weight:700;font-size:1rem;line-height:1.3;margin:0;flex:1}
.as-card__desc{font-size:.85rem;color:#a8a8b3;line-height:1.5;flex:1;min-height:3em}
.as-card__meta{display:flex;justify-content:space-between;font-size:.75rem;color:#a8a8b3;margin:.75rem 0}
.as-card__cta{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--as-border);padding-top:.75rem;margin-top:.5rem}
.as-card__price{font-weight:700;font-size:1.05rem;color:#fff}
.as-card__price.free{color:var(--as-green)}
.as-card__btn{background:var(--as-accent);color:#fff;border:0;padding:.45rem 1rem;border-radius:6px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none}
.as-card__btn:hover{background:var(--as-accent-light)}
.as-card__pay{font-size:.65rem;color:var(--as-gold);text-align:right;margin-top:.4rem}
.as-empty{text-align:center;padding:4rem 1rem;color:#a8a8b3}
.as-featured-badge{position:absolute;top:.5rem;right:.5rem;background:var(--as-gold);color:#000;font-size:.65rem;font-weight:700;padding:.15rem .5rem;border-radius:10px;text-transform:uppercase}
.as-card{position:relative}
@media(max-width:600px){.as-hero h1{font-size:1.8rem}}
</style>

<section class="as-hero">
  <h1>🏪 Kingdom Apps Store</h1>
  <p class="sub">Apps · Games · AI Agents · Extensions · Robots · Drones</p>
  <div class="pay-badge">⛪ All purchases route through <strong>GoSiteMe Pay</strong> — One Kingdom · One Altar</div>
</section>

<div class="as-wrap">

  <?php if (!empty($cats)): ?>
  <nav class="as-cats" aria-label="App Store Categories">
    <a class="as-cat <?= $activeCat==='' ? 'active' : '' ?>" href="/apps-store">
      <div class="as-cat__icon">🌟</div>
      <div class="as-cat__name">All</div>
      <div class="as-cat__count"><?= count($apps) ?> items</div>
    </a>
    <?php foreach ($cats as $c): ?>
      <a class="as-cat <?= $activeCat===$c['slug'] ? 'active' : '' ?>" href="/apps-store?cat=<?= htmlspecialchars($c['slug']) ?>">
        <div class="as-cat__icon"><?php
          $icn = $c['icon'] ?: '📦';
          if (preg_match('/^(fas|far|fab|fa) /', $icn)) {
              echo '<i class="' . htmlspecialchars($icn) . '"></i>';
          } else {
              echo htmlspecialchars($icn);
          }
        ?></div>
        <div class="as-cat__name"><?= htmlspecialchars($c['name']) ?></div>
        <div class="as-cat__count"><?= (int)$c['agent_count'] ?> items</div>
      </a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <?php
  $shown = $activeCat
    ? array_filter($apps, fn($a) => $a['category'] === $activeCat)
    : $apps;
  ?>

  <?php if (empty($shown)): ?>
    <div class="as-empty">
      <h3>No items in this category yet</h3>
      <p>The Kingdom Apps Store is growing daily. Check back soon — robots, drones, and AI agents arriving.</p>
      <p style="margin-top:2rem"><a href="/apps-store" style="color:var(--as-gold);text-decoration:none">← View all apps</a></p>
    </div>
  <?php else: ?>
    <div class="as-grid">
      <?php foreach ($shown as $a):
        $iconColor = $a['icon_color'] ?: '#6c5ce7';
        $icon = $a['icon'] ?: 'fas fa-cube';
        $isFree = ((float)$a['price']) <= 0.001;
      ?>
        <article class="as-card">
          <?php if ($a['is_featured']): ?><span class="as-featured-badge">★ Featured</span><?php endif; ?>
          <div class="as-card__head">
            <div class="as-card__icon" style="background:<?= htmlspecialchars($iconColor) ?>">
              <?php if (str_starts_with($icon, 'fa')): ?>
                <i class="<?= htmlspecialchars($icon) ?>"></i>
              <?php else: ?>
                <?= htmlspecialchars($icon) ?>
              <?php endif; ?>
            </div>
            <h3 class="as-card__title"><?= htmlspecialchars($a['title']) ?></h3>
          </div>
          <p class="as-card__desc"><?= htmlspecialchars(mb_strimwidth($a['description'] ?: 'No description provided.', 0, 140, '…')) ?></p>
          <div class="as-card__meta">
            <span>⭐ <?= number_format((float)$a['rating'], 1) ?> (<?= (int)$a['review_count'] ?>)</span>
            <span>↓ <?= number_format((int)$a['download_count']) ?></span>
          </div>
          <div class="as-card__cta">
            <span class="as-card__price <?= $isFree ? 'free' : '' ?>">
              <?= $isFree ? 'FREE' : '$' . number_format((float)$a['price'], 2) ?>
            </span>
            <a class="as-card__btn" href="/pay/cart.php?action=add_app&app_id=<?= (int)$a['id'] ?>" rel="nofollow">
              <?= $isFree ? 'Install' : 'Buy via Pay' ?>
            </a>
          </div>
          <div class="as-card__pay">⛪ Routed through GoSiteMe Pay</div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div style="text-align:center;margin-top:3rem;padding:2rem;background:var(--as-surface);border-radius:10px;border:1px solid var(--as-border)">
    <h3 style="margin:0 0 .5rem;color:var(--as-gold)">Bringing your app, robot, or AI agent to the Kingdom?</h3>
    <p style="color:#a8a8b3;margin:.5rem 0 1rem">All builders publish through the same altar. One catalog. One Pay. One Kingdom.</p>
    <a href="/pay/account/marketplace.php" style="display:inline-block;background:var(--as-gold);color:#000;padding:.6rem 1.4rem;border-radius:6px;font-weight:700;text-decoration:none">Become a Builder →</a>
  </div>

</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
