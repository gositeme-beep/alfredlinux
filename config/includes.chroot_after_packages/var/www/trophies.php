<?php
$page_title = 'NFT Trophies — GoSiteMe';
$page_description = 'Earn, collect, and mint on-chain NFT trophies for your gaming achievements.';
$page_canonical = 'https://root.com/trophies.php';
include __DIR__ . '/includes/site-header.inc.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
?>

<style>
:root {
  --tr-void: #030308;
  --tr-surface: #0c0c1a;
  --tr-surface-alt: #12122a;
  --tr-border: #1e1e3a;
  --tr-gold: #fbbf24;
  --tr-purple: #7c5cfc;
  --tr-green: #00e676;
  --tr-text: #e2e8f0;
  --tr-muted: #94a3b8;
  --tr-common: #9ca3af;
  --tr-uncommon: #22c55e;
  --tr-rare: #3b82f6;
  --tr-epic: #a855f7;
  --tr-legendary: #f59e0b;
  --tr-radius: 14px;
  --tr-glass: rgba(124, 92, 252, .06);
}

.tr-page { background: var(--tr-void); min-height: 100vh; color: var(--tr-text); font-family: 'Inter', system-ui, sans-serif; }

/* ── Hero ── */
.tr-hero { position: relative; padding: 4rem 1.5rem 3rem; text-align: center; overflow: hidden; }
.tr-hero::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at 50% 0%, rgba(124,92,252,.18) 0%, transparent 70%); pointer-events: none; }
.tr-hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; margin: 0 0 .5rem; background: linear-gradient(135deg, var(--tr-gold), var(--tr-purple)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.tr-hero p { color: var(--tr-muted); font-size: 1.1rem; max-width: 560px; margin: 0 auto 2rem; }

.tr-stats-row { display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; }
.tr-stat { background: var(--tr-glass); backdrop-filter: blur(12px); border: 1px solid var(--tr-border); border-radius: var(--tr-radius); padding: 1.2rem 1.6rem; min-width: 150px; text-align: center; }
.tr-stat-val { font-size: 1.8rem; font-weight: 700; color: var(--tr-gold); display: block; }
.tr-stat-label { font-size: .78rem; color: var(--tr-muted); text-transform: uppercase; letter-spacing: .06em; margin-top: .25rem; }

/* ── Tabs ── */
.tr-tabs { display: flex; justify-content: center; gap: .5rem; padding: 1rem 1.5rem; flex-wrap: wrap; position: sticky; top: 0; z-index: 50; background: var(--tr-void); border-bottom: 1px solid var(--tr-border); }
.tr-tab { padding: .6rem 1.4rem; border-radius: 999px; border: 1px solid var(--tr-border); background: transparent; color: var(--tr-muted); cursor: pointer; font-size: .9rem; font-weight: 600; transition: all .2s; }
.tr-tab:hover { border-color: var(--tr-purple); color: var(--tr-text); }
.tr-tab.active { background: var(--tr-purple); border-color: var(--tr-purple); color: #fff; }

/* ── Filters ── */
.tr-filters { display: flex; justify-content: center; gap: .5rem; padding: 1rem 1.5rem; flex-wrap: wrap; }
.tr-chip { padding: .4rem 1rem; border-radius: 999px; border: 1px solid var(--tr-border); background: transparent; color: var(--tr-muted); cursor: pointer; font-size: .8rem; font-weight: 600; transition: all .2s; }
.tr-chip:hover { opacity: .85; }
.tr-chip.active { color: #fff; }
.tr-chip[data-rarity="all"].active { background: var(--tr-purple); border-color: var(--tr-purple); }
.tr-chip[data-rarity="common"].active { background: var(--tr-common); border-color: var(--tr-common); }
.tr-chip[data-rarity="uncommon"].active { background: var(--tr-uncommon); border-color: var(--tr-uncommon); }
.tr-chip[data-rarity="rare"].active { background: var(--tr-rare); border-color: var(--tr-rare); }
.tr-chip[data-rarity="epic"].active { background: var(--tr-epic); border-color: var(--tr-epic); }
.tr-chip[data-rarity="legendary"].active { background: var(--tr-legendary); border-color: var(--tr-legendary); }

/* ── Grid ── */
.tr-content { max-width: 1200px; margin: 0 auto; padding: 1rem 1.5rem 4rem; }
.tr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
.tr-empty { text-align: center; padding: 4rem 1rem; color: var(--tr-muted); }
.tr-empty-icon { font-size: 3rem; margin-bottom: 1rem; }

/* ── Trophy Card ── */
.tr-card { background: var(--tr-glass); backdrop-filter: blur(12px); border: 1px solid var(--tr-border); border-radius: var(--tr-radius); padding: 1.5rem; cursor: pointer; transition: transform .2s, border-color .25s, box-shadow .25s; position: relative; overflow: hidden; }
.tr-card:hover { transform: translateY(-4px); border-color: var(--tr-purple); box-shadow: 0 8px 32px rgba(124,92,252,.15); }
.tr-card-icon { font-size: 3rem; margin-bottom: .75rem; display: block; }
.tr-card-name { font-size: 1.1rem; font-weight: 700; margin-bottom: .4rem; }
.tr-card-desc { font-size: .82rem; color: var(--tr-muted); margin-bottom: .75rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.tr-badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.tr-badge-common { background: rgba(156,163,175,.15); color: var(--tr-common); }
.tr-badge-uncommon { background: rgba(34,197,94,.12); color: var(--tr-uncommon); }
.tr-badge-rare { background: rgba(59,130,246,.12); color: var(--tr-rare); }
.tr-badge-epic { background: rgba(168,85,247,.12); color: var(--tr-epic); }
.tr-badge-legendary { background: rgba(245,158,11,.12); color: var(--tr-legendary); }

.tr-card-cat { display: inline-block; padding: .18rem .55rem; border-radius: 6px; font-size: .68rem; color: var(--tr-muted); border: 1px solid var(--tr-border); margin-left: .4rem; text-transform: capitalize; }

.tr-card-meta { display: flex; justify-content: space-between; align-items: center; margin-top: .75rem; padding-top: .75rem; border-top: 1px solid var(--tr-border); font-size: .78rem; }
.tr-card-reward { color: var(--tr-green); font-weight: 600; }
.tr-card-mint-cost { color: var(--tr-gold); font-weight: 600; }

.tr-progress-wrap { margin-top: .6rem; }
.tr-progress-label { font-size: .72rem; color: var(--tr-muted); margin-bottom: .3rem; display: flex; justify-content: space-between; }
.tr-progress-bar { height: 6px; border-radius: 3px; background: var(--tr-border); overflow: hidden; }
.tr-progress-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--tr-purple), var(--tr-gold)); transition: width .4s ease; }

.tr-card-minted { position: absolute; top: 12px; right: 12px; font-size: .68rem; padding: .2rem .5rem; border-radius: 6px; background: rgba(0,230,118,.12); color: var(--tr-green); font-weight: 700; }
.tr-card-toggle { position: absolute; top: 12px; left: 12px; background: none; border: none; cursor: pointer; font-size: 1.1rem; opacity: .5; transition: opacity .2s; }
.tr-card-toggle:hover { opacity: 1; }

/* ── Modal ── */
.tr-modal-overlay { position: fixed; inset: 0; background: rgba(3,3,8,.85); backdrop-filter: blur(6px); z-index: 1000; display: none; align-items: center; justify-content: center; padding: 1rem; }
.tr-modal-overlay.open { display: flex; }
.tr-modal { background: var(--tr-surface); border: 1px solid var(--tr-border); border-radius: var(--tr-radius); max-width: 520px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2rem; position: relative; animation: trSlideUp .25s ease; }
@keyframes trSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.tr-modal-close { position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--tr-muted); font-size: 1.4rem; cursor: pointer; }
.tr-modal-icon { font-size: 4rem; text-align: center; margin-bottom: 1rem; }
.tr-modal h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .3rem; }
.tr-modal-rarity { margin-bottom: .75rem; }
.tr-modal-desc { color: var(--tr-muted); font-size: .92rem; line-height: 1.55; margin-bottom: 1.25rem; }

