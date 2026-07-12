/* ==========================================================
   Alfred AI Chrome Extension — Content Script
   ========================================================== */

(function() {
  'use strict';

  // Prevent double-injection
  if (window.__alfredInjected) return;
  window.__alfredInjected = true;

  /* ---------- Floating Button ---------- */
  const btn = document.createElement('div');
  btn.id = 'alfred-floating-btn';
  btn.innerHTML = '<span class="alfred-btn-icon">A</span>';
  btn.title = 'Open Alfred AI';
  document.body.appendChild(btn);

  let isDragging = false;
  let dragStartY = 0;
  let btnStartTop = 0;

  btn.addEventListener('mousedown', e => {
    isDragging = false;
    dragStartY = e.clientY;
    btnStartTop = btn.offsetTop;

    const onMove = me => {
      const delta = Math.abs(me.clientY - dragStartY);
      if (delta > 5) isDragging = true;
      btn.style.top = (btnStartTop + me.clientY - dragStartY) + 'px';
      btn.style.bottom = 'auto';
    };

    const onUp = () => {
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseup', onUp);
      if (!isDragging) openAlfred();
    };

    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
  });

  function openAlfred() {
    // Try to open side panel; falls back to sending message to background
    chrome.runtime.sendMessage({ type: 'OPEN_SIDE_PANEL' });
  }

  /* ---------- Page Content Extraction ---------- */
  function extractPageContext() {
    const title = document.title || '';
    const url   = window.location.href;

    const metaDesc = document.querySelector('meta[name="description"]');
    const description = metaDesc ? metaDesc.getAttribute('content') || '' : '';

    const headings = [];
    document.querySelectorAll('h1, h2, h3').forEach(h => {
      const text = h.textContent.trim();
      if (text) headings.push(`${h.tagName}: ${text}`);
    });

    const selectedText = window.getSelection().toString().trim();

    // Get main article text (first 3000 chars)
    const article = document.querySelector('article') || document.querySelector('main') || document.body;
    const content = (article.innerText || '').substring(0, 3000);

    return { title, url, description, headings: headings.slice(0, 20), selectedText, content };
  }

  /* ---------- Message Listener ---------- */
  chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
    if (msg.type === 'EXTRACT_CONTEXT') {
      sendResponse(extractPageContext());
      return true;
    }
  });

})();
