/**
 * GoCodeMe IDE — Context Window Token Meter
 *
 * Displays a context window usage indicator in the AI Chat panel,
 * similar to VS Code Copilot's token counter. Shows estimated tokens
 * used vs max, with a progress bar and compact conversation button.
 *
 * Reads the `x-context-usage` header from Anthropic proxy responses
 * by intercepting fetch(). Also adds a "Compact Conversation" button
 * when usage exceeds 75%.
 *
 * Backend:  x-context-usage header on every /api/anthropic-proxy response
 *           POST /middleware/api/anthropic-proxy/compact
 */
(function gcmContextMeter() {
  'use strict';

  // ── State ────────────────────────────────────────────────────────
  let contextData = null;  // { estimatedTokens, maxTokens, percent, level }
  let meterElement = null;
  let injectedMeters = new WeakSet();

  // ── CSS ──────────────────────────────────────────────────────────
  function injectStyles() {
    if (document.getElementById('gcm-context-meter-css')) return;
    const style = document.createElement('style');
    style.id = 'gcm-context-meter-css';
    style.textContent = `
      /* ── Context Window Meter ── */
      .gcm-context-meter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 8px;
        font-size: 11px;
        font-family: var(--theia-ui-font-family, system-ui, -apple-system, sans-serif);
        color: var(--theia-descriptionForeground, rgba(255,255,255,0.55));
        cursor: pointer;
        user-select: none;
        border-radius: 4px;
        transition: background 0.15s;
        position: relative;
        margin-left: 4px;
        white-space: nowrap;
      }
      .gcm-context-meter:hover {
        background: var(--theia-toolbar-hoverBackground, rgba(255,255,255,0.08));
        color: var(--theia-foreground, #ccc);
      }
      .gcm-context-bar-bg {
        width: 48px;
        height: 4px;
        border-radius: 2px;
        background: rgba(255,255,255,0.1);
        overflow: hidden;
        flex-shrink: 0;
      }
      .gcm-context-bar-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.4s ease, background 0.3s;
      }
      .gcm-context-bar-fill.ok       { background: #4fc3f7; }
      .gcm-context-bar-fill.warning  { background: #ff9800; }
      .gcm-context-bar-fill.critical { background: #f44336; }
      .gcm-context-pct {
        font-size: 10px;
        font-weight: 600;
        min-width: 26px;
        text-align: right;
      }
      .gcm-context-pct.ok       { color: rgba(255,255,255,0.5); }
      .gcm-context-pct.warning  { color: #ff9800; }
      .gcm-context-pct.critical { color: #f44336; }

      /* Tooltip popup on hover */
      .gcm-context-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 8px);
        right: 0;
        background: var(--theia-editorWidget-background, #252526);
        border: 1px solid var(--theia-editorWidget-border, #454545);
        border-radius: 6px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.4);
        padding: 10px 14px;
        min-width: 220px;
        z-index: 100001;
        font-size: 11px;
        color: var(--theia-foreground, #ccc);
        white-space: normal;
      }
      .gcm-context-meter:hover .gcm-context-tooltip {
        display: block;
      }
      .gcm-context-tooltip-title {
        font-weight: 600;
        font-size: 12px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
      }
      .gcm-context-tooltip-row {
        display: flex;
        justify-content: space-between;
        padding: 2px 0;
        font-size: 11px;
      }
      .gcm-context-tooltip-row .label {
        color: var(--theia-descriptionForeground, rgba(255,255,255,0.55));
      }
      .gcm-context-tooltip-row .value {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
      }
      .gcm-context-tooltip-bar {
        width: 100%;
        height: 6px;
        border-radius: 3px;
        background: rgba(255,255,255,0.1);
        margin: 8px 0 4px;
        overflow: hidden;
      }
      .gcm-context-tooltip-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s;
      }
      .gcm-context-compact-btn {
        display: block;
        width: 100%;
        margin-top: 8px;
        padding: 4px 8px;
        font-size: 11px;
        font-family: inherit;
        background: rgba(79,195,247,0.15);
        color: #4fc3f7;
        border: 1px solid rgba(79,195,247,0.3);
        border-radius: 4px;
        cursor: pointer;
        text-align: center;
        font-weight: 500;
      }
      .gcm-context-compact-btn:hover {
        background: rgba(79,195,247,0.25);
      }
      .gcm-context-compact-btn.warning {
        background: rgba(255,152,0,0.15);
        color: #ff9800;
        border-color: rgba(255,152,0,0.3);
      }
      .gcm-context-compact-btn.warning:hover {
        background: rgba(255,152,0,0.25);
      }

      /* Status bar context indicator */
      .gcm-context-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0 8px;
        font-size: 11px;
        color: var(--theia-statusBarItem-remoteForeground, var(--theia-statusBar-foreground, rgba(255,255,255,0.7)));
        cursor: pointer;
        height: 100%;
        white-space: nowrap;
      }
      .gcm-context-status:hover {
        background: var(--theia-statusBarItem-hoverBackground, rgba(255,255,255,0.12));
      }
    `;
    document.head.appendChild(style);
  }

  // ── Format token numbers ─────────────────────────────────────────
  function formatTokens(n) {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return n.toString();
  }

  // ── Intercept fetch to capture x-context-usage ───────────────────
  const originalFetch = window.fetch;
  window.fetch = async function(...args) {
    const response = await originalFetch.apply(this, args);

    // Only intercept Anthropic proxy responses
    const url = typeof args[0] === 'string' ? args[0] : (args[0]?.url || '');
    if (url.includes('/api/anthropic-proxy') || url.includes('/v1/messages')) {
      try {
        const header = response.headers.get('x-context-usage');
        if (header) {
          const data = JSON.parse(header);
          if (data.estimatedTokens !== undefined) {
            contextData = data;
            updateAllMeters();
          }
        }
      } catch { /* ignore parse errors */ }
    }

    return response;
  };

  // ── Build / update meter element ─────────────────────────────────
  function createMeter() {
    const meter = document.createElement('div');
    meter.className = 'gcm-context-meter';
    meter.title = 'Context Window Usage';

    updateMeterContent(meter);
    return meter;
  }

  function updateMeterContent(meter) {
    if (!contextData) {
      meter.innerHTML = `
        <span class="gcm-context-pct ok" style="font-size:10px;opacity:0.6">Context</span>
        <span class="gcm-context-bar-bg"><span class="gcm-context-bar-fill ok" style="width:0%"></span></span>
        <span class="gcm-context-pct ok">—</span>
      `;
      return;
    }

    const { estimatedTokens, maxTokens, percent, level } = contextData;
    const fillColor = level || (percent >= 90 ? 'critical' : percent >= 75 ? 'warning' : 'ok');

    meter.innerHTML = `
      <span style="font-size:10px;opacity:0.7">Context</span>
      <span class="gcm-context-bar-bg">
        <span class="gcm-context-bar-fill ${fillColor}" style="width:${Math.min(percent, 100)}%"></span>
      </span>
      <span class="gcm-context-pct ${fillColor}">${percent}%</span>
      <div class="gcm-context-tooltip">
        <div class="gcm-context-tooltip-title">
          <span>📊</span>
          <span>Context Window</span>
        </div>
        <div class="gcm-context-tooltip-row">
          <span class="label">Tokens Used</span>
          <span class="value">${formatTokens(estimatedTokens)}</span>
        </div>
        <div class="gcm-context-tooltip-row">
          <span class="label">Max Tokens</span>
          <span class="value">${formatTokens(maxTokens)}</span>
        </div>
        <div class="gcm-context-tooltip-row">
          <span class="label">Usage</span>
          <span class="value" style="color:${fillColor === 'critical' ? '#f44336' : fillColor === 'warning' ? '#ff9800' : '#4fc3f7'}">${formatTokens(estimatedTokens)} / ${formatTokens(maxTokens)} (${percent}%)</span>
        </div>
        <div class="gcm-context-tooltip-bar">
          <span class="gcm-context-tooltip-bar-fill ${fillColor}" style="width:${Math.min(percent, 100)}%;display:block;background:${fillColor === 'critical' ? '#f44336' : fillColor === 'warning' ? '#ff9800' : '#4fc3f7'}"></span>
        </div>
        ${percent >= 50 ? `<button class="gcm-context-compact-btn ${fillColor === 'warning' || fillColor === 'critical' ? 'warning' : ''}" onclick="event.stopPropagation(); window.__gcmCompactConversation && window.__gcmCompactConversation();">🗜️ Compact Conversation</button>` : ''}
      </div>
    `;
  }

  function updateAllMeters() {
    document.querySelectorAll('.gcm-context-meter').forEach(m => updateMeterContent(m));
    updateStatusBarIndicator();
  }

  // ── Inject into chat input options ───────────────────────────────
  function injectMeter(container) {
    if (injectedMeters.has(container)) return;
    injectedMeters.add(container);

    const meter = createMeter();
    // Insert after model trigger (if present) or at end
    const modelTrigger = container.querySelector('.gcm-model-trigger');
    if (modelTrigger) {
      modelTrigger.parentNode.insertBefore(meter, modelTrigger.nextSibling);
    } else {
      container.appendChild(meter);
    }
  }

  // ── Status bar indicator ─────────────────────────────────────────
  let statusElement = null;

  function injectStatusBarIndicator() {
    const statusBar = document.querySelector('#theia-statusBar .area.right') ||
                      document.querySelector('#theia-statusBar .right');
    if (!statusBar || document.getElementById('gcm-context-status')) return;

    statusElement = document.createElement('div');
    statusElement.id = 'gcm-context-status';
    statusElement.className = 'gcm-context-status element';
    statusElement.title = 'Context Window Usage';
    updateStatusBarIndicator();

    // Insert after model status (if present) or at beginning
    const modelStatus = document.getElementById('gcm-model-status');
    if (modelStatus) {
      modelStatus.parentNode.insertBefore(statusElement, modelStatus.nextSibling);
    } else {
      statusBar.insertBefore(statusElement, statusBar.firstChild);
    }
  }

  function updateStatusBarIndicator() {
    if (!statusElement) return;
    if (!contextData) {
      statusElement.innerHTML = '<span style="opacity:0.5">Context —</span>';
      return;
    }
    const { percent, level } = contextData;
    const color = level === 'critical' ? '#f44336' : level === 'warning' ? '#ff9800' : 'rgba(255,255,255,0.6)';
    statusElement.innerHTML = `<span style="color:${color}">Context ${percent}%</span>`;
  }

  // ── Compact conversation handler ─────────────────────────────────
  // This is exposed globally so the tooltip button can call it.
  // Theia 1.68.2 has a built-in compact command we can invoke.
  window.__gcmCompactConversation = function() {
    // Try Theia's built-in command palette
    try {
      const commandService = document.querySelector('[data-command-id]');
      // Fallback: use Theia's keybinding approach or direct API
      if (window.theia && window.theia.commands) {
        window.theia.commands.executeCommand('aiHistory:toggleCompact');
        return;
      }
    } catch {}
    // Manual compact: call the middleware endpoint
    console.log('[GCM] Compact conversation requested — use the Compact button in Chat toolbar');
  };

  // ── MutationObserver ─────────────────────────────────────────────
  function startObserver() {
    const observer = new MutationObserver(() => {
      const containers = document.querySelectorAll('.theia-ChatInputOptions-left');
      containers.forEach(container => {
        if (!injectedMeters.has(container)) {
          injectMeter(container);
        }
      });

      if (!document.getElementById('gcm-context-status')) {
        injectStatusBarIndicator();
      }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Initial injection attempts
    setTimeout(() => {
      document.querySelectorAll('.theia-ChatInputOptions-left').forEach(c => injectMeter(c));
      injectStatusBarIndicator();
    }, 2500);

    setTimeout(() => {
      document.querySelectorAll('.theia-ChatInputOptions-left').forEach(c => injectMeter(c));
      injectStatusBarIndicator();
    }, 5500);
  }

  // ── Init ─────────────────────────────────────────────────────────
  function init() {
    injectStyles();

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', startObserver);
    } else {
      startObserver();
    }

    console.log('[GoCodeMe] Context meter initialized');
  }

  if (document.readyState === 'complete') {
    setTimeout(init, 2000);
  } else {
    window.addEventListener('load', () => setTimeout(init, 2000));
  }
})();