.tr-modal-info { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1.25rem; }
.tr-modal-info-item { background: var(--tr-surface-alt); border-radius: 10px; padding: .75rem; }
.tr-modal-info-label { font-size: .7rem; color: var(--tr-muted); text-transform: uppercase; letter-spacing: .05em; }
.tr-modal-info-val { font-size: 1.05rem; font-weight: 700; margin-top: .15rem; }

.tr-modal-earn { background: var(--tr-surface-alt); border-radius: 10px; padding: 1rem; margin-bottom: 1.25rem; }
.tr-modal-earn h4 { font-size: .82rem; text-transform: uppercase; letter-spacing: .05em; color: var(--tr-muted); margin: 0 0 .4rem; }
.tr-modal-earn p { margin: 0; font-size: .92rem; }

.tr-modal-actions { display: flex; gap: .75rem; }
.tr-btn { padding: .7rem 1.4rem; border-radius: 10px; border: none; font-weight: 700; font-size: .9rem; cursor: pointer; transition: all .2s; flex: 1; }
.tr-btn-mint { background: linear-gradient(135deg, var(--tr-gold), #f59e0b); color: #000; }
.tr-btn-mint:hover { box-shadow: 0 4px 20px rgba(251,191,36,.3); }
.tr-btn-mint:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; }
.tr-btn-secondary { background: var(--tr-surface-alt); color: var(--tr-text); border: 1px solid var(--tr-border); }
.tr-btn-secondary:hover { border-color: var(--tr-purple); }

/* ── Leaderboard ── */
.tr-leaderboard { max-width: 700px; margin: 0 auto; }
.tr-lb-row { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--tr-glass); border: 1px solid var(--tr-border); border-radius: var(--tr-radius); margin-bottom: .6rem; }
.tr-lb-rank { font-size: 1.3rem; font-weight: 800; min-width: 2.5rem; text-align: center; }
.tr-lb-rank-1 { color: var(--tr-gold); }
.tr-lb-rank-2 { color: #c0c0c0; }
.tr-lb-rank-3 { color: #cd7f32; }
.tr-lb-name { flex: 1; font-weight: 600; }
.tr-lb-count { color: var(--tr-purple); font-weight: 700; font-size: 1.1rem; }

/* ── Loading ── */
.tr-spinner { display: flex; justify-content: center; padding: 3rem; }
.tr-spinner::after { content: ''; width: 36px; height: 36px; border: 3px solid var(--tr-border); border-top-color: var(--tr-purple); border-radius: 50%; animation: trSpin .7s linear infinite; }
@keyframes trSpin { to { transform: rotate(360deg); } }

/* ── Pagination ── */
.tr-pager { display: flex; justify-content: center; gap: .5rem; margin-top: 2rem; }
.tr-pager-btn { padding: .5rem 1rem; border-radius: 8px; border: 1px solid var(--tr-border); background: transparent; color: var(--tr-muted); cursor: pointer; font-size: .85rem; font-weight: 600; transition: all .2s; }
.tr-pager-btn:hover { border-color: var(--tr-purple); color: var(--tr-text); }
.tr-pager-btn.active { background: var(--tr-purple); border-color: var(--tr-purple); color: #fff; }
.tr-pager-btn:disabled { opacity: .35; cursor: not-allowed; }

/* ── Toast ── */
.tr-toast { position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%); background: var(--tr-surface); border: 1px solid var(--tr-border); border-radius: 10px; padding: .8rem 1.4rem; color: var(--tr-text); font-size: .88rem; font-weight: 600; z-index: 2000; box-shadow: 0 8px 32px rgba(0,0,0,.4); opacity: 0; transition: opacity .3s; pointer-events: none; }
.tr-toast.show { opacity: 1; }
.tr-toast.error { border-color: #ef4444; }
.tr-toast.success { border-color: var(--tr-green); }

/* ── Responsive ── */
@media (max-width: 640px) {
  .tr-hero { padding: 2.5rem 1rem 2rem; }
  .tr-stats-row { gap: .75rem; }
  .tr-stat { min-width: 130px; padding: .9rem 1rem; }
  .tr-grid { grid-template-columns: 1fr; }
  .tr-modal { padding: 1.5rem; }
  .tr-modal-info { grid-template-columns: 1fr; }
  .tr-modal-actions { flex-direction: column; }
}
</style>

<div class="tr-page">

  <!-- Hero -->
  <section class="tr-hero">
    <h1>🏆 NFT Trophies</h1>
    <p>Earn achievements across the GoSiteMe ecosystem. Mint them on-chain as permanent NFTs to prove your legacy.</p>
    <div class="tr-stats-row" id="trHeroStats">
      <div class="tr-stat"><span class="tr-stat-val" id="trStatTotal">—</span><span class="tr-stat-label">Total Trophies</span></div>
      <div class="tr-stat"><span class="tr-stat-val" id="trStatEarned">—</span><span class="tr-stat-label">Earned by Community</span></div>
      <div class="tr-stat"><span class="tr-stat-val" id="trStatMinted">—</span><span class="tr-stat-label">Minted On-Chain</span></div>
      <div class="tr-stat"><span class="tr-stat-val" id="trStatRarest">—</span><span class="tr-stat-label">Rarest Trophy</span></div>
    </div>
  </section>

  <!-- Tabs -->
  <nav class="tr-tabs">
    <button class="tr-tab active" data-tab="gallery">Gallery</button>
    <button class="tr-tab" data-tab="my-trophies">My Trophies</button>
    <button class="tr-tab" data-tab="available">Available</button>
    <button class="tr-tab" data-tab="leaderboard">Leaderboard</button>
  </nav>

  <!-- Rarity Filters -->
  <div class="tr-filters" id="trFilters">
    <button class="tr-chip active" data-rarity="all">All</button>
    <button class="tr-chip" data-rarity="common">Common</button>
    <button class="tr-chip" data-rarity="uncommon">Uncommon</button>
    <button class="tr-chip" data-rarity="rare">Rare</button>
    <button class="tr-chip" data-rarity="epic">Epic</button>
    <button class="tr-chip" data-rarity="legendary">Legendary</button>
  </div>

  <!-- Content -->
  <div class="tr-content">
    <div id="trPanel" class="tr-grid"></div>
    <div id="trPager" class="tr-pager" style="display:none"></div>
  </div>

  <!-- Detail Modal -->
  <div class="tr-modal-overlay" id="trModal">
    <div class="tr-modal">
      <button class="tr-modal-close" id="trModalClose">&times;</button>
      <div class="tr-modal-icon" id="trModalIcon"></div>
      <h2 id="trModalName"></h2>
      <div class="tr-modal-rarity" id="trModalRarity"></div>
      <p class="tr-modal-desc" id="trModalDesc"></p>
      <div class="tr-modal-info">
        <div class="tr-modal-info-item"><span class="tr-modal-info-label">Category</span><div class="tr-modal-info-val" id="trModalCat"></div></div>
        <div class="tr-modal-info-item"><span class="tr-modal-info-label">Total Owners</span><div class="tr-modal-info-val" id="trModalOwners"></div></div>
        <div class="tr-modal-info-item"><span class="tr-modal-info-label">GSM Reward</span><div class="tr-modal-info-val" style="color:var(--tr-green)" id="trModalReward"></div></div>
        <div class="tr-modal-info-item"><span class="tr-modal-info-label">Mint Cost</span><div class="tr-modal-info-val" style="color:var(--tr-gold)" id="trModalMintCost"></div></div>
        <div class="tr-modal-info-item"><span class="tr-modal-info-label">Total Minted</span><div class="tr-modal-info-val" id="trModalMinted"></div></div>
        <div class="tr-modal-info-item"><span class="tr-modal-info-label">Max Supply</span><div class="tr-modal-info-val" id="trModalSupply"></div></div>
      </div>
      <div class="tr-modal-earn">
        <h4>How to Earn</h4>
        <p id="trModalEarn"></p>
      </div>
      <div id="trModalProgress" style="display:none">
        <div class="tr-progress-wrap" style="margin-bottom:1.25rem">
          <div class="tr-progress-label"><span id="trModalProgLabel">Progress</span><span id="trModalProgPct"></span></div>
          <div class="tr-progress-bar"><div class="tr-progress-fill" id="trModalProgBar"></div></div>
        </div>
      </div>
      <div class="tr-modal-actions" id="trModalActions"></div>
    </div>
  </div>

  <!-- Toast -->
  <div class="tr-toast" id="trToast"></div>
</div>

<script>
(function() {
  'use strict';
  const API = '/api/nft-trophies.php';
  const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

  let currentTab = 'gallery';
  let currentRarity = 'all';
  let currentPage = 1;
  const perPage = 18;
  let detailCache = {};

  /* ── Helpers ── */
  function qs(s, p) { return (p || document).querySelector(s); }
  function qsa(s, p) { return (p || document).querySelectorAll(s); }

  async function api(action, params = {}, method = 'GET') {
    const url = new URL(API, location.origin);
    url.searchParams.set('action', action);
    const opts = { credentials: 'same-origin' };
    if (method === 'GET') {
      Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    } else {
      opts.method = 'POST';
      opts.headers = { 'Content-Type': 'application/json' };
      opts.body = JSON.stringify(params);
    }
    const r = await fetch(url, opts);
    if (!r.ok) throw new Error('API error ' + r.status);
    return r.json();
  }

  function toast(msg, type = '') {
    const el = qs('#trToast');
    el.textContent = msg;
    el.className = 'tr-toast show' + (type ? ' ' + type : '');
    setTimeout(() => el.classList.remove('show'), 3000);
  }

  function rarityBadge(rarity) {
    return '<span class="tr-badge tr-badge-' + rarity + '">' + rarity + '</span>';
  }

  function formatNum(n) {
    if (n == null) return '—';
    return Number(n).toLocaleString();
  }

  function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  /* ── Stats ── */
  async function loadStats() {
    try {
      const d = await api('trophy-stats');
      if (d.success) {
        qs('#trStatTotal').textContent = formatNum(d.data.total_trophies);
        qs('#trStatEarned').textContent = formatNum(d.data.total_earned);
        qs('#trStatMinted').textContent = formatNum(d.data.total_minted);
        qs('#trStatRarest').textContent = escHtml(d.data.rarest_trophy || '—');
      }
    } catch (_) {}
  }

  /* ── Card Rendering ── */
  function renderCard(t, context) {
    const card = document.createElement('div');
    card.className = 'tr-card';
    card.dataset.id = t.id;

    let inner = '<span class="tr-card-icon">' + escHtml(t.icon || '🏆') + '</span>';
    inner += '<div class="tr-card-name">' + escHtml(t.name) + '</div>';
    inner += '<div class="tr-card-desc">' + escHtml(t.description) + '</div>';
    inner += rarityBadge(t.rarity) + '<span class="tr-card-cat">' + escHtml(t.category) + '</span>';

    if (t.minted_on_chain) {
      inner += '<div class="tr-card-minted">✦ On-Chain</div>';
    }
    if (context === 'my-trophies' && t.display_on_profile !== undefined) {
      inner += '<button class="tr-card-toggle" data-trophy="' + t.id + '" title="Toggle profile display">'
        + (t.display_on_profile ? '👁️' : '👁️‍🗨️') + '</button>';
    }

    inner += '<div class="tr-card-meta">';
    inner += '<span class="tr-card-reward">+' + formatNum(t.gsm_reward) + ' GSM</span>';
    inner += '<span class="tr-card-mint-cost">Mint: ' + formatNum(t.mint_cost_gsm) + ' GSM</span>';
    inner += '</div>';

    if (t.progress !== undefined && t.progress !== null && t.requirement_value) {
      const pct = Math.min(100, Math.round((t.progress / t.requirement_value) * 100));
      inner += '<div class="tr-progress-wrap">';
      inner += '<div class="tr-progress-label"><span>' + formatNum(t.progress) + ' / ' + formatNum(t.requirement_value) + '</span><span>' + pct + '%</span></div>';
      inner += '<div class="tr-progress-bar"><div class="tr-progress-fill" style="width:' + pct + '%"></div></div>';
      inner += '</div>';
    }

    card.innerHTML = inner;
    card.addEventListener('click', (e) => {
      if (e.target.closest('.tr-card-toggle')) return;
      openDetail(t.id);
    });
    return card;
  }

  /* ── Tab Content ── */
  async function loadTab(tab, page) {
    currentTab = tab;
    currentPage = page || 1;
    const panel = qs('#trPanel');
    const pager = qs('#trPager');
    const filters = qs('#trFilters');
    panel.innerHTML = '<div class="tr-spinner"></div>';
    pager.style.display = 'none';

    filters.style.display = (tab === 'leaderboard') ? 'none' : 'flex';

    if (tab === 'leaderboard') {
      panel.className = 'tr-leaderboard';
      await loadLeaderboard(panel);
      return;
    }
    panel.className = 'tr-grid';

    try {
      let data;
      const params = { page: currentPage, limit: perPage };
      if (currentRarity !== 'all') params.rarity = currentRarity;

      if (tab === 'gallery') {
        data = await api('trophy-gallery', params);
      } else if (tab === 'my-trophies') {
        if (!isLoggedIn) { showAuthPrompt(panel); return; }
        data = await api('my-trophies', params);
      } else if (tab === 'available') {
        if (!isLoggedIn) { showAuthPrompt(panel); return; }
        data = await api('available-trophies', params);
      }

      if (!data || !data.success) {
        panel.innerHTML = '<div class="tr-empty"><div class="tr-empty-icon">😕</div>Failed to load trophies.</div>';
        return;
      }

      const trophies = data.data.trophies || data.data || [];
      if (!trophies.length) {
        const msgs = {
          'gallery': 'No trophies found.',
          'my-trophies': 'You haven\\'t earned any trophies yet. Start playing!',
          'available': 'No available trophies match this filter.'
        };
        panel.innerHTML = '<div class="tr-empty"><div class="tr-empty-icon">🏅</div>' + (msgs[tab] || 'Nothing here.') + '</div>';
        return;
      }

      panel.innerHTML = '';
      trophies.forEach(t => panel.appendChild(renderCard(t, tab)));

      const total = data.data.total || trophies.length;
      const pages = Math.ceil(total / perPage);
      if (pages > 1) renderPager(pages);

    } catch (err) {
      panel.innerHTML = '<div class="tr-empty"><div class="tr-empty-icon">⚠️</div>Error loading trophies.</div>';
    }
  }

  function showAuthPrompt(panel) {
    panel.innerHTML = '<div class="tr-empty"><div class="tr-empty-icon">🔒</div>Sign in to view your trophies.<br><a href="/login.php" style="color:var(--tr-purple);text-decoration:underline;margin-top:.5rem;display:inline-block">Sign In</a></div>';
  }

  /* ── Leaderboard ── */
  async function loadLeaderboard(panel) {
    try {
      const d = await api('trophy-stats');
      if (!d.success || !d.data.leaderboard || !d.data.leaderboard.length) {
        panel.innerHTML = '<div class="tr-empty"><div class="tr-empty-icon">📊</div>Leaderboard data coming soon.</div>';
        return;
      }
      panel.innerHTML = '';
      d.data.leaderboard.forEach((u, i) => {
        const row = document.createElement('div');
        row.className = 'tr-lb-row';
        const rank = i + 1;
        const rc = rank <= 3 ? ' tr-lb-rank-' + rank : '';
        row.innerHTML = '<div class="tr-lb-rank' + rc + '">#' + rank + '</div>'
          + '<div class="tr-lb-name">' + escHtml(u.display_name || u.username || 'User') + '</div>'
          + '<div class="tr-lb-count">' + formatNum(u.trophy_count) + ' 🏆</div>';
        panel.appendChild(row);
      });
    } catch (_) {
      panel.innerHTML = '<div class="tr-empty"><div class="tr-empty-icon">⚠️</div>Could not load leaderboard.</div>';
    }
  }

  /* ── Pager ── */
  function renderPager(pages) {
    const pager = qs('#trPager');
    pager.innerHTML = '';
    pager.style.display = 'flex';

    const prev = document.createElement('button');
    prev.className = 'tr-pager-btn';
    prev.textContent = '← Prev';
    prev.disabled = currentPage <= 1;
    prev.addEventListener('click', () => loadTab(currentTab, currentPage - 1));
    pager.appendChild(prev);

    const maxBtns = 7;
    let start = Math.max(1, currentPage - 3);
    let end = Math.min(pages, start + maxBtns - 1);
    if (end - start < maxBtns - 1) start = Math.max(1, end - maxBtns + 1);

    for (let i = start; i <= end; i++) {
      const btn = document.createElement('button');
      btn.className = 'tr-pager-btn' + (i === currentPage ? ' active' : '');
      btn.textContent = i;
      btn.addEventListener('click', () => loadTab(currentTab, i));
      pager.appendChild(btn);
    }

    const next = document.createElement('button');
    next.className = 'tr-pager-btn';
    next.textContent = 'Next →';
    next.disabled = currentPage >= pages;
    next.addEventListener('click', () => loadTab(currentTab, currentPage + 1));
    pager.appendChild(next);
  }

  /* ── Detail Modal ── */
  async function openDetail(id) {
    const modal = qs('#trModal');
    modal.classList.add('open');
    qs('#trModalIcon').textContent = '⏳';
    qs('#trModalName').textContent = 'Loading…';
    qs('#trModalDesc').textContent = '';
    qs('#trModalActions').innerHTML = '';
    qs('#trModalProgress').style.display = 'none';

    try {
      let t;
      if (detailCache[id]) {
        t = detailCache[id];
      } else {
        const d = await api('trophy-detail', { trophy_id: id });
        if (!d.success) { toast('Trophy not found', 'error'); closeModal(); return; }
        t = d.data;
        detailCache[id] = t;
      }

      qs('#trModalIcon').textContent = t.icon || '🏆';
      qs('#trModalName').textContent = t.name;
      qs('#trModalRarity').innerHTML = rarityBadge(t.rarity);
      qs('#trModalDesc').textContent = t.description;
      qs('#trModalCat').textContent = t.category;
      qs('#trModalOwners').textContent = formatNum(t.total_owners || 0);
      qs('#trModalReward').textContent = '+' + formatNum(t.gsm_reward) + ' GSM';
      qs('#trModalMintCost').textContent = formatNum(t.mint_cost_gsm) + ' GSM';
      qs('#trModalMinted').textContent = formatNum(t.total_minted);
      qs('#trModalSupply').textContent = t.max_supply ? formatNum(t.max_supply) : '∞';
      qs('#trModalEarn').textContent = t.requirement_description || t.requirement_type + ': ' + formatNum(t.requirement_value);

      if (t.progress !== undefined && t.progress !== null && t.requirement_value) {
        const pct = Math.min(100, Math.round((t.progress / t.requirement_value) * 100));
        qs('#trModalProgress').style.display = 'block';
        qs('#trModalProgLabel').textContent = formatNum(t.progress) + ' / ' + formatNum(t.requirement_value);
        qs('#trModalProgPct').textContent = pct + '%';
        qs('#trModalProgBar').style.width = pct + '%';
      } else {
        qs('#trModalProgress').style.display = 'none';
      }

      let actions = '';
      if (isLoggedIn && t.earned && !t.minted_on_chain) {
        actions += '<button class="tr-btn tr-btn-mint" id="trMintBtn" data-id="' + t.id + '">Mint as NFT (' + formatNum(t.mint_cost_gsm) + ' GSM)</button>';
      } else if (isLoggedIn && t.minted_on_chain) {
        actions += '<button class="tr-btn tr-btn-secondary" disabled>✦ Already Minted</button>';
      }
      if (isLoggedIn && t.earned) {
        const togLabel = t.display_on_profile ? 'Hide from Profile' : 'Show on Profile';
        actions += '<button class="tr-btn tr-btn-secondary" id="trToggleBtn" data-id="' + t.id + '">' + togLabel + '</button>';
      }
      if (!actions && !isLoggedIn) {
        actions = '<a href="/login.php" class="tr-btn tr-btn-secondary" style="text-align:center;text-decoration:none">Sign in to mint</a>';
      }
      qs('#trModalActions').innerHTML = actions || '';

      const mintBtn = qs('#trMintBtn');
      if (mintBtn) mintBtn.addEventListener('click', () => mintTrophy(t.id, mintBtn));

      const togBtn = qs('#trToggleBtn');
      if (togBtn) togBtn.addEventListener('click', () => toggleDisplay(t.id, togBtn));

    } catch (err) {
      toast('Failed to load trophy details', 'error');
      closeModal();
    }
  }

  function closeModal() { qs('#trModal').classList.remove('open'); }
  qs('#trModalClose').addEventListener('click', closeModal);
  qs('#trModal').addEventListener('click', (e) => { if (e.target === qs('#trModal')) closeModal(); });

  /* ── Mint ── */
  async function mintTrophy(id, btn) {
    btn.disabled = true;
    btn.textContent = 'Minting…';
    try {
      const d = await api('mint-trophy', { trophy_id: id }, 'POST');
      if (d.success) {
        toast('Trophy minted on-chain!', 'success');
        delete detailCache[id];
        closeModal();
        loadTab(currentTab, currentPage);
      } else {
        toast(d.message || 'Mint failed', 'error');
        btn.disabled = false;
        btn.textContent = 'Mint as NFT';
      }
    } catch (_) {
      toast('Mint request failed', 'error');
      btn.disabled = false;
      btn.textContent = 'Mint as NFT';
    }
  }

  /* ── Toggle Display ── */
  async function toggleDisplay(id, btn) {
    try {
      const d = await api('toggle-display', { trophy_id: id }, 'POST');
      if (d.success) {
        toast(d.data.display_on_profile ? 'Showing on profile' : 'Hidden from profile', 'success');
        delete detailCache[id];
        closeModal();
        loadTab(currentTab, currentPage);
      } else {
        toast(d.message || 'Toggle failed', 'error');
      }
    } catch (_) {
      toast('Toggle request failed', 'error');
    }
  }

  /* ── Toggle from Card ── */
  document.addEventListener('click', (e) => {
    const togBtn = e.target.closest('.tr-card-toggle');
    if (!togBtn) return;
    e.stopPropagation();
    toggleDisplay(togBtn.dataset.trophy, togBtn);
  });

  /* ── Tab Switching ── */
  qsa('.tr-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      qsa('.tr-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      loadTab(tab.dataset.tab, 1);
    });
  });

  /* ── Rarity Filter ── */
  qsa('.tr-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      qsa('.tr-chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      currentRarity = chip.dataset.rarity;
      loadTab(currentTab, 1);
    });
  });

  /* ── Keyboard ── */
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  /* ── Init ── */
  loadStats();
  loadTab('gallery', 1);
})();
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
