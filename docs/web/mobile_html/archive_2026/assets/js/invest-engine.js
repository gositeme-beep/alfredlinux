(function() {
  'use strict';

  /* ── Smooth Scroll ── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ── Fetch Live Metrics from API ── */
  async function fetchMetrics() {
    try {
      const res = await fetch('/api/investor.php?action=metrics');
      if (!res.ok) return;
      const data = await res.json();
      if (data && data.success !== false) {
        // Update hero counters
        if (data.total_raised !== undefined) animateValue('inv-raised', 0, data.total_raised, 2500);
        if (data.investor_count !== undefined) animateValue('inv-investor-count', 0, data.investor_count, 2000);
        if (data.round_progress !== undefined) animateValue('inv-momentum', 0, data.round_progress, 2000);
        if (data.investor_count !== undefined) {
          const fc = document.getElementById('inv-footer-count');
          if (fc) fc.textContent = data.investor_count > 0 ? data.investor_count : 'early';
        }
        // Update traction metrics
        if (data.tools) updateMetric('met-tools', data.tools + '+');
        if (data.voice_tools) updateMetric('met-voice', data.voice_tools + '+');
        if (data.pages) updateMetric('met-pages', data.pages);
        if (data.articles) updateMetric('met-articles', data.articles);
        if (data.api_endpoints) updateMetric('met-api', data.api_endpoints + '+');
        if (data.sdks) updateMetric('met-sdks', data.sdks);
        if (data.codebase_files) updateMetric('met-files', Number(data.codebase_files).toLocaleString());
        if (data.verticals) updateMetric('met-verticals', data.verticals);
      }
    } catch (err) {
      console.log('Metrics fetch unavailable:', err.message);
    }
  }

  function updateMetric(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function animateValue(id, start, end, duration) {
    const el = document.getElementById(id);
    if (!el) return;
    const startTime = performance.now();
    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
      const current = Math.floor(start + (end - start) * eased);
      el.textContent = current.toLocaleString();
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  }

  fetchMetrics();

  /* ── Counter Animation on Scroll ── */
  const counters = document.querySelectorAll('.inv-count');
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseInt(el.getAttribute('data-target'), 10);
        if (isNaN(target)) return;
        animateCounter(el, 0, target, 2000);
        counterObserver.unobserve(el);
      }
    });
  }, { threshold: 0.3 });

  counters.forEach(c => counterObserver.observe(c));

  function animateCounter(el, start, end, duration) {
    const startTime = performance.now();
    function tick(now) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(start + (end - start) * eased).toLocaleString();
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  /* ── Scroll Animations ── */
  const animateEls = document.querySelectorAll('.inv-animate');
  const animObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        animObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  animateEls.forEach(el => animObserver.observe(el));

  /* ── Progress Bar Animation ── */
  const progressBars = document.querySelectorAll('.inv-progress-fill');
  const progressObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const width = entry.target.getAttribute('data-width');
        if (width) entry.target.style.width = width + '%';
        progressObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  progressBars.forEach(b => progressObserver.observe(b));

  /* ── Chart Bar Animation ── */
  const chartBars = document.querySelectorAll('.inv-chart-bar-fill');
  const chartObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const width = entry.target.getAttribute('data-width');
        if (width) entry.target.style.width = width + '%';
        chartObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });

  chartBars.forEach(b => chartObserver.observe(b));

})();

/* ── FAQ Accordion ── */
function toggleFaq(el) {
  const item = el.closest('.inv-faq-item');
  if (!item) return;
  const isOpen = item.classList.contains('open');
  // Close all
  document.querySelectorAll('.inv-faq-item.open').forEach(i => i.classList.remove('open'));
  // Toggle
  if (!isOpen) item.classList.add('open');
}

/* ── Tier Selection ── */
function selectTier(tier, amountRange) {
  // Scroll to form
  const form = document.getElementById('inv-contact');
  if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });

  // Set tier select
  setTimeout(() => {
    const tierSelect = document.getElementById('inv-tier');
    if (tierSelect) {
      tierSelect.value = tier;
      updateROIPreview();
    }
    // Set default amount
    const amountInput = document.getElementById('inv-amount');
    if (amountInput && !amountInput.value) {
      const defaults = { seed: '$1,000', growth: '$5,000', strategic: '$25,000' };
      amountInput.value = defaults[tier] || '';
    }
    // Highlight selected tier card
    document.querySelectorAll('.inv-tier-card').forEach(c => c.style.outline = 'none');
    const selectedCard = document.querySelector('.inv-tier-card[data-tier="' + tier + '"]');
    if (selectedCard) selectedCard.style.outline = '2px solid var(--inv-green)';
  }, 600);
}

