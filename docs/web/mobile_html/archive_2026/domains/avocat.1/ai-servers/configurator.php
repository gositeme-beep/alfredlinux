<?php
$page_title = 'Build Your AI Server | Custom AI Workstation';
$page_description = 'Configure a top-of-the-line AI workstation. Kimi K2.5, local LLMs, training & inference ready. NVIDIA RTX PRO, Threadripper, 512GB RAM.';
$page_canonical = 'https://gositeme.com/ai-servers/';
$preload_hero = false;
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/../includes/site-header.inc.php';

$product_id = defined('AI_SERVERS_PRODUCT_ID') && AI_SERVERS_PRODUCT_ID > 0 ? (int) AI_SERVERS_PRODUCT_ID : 0;
?>
<link rel="stylesheet" href="/ai-servers/assets/css/configurator.css">

<main id="main" class="ai-servers-page">
  <section class="ai-config-hero">
    <div class="ai-config-hero-inner">
      <h1 class="ai-config-hero-title">Build Your AI Server</h1>
      <p class="ai-config-hero-tagline">Configure a top-of-the-line AI workstation in under 60 seconds. Kimi K2.5, local LLMs, training &amp; inference ready.</p>
      <div class="presets presets-hero">
        <button type="button" class="preset-btn" data-preset="Starter AI">Starter AI</button>
        <button type="button" class="preset-btn" data-preset="Pro AI">Pro AI</button>
        <button type="button" class="preset-btn" data-preset="Studio">Studio</button>
        <button type="button" class="preset-btn" data-preset="Max">Max</button>
      </div>
    </div>
  </section>

  <div class="main-wrap">
    <div id="config-sections" class="config-sections">
      <p class="loading" style="color: var(--ai-text-muted);">Loading products…</p>
    </div>

    <aside class="build-sidebar">
      <h3>Your Build</h3>
      <div class="build-total-wrap">Total: <span id="build-total">CAD 0.00</span></div>
      <div id="build-summary">VRAM: 0GB | System RAM: 0GB</div>
      <div id="build-warnings" style="display: none;"></div>
      <div class="quote-box">
        <?php if ($product_id): ?>
        <button type="button" id="add-to-cart-btn" class="quote-submit quote-submit-primary">Add to Cart</button>
        <p class="quote-hint">Your build will be added to the cart and attached to the order at checkout.</p>
        <details class="quote-optional" style="margin-top: 1rem;">
          <summary style="cursor: pointer; font-size: 0.9rem; color: var(--ai-text-muted);">Request quote instead</summary>
          <label for="quote-email" style="margin-top: 0.5rem;">Email</label>
          <input type="email" id="quote-email" placeholder="your@gositeme.com" autocomplete="email">
          <button type="button" id="quote-submit" class="quote-submit quote-submit-secondary">Request Quote</button>
        </details>
        <?php else: ?>
        <p class="quote-hint" style="margin-bottom: 0.5rem;">Set <code>AI_SERVERS_PRODUCT_ID</code> in ai-servers/includes/config.php to enable Add to Cart.</p>
        <label for="quote-email">Email for quote</label>
        <input type="email" id="quote-email" placeholder="your@gositeme.com" autocomplete="email">
        <button type="button" id="quote-submit" class="quote-submit">Request Quote</button>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>

<script>
window.AI_SERVERS_PRODUCT_ID = <?php echo (int) $product_id; ?>;
window.AI_SERVERS_WHMCS_PID = <?php echo (int) $product_id; ?>;
</script>
<script src="/ai-servers/assets/js/compatibility.js"></script>
<script src="/ai-servers/assets/js/configurator.js"></script>

<?php require_once __DIR__ . '/../includes/site-footer.inc.php'; ?>
