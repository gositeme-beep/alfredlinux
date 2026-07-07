(function () {
  var API = '/ai-servers/api/all.php';
  var data = { products: {}, presets: {}, currency: 'CAD' };
  var build = { gpu: null, cpu: null, motherboard: null, ram: null, storage: null, psu: null, case: null };
  var compat = window.AIServersCompat;

  function byId(id) { return document.getElementById(id); }
  function qs(s, el) { return (el || document).querySelector(s); }
  function qsAll(s, el) { return (el || document).querySelectorAll(s); }

  function formatPrice(n) {
    return data.currency + ' ' + (n == null ? '0' : Number(n).toLocaleString('en-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  }

  function getTotal() {
    var t = 0;
    ['gpus', 'cpus', 'motherboards', 'ram', 'storage', 'psus', 'cases'].forEach(function (cat) {
      var key = cat === 'gpus' ? 'gpu' : cat === 'cpus' ? 'cpu' : cat === 'motherboards' ? 'motherboard' : cat === 'ram' ? 'ram' : cat === 'storage' ? 'storage' : cat === 'psus' ? 'psu' : 'case';
      var id = build[key];
      if (!id) return;
      var list = data.products[cat];
      if (!list) return;
      var p = list.find(function (x) { return x.id === id; });
      if (p && p.msrp != null) t += p.msrp;
    });
    return t;
  }

  function getSummary() {
    var gpu = build.gpu && compat ? compat.getProduct(data.products, 'gpus', build.gpu) : null;
    var ram = build.ram && compat ? compat.getProduct(data.products, 'ram', build.ram) : null;
    var vram = (gpu && gpu.specs && gpu.specs.vramGb) ? gpu.specs.vramGb : 0;
    var sysRam = (ram && ram.specs && ram.specs.capacityGb) ? ram.specs.capacityGb : 0;
    var kimiReady = vram >= 24 && sysRam >= 512;
    return { vram: vram, ram: sysRam, kimiReady: kimiReady };
  }

  function renderProductCard(p, category, selected) {
    var key = category === 'gpus' ? 'gpu' : category === 'cpus' ? 'cpu' : category === 'motherboards' ? 'motherboard' : category === 'ram' ? 'ram' : category === 'storage' ? 'storage' : category === 'psus' ? 'psu' : 'case';
    var specLine = '';
    if (p.specs) {
      if (p.specs.vramGb) specLine = p.specs.vramGb + 'GB ' + (p.specs.vramType || 'VRAM');
      else if (p.specs.cores) specLine = p.specs.cores + ' cores, ' + (p.specs.tdp || '') + 'W TDP';
      else if (p.specs.socket) specLine = p.specs.socket + ', ' + (p.specs.maxRamGb || '') + 'GB max RAM';
      else if (p.specs.capacityGb) specLine = (p.specs.capacityGb / 1000) + 'TB' + (p.specs.interface ? ' ' + p.specs.interface : '');
      else if (p.specs.wattage) specLine = p.specs.wattage + 'W ' + (p.specs.efficiency || '');
      else if (p.specs.formFactor) specLine = p.specs.formFactor + (p.specs.maxGpuLengthMm ? ', GPU to ' + p.specs.maxGpuLengthMm + 'mm' : '');
    }
    var badges = (p.badges && p.badges.length) ? p.badges.map(function (b) { return '<span class="badge">' + b + '</span>'; }).join('') : '';
    var img = p.imageUrl ? '<img src="' + p.imageUrl + '" alt="">' : '<div class="no-img">No image</div>';
    return '<div class="product-card' + (selected ? ' selected' : '') + '" data-id="' + p.id + '" data-category="' + category + '" data-key="' + key + '">' +
      '<div class="card-img">' + img + '</div>' +
      '<div class="card-body">' +
        '<h4>' + (p.shortName || p.name) + '</h4>' +
        (specLine ? '<p class="spec-line">' + specLine + '</p>' : '') +
        (badges ? '<div class="badges">' + badges + '</div>' : '') +
        '<p class="price">' + formatPrice(p.msrp) + '</p>' +
      '</div></div>';
  }

  function renderSection(key, category, title) {
    var list = data.products[category];
    if (!list) return '';
    var selectedId = build[key];
    var filtered = compat ? compat.getFiltered(data.products, build, category) : list;
    var current = selectedId ? list.find(function (p) { return p.id === selectedId; }) : null;
    var html = '<section class="config-section" data-key="' + key + '" data-category="' + category + '">';
    html += '<h2>' + title + '</h2>';
    html += '<div class="selected-summary">';
    if (current) {
      html += '<div class="current-pick">' + renderProductCard(current, category, true) + '</div>';
    } else {
      html += '<p class="choose-prompt">Choose ' + title.toLowerCase() + '</p>';
    }
    html += '</div>';
    html += '<div class="options-label">Options</div>';
    html += '<div class="product-grid">';
    filtered.filter(function (p) { return p.id !== selectedId; }).forEach(function (p) {
      html += renderProductCard(p, category, false);
    });
    html += '</div></section>';
    return html;
  }

  function refreshUI() {
    var totalEl = byId('build-total');
    if (totalEl) totalEl.textContent = formatPrice(getTotal());
    var summary = getSummary();
    var sumEl = byId('build-summary');
    if (sumEl) {
      sumEl.innerHTML = 'VRAM: ' + summary.vram + 'GB &nbsp;|&nbsp; System RAM: ' + summary.ram + 'GB' +
        (summary.kimiReady ? ' &nbsp;|&nbsp; <strong class="kimi-ready">Kimi K2.5 Ready</strong>' : '');
    }
    var warnEl = byId('build-warnings');
    if (warnEl && compat) {
      var warnings = compat.getWarnings(data.products, build);
      warnEl.innerHTML = warnings.length ? '<p class="warn">' + warnings.join(' ') + '</p>' : '';
      warnEl.style.display = warnings.length ? 'block' : 'none';
    }
    // Re-render sections so "selected" state and filtered options update
    var container = byId('config-sections');
    if (container && data.products.gpus) {
      container.innerHTML =
        renderSection('gpu', 'gpus', 'GPU') +
        renderSection('cpu', 'cpus', 'CPU') +
        renderSection('motherboard', 'motherboards', 'Motherboard') +
        renderSection('ram', 'ram', 'RAM') +
        renderSection('storage', 'storage', 'Storage') +
        renderSection('psu', 'psus', 'Power Supply') +
        renderSection('case', 'cases', 'Case');
      bindCards();
    }
  }

  function bindCards() {
    qsAll('.product-card').forEach(function (card) {
      card.addEventListener('click', function () {
        var key = this.getAttribute('data-key');
        var id = this.getAttribute('data-id');
        if (key && id) {
          build[key] = id;
          refreshUI();
        }
      });
    });
  }

  function applyPreset(name) {
    var preset = data.presets[name];
    if (!preset) return;
    build = { gpu: preset.gpu || null, cpu: preset.cpu || null, motherboard: preset.motherboard || null, ram: preset.ram || null, storage: preset.storage || null, psu: preset.psu || null, case: preset.case || null };
    qsAll('.preset-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-preset') === name);
    });
    refreshUI();
  }

  function bindPresets() {
    qsAll('.preset-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var name = this.getAttribute('data-preset');
        if (name) applyPreset(name);
      });
    });
  }

  function submitQuote() {
    var email = (qs('#quote-email') || {}).value || '';
    var payload = { build: build, contact: { email: email } };
    fetch('/ai-servers/api/quote.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          alert(res.message || 'Quote request sent.');
        } else {
          alert(res.error || 'Request failed.');
        }
      })
      .catch(function () { alert('Request failed.'); });
  }

  function init() {
    fetch(API)
      .then(function (r) { return r.json(); })
      .then(function (res) {
        data.products = res.products || {};
        data.presets = res.presets || {};
        data.currency = res.currency || 'CAD';
        if (Object.keys(data.presets).length) {
          applyPreset('Starter AI');
        } else {
          refreshUI();
        }
        bindPresets();
        byId('quote-submit').addEventListener('click', submitQuote);
    var addToCartBtn = byId('add-to-cart-btn');
    if (addToCartBtn && window.AI_SERVERS_WHMCS_PID) {
      addToCartBtn.addEventListener('click', function () {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ai-servers/add-to-cart.php';
        form.style.display = 'none';
        var input = document.createElement('input');
        input.name = 'build';
        input.value = JSON.stringify(build);
        form.appendChild(input);
        var totalInput = document.createElement('input');
        totalInput.name = 'total';
        totalInput.value = getTotal();
        form.appendChild(totalInput);
        var currencyInput = document.createElement('input');
        currencyInput.name = 'currency';
        currencyInput.value = data.currency || 'CAD';
        form.appendChild(currencyInput);
        document.body.appendChild(form);
        form.submit();
      });
    }
      })
      .catch(function () {
        byId('config-sections').innerHTML = '<p class="error">Could not load products. Check API.</p>';
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.AIServersBuild = build;
  window.AIServersData = data;
  window.AIServersRefresh = refreshUI;
})();