/* ── ROI Preview ── */
function updateROIPreview() {
  const tier = document.getElementById('inv-tier').value;
  const box = document.getElementById('inv-roi-box');
  const value = document.getElementById('inv-roi-value');
  if (!tier) {
    box.classList.remove('visible');
    return;
  }
  const rois = {
    seed: '5x – 15x return',
    growth: '8x – 25x return',
    strategic: '12x – 40x return'
  };
  value.textContent = rois[tier] || '—';
  box.classList.add('visible');
}

/* ── Form Submission — Stripe Checkout ── */
async function submitInvestorForm(e) {
  e.preventDefault();
  const btn = document.getElementById('inv-submit-btn');
  const msgEl = document.getElementById('inv-form-msg');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Creating secure checkout...';
  msgEl.className = 'inv-form-message';
  msgEl.style.display = 'none';

  const formData = new FormData(document.getElementById('inv-form'));
  const data = {};
  formData.forEach((v, k) => data[k] = v);

  // Clean amount (remove $ and commas)
  data.amount = String(data.amount).replace(/[$,\s]/g, '');

  try {
    const res = await fetch('/api/investor.php?action=create_checkout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' },
      credentials: 'same-origin',
      body: JSON.stringify(data)
    });
    const result = await res.json();

    if (result.success && result.checkout_url) {
      msgEl.className = 'inv-form-message success';
      msgEl.innerHTML = '🔒 <strong>Redirecting to secure Stripe checkout...</strong> Reference: ' + result.ref_code;
      msgEl.style.display = 'block';
      // Redirect to Stripe Checkout
      setTimeout(() => { window.location.href = result.checkout_url; }, 800);
      return false;
    } else {
      throw new Error(result.error || 'Failed to create checkout session');
    }
  } catch (err) {
    msgEl.className = 'inv-form-message error';
    msgEl.innerHTML = '❌ <strong>Something went wrong.</strong> ' + err.message + '. Please try again or email us at invest@gositeme.com';
    msgEl.style.display = 'block';
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-lock" style="margin-right:8px;"></i> Proceed to Secure Payment →';
  return false;
}

/* ── Handle Payment Success/Cancel URL Params ── */
(function() {
  const params = new URLSearchParams(window.location.search);
  const paymentStatus = params.get('payment');
  const sessionId = params.get('session_id');

  if (paymentStatus === 'success' && sessionId) {
    // Verify payment with backend
    fetch('/api/investor.php?action=verify_payment&session_id=' + encodeURIComponent(sessionId))
      .then(r => r.json())
      .then(data => {
        const banner = document.createElement('div');
        banner.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:99999;padding:20px;text-align:center;font-family:Inter,sans-serif;font-weight:600;font-size:1rem;animation:slideDown .5s ease;';
        
        if (data.success && data.payment_status === 'paid') {
          banner.style.background = 'linear-gradient(135deg,#00b894,#0984e3)';
          banner.style.color = '#fff';
          banner.innerHTML = '🎉 <strong>Payment Successful!</strong> Welcome to the GoSiteMe investor family. Reference: ' + (data.ref_code || '') + ' — Check your email for confirmation. <a href="/investor-dashboard.php" style="color:#fff;text-decoration:underline;margin-left:12px;">Go to Dashboard →</a> <button onclick="this.parentElement.remove()" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;">×</button>';
        } else {
          banner.style.background = 'linear-gradient(135deg,#fdcb6e,#e17055)';
          banner.style.color = '#fff';
          banner.innerHTML = '⏳ <strong>Payment is being processed.</strong> You\'ll receive an email once confirmed. <button onclick="this.parentElement.remove()" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;">×</button>';
        }
        document.body.prepend(banner);
        // Clean URL
        window.history.replaceState({}, '', '/invest');
      })
      .catch(() => {});
  } else if (paymentStatus === 'cancelled') {
    const banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:99999;padding:20px;text-align:center;background:var(--inv-surface-2,#1a1a2e);color:#fdcb6e;font-family:Inter,sans-serif;font-weight:600;font-size:1rem;border-bottom:1px solid rgba(255,255,255,0.1);';
    banner.innerHTML = '↩ Payment cancelled. You can try again anytime or <a href="mailto:invest@gositeme.com" style="color:#55efc4;text-decoration:underline;">contact us</a>. <button onclick="this.parentElement.remove()" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;color:#fdcb6e;font-size:1.2rem;cursor:pointer;">×</button>';
    document.body.prepend(banner);
    window.history.replaceState({}, '', '/invest');
  }
})();
