/**
 * GoCodeMe IDE — Model Selector Injection
 * 
 * Injects a Cursor-style model dropdown into the Theia AI Chat input area,
 * alongside the existing agent mode selector (Default/Simple/Plan).
 * 
 * Uses MutationObserver to detect when the chat input renders, then injects
 * a <select> that calls the middleware model selection API.
 * 
 * Backend: POST /middleware/api/usage/set-model  { model: "model-id" }
 *          GET  /middleware/api/usage/current-model
 */
(function gcmModelSelector() {
  'use strict';

  // ── Model definitions (must match modelRouter.js) ──────────────────
  const MODEL_GROUPS = [
    {
      label: 'Anthropic',
      emoji: '🟣',
      models: [
        { id: 'claude-opus-4-6',   name: 'Claude Opus 4.6',   tier: 'premium',  emoji: '🧠' },
        { id: 'claude-sonnet-4-6', name: 'Claude Sonnet 4.6',  tier: 'standard', emoji: '⚡' },
        { id: 'claude-haiku-4-5',  name: 'Claude Haiku 4.5',   tier: 'economy',  emoji: '💨' },
      ]
    },
    {
      label: 'OpenAI',
      emoji: '🟢',
      models: [
        { id: 'gpt-4.1',       name: 'GPT-4.1',         tier: 'standard',  emoji: '🟢' },
        { id: 'gpt-4.1-mini',  name: 'GPT-4.1 Mini',    tier: 'economy',   emoji: '🟡' },
        { id: 'gpt-4.1-nano',  name: 'GPT-4.1 Nano',    tier: 'economy',   emoji: '⚡' },
        { id: 'gpt-4o',        name: 'GPT-4o',           tier: 'standard',  emoji: '🌈' },
        { id: 'o3',            name: 'o3 (Reasoning)',    tier: 'premium',   emoji: '🔮' },
        { id: 'o3-mini',       name: 'o3 Mini',          tier: 'standard',  emoji: '💎' },
        { id: 'o4-mini',       name: 'o4 Mini',          tier: 'standard',  emoji: '✨' },
      ]
    },
    {
      label: 'Google',
      emoji: '🔵',
      models: [
        { id: 'gemini-3.1-pro',       name: 'Gemini 3.1 Pro',        tier: 'premium',  emoji: '🌐' },
        { id: 'gemini-3-flash',        name: 'Gemini 3 Flash',        tier: 'economy',  emoji: '⚡' },
        { id: 'gemini-3.1-flash-lite', name: 'Gemini 3.1 Flash Lite', tier: 'economy',  emoji: '🪶' },
        { id: 'gemini-image',          name: 'Gemini Image Gen',      tier: 'economy',  emoji: '🍌' },
        { id: 'gemini-2.5-pro',        name: 'Gemini 2.5 Pro',        tier: 'premium',  emoji: '💎' },
        { id: 'gemini-2.5-flash',      name: 'Gemini 2.5 Flash',      tier: 'economy',  emoji: '💡' },
      ]
    },
    {
      label: 'xAI',
      emoji: '🚀',
      models: [
        { id: 'grok-3',       name: 'Grok 3',       tier: 'standard', emoji: '🚀' },
        { id: 'grok-3-mini',  name: 'Grok 3 Mini',  tier: 'economy',  emoji: '⚡' },
      ]
    },
    {
      label: 'Open Source',
      emoji: '🔶',
      models: [
        { id: 'qwen3-coder',       name: 'Qwen3 Coder',       tier: 'economy',  emoji: '🔧' },
        { id: 'qwen3-coder-480b',  name: 'Qwen3 Coder 480B',  tier: 'standard', emoji: '🏗️' },
        { id: 'qwen3.5',           name: 'Qwen 3.5',          tier: 'economy',  emoji: '🌟' },
        { id: 'deepseek-v3',       name: 'DeepSeek V3.1',     tier: 'economy',  emoji: '🌊' },
        { id: 'deepseek-r1',       name: 'DeepSeek R1',       tier: 'premium',  emoji: '🧪' },
        { id: 'glm-5',             name: 'GLM-5',             tier: 'economy',  emoji: '🔶' },
        { id: 'kimi-k2.5',         name: 'Kimi K2.5',         tier: 'economy',  emoji: '🌙' },
        { id: 'kimi-k2-thinking',  name: 'Kimi K2 Thinking',  tier: 'standard', emoji: '🧠' },
        { id: 'llama-4-maverick',  name: 'Llama 4 Maverick',  tier: 'economy',  emoji: '🦙' },
        { id: 'llama-4-scout',     name: 'Llama 4 Scout',     tier: 'economy',  emoji: '🔍' },
        { id: 'mistral-small',     name: 'Mistral Small',     tier: 'economy',  emoji: '🇫🇷' },
      ]
    },
    {
      label: 'Groq (Free)',
      emoji: '🆓',
      models: [
        { id: 'groq-llama-3.3-70b', name: 'Llama 3.3 70B',  tier: 'free', emoji: '🆓' },
        { id: 'groq-llama-3.1-8b',  name: 'Llama 3.1 8B',   tier: 'free', emoji: '🆓' },
      ]
    },
    {
      label: 'Video Gen',
      emoji: '🎬',
      models: [
        { id: 'veo-2-video', name: 'Veo 2 Video',  tier: 'standard', emoji: '🎬' },
      ]
    }
  ];

  const TIER_LABELS = {
    premium:  '★ Premium',
    standard: '● Standard',
    economy:  '○ Economy',
    free:     '🆓 Free',
  };

  const TIER_COLORS = {
    premium:  '#f5a623',
    standard: '#4fc3f7',
    economy:  '#81c784',
    free:     '#f97316',
  };

  // ── State ──────────────────────────────────────────────────────────
  let currentModel = 'auto';
  let injectedSelectors = new WeakSet();  // track already-injected containers
  let statusIndicator = null;
  let multipliers = {};   // { modelKey: { multiplier, source, defaultMultiplier } }
  let isOwner = false;
  let creditBalance = null; // null = not loaded yet, number = balance in USD

  // ── Inject CSS ─────────────────────────────────────────────────────
  function injectStyles() {
    if (document.getElementById('gcm-model-selector-css')) return;
    const style = document.createElement('style');
    style.id = 'gcm-model-selector-css';
    style.textContent = `
      /* ── GoCodeMe Model Selector ── */
      .gcm-model-select-wrap {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 6px;
        position: relative;
      }
      .gcm-model-select-wrap .gcm-model-label {
        font-size: 11px;
        color: var(--theia-descriptionForeground, rgba(255,255,255,0.5));
        white-space: nowrap;
        user-select: none;
      }
      .gcm-model-select {
        appearance: none;
        -webkit-appearance: none;
        background: var(--theia-input-background, #3c3c3c);
        color: var(--theia-input-foreground, #cccccc);
        border: 1px solid var(--theia-input-border, #3c3c3c);
        border-radius: 4px;
        padding: 2px 22px 2px 6px;
        font-size: 11px;
        font-family: var(--theia-ui-font-family, system-ui, -apple-system, sans-serif);
        cursor: pointer;
        outline: none;
        max-width: 180px;
        min-width: 120px;
        line-height: 1.4;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='5'%3E%3Cpath d='M0 0l5 5 5-5z' fill='%23999'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 6px center;
        background-size: 8px 4px;
        transition: border-color 0.15s, box-shadow 0.15s;
      }
      .gcm-model-select:hover {
        border-color: var(--theia-focusBorder, #007fd4);
      }
      .gcm-model-select:focus {
        border-color: var(--theia-focusBorder, #007fd4);
        box-shadow: 0 0 0 1px var(--theia-focusBorder, #007fd4);
      }
      .gcm-model-select option {
        background: var(--theia-dropdown-background, #252526);
        color: var(--theia-dropdown-foreground, #cccccc);
        padding: 4px 8px;
      }
      .gcm-model-select optgroup {
        font-weight: 600;
        font-style: normal;
        color: var(--theia-descriptionForeground, rgba(255,255,255,0.6));
        background: var(--theia-dropdown-background, #252526);
        padding-top: 4px;
      }
      /* Tier dot indicators */
      .gcm-model-select option[data-tier="premium"] { }
      .gcm-model-select option[data-tier="standard"] { }
      .gcm-model-select option[data-tier="economy"] { }

      /* Status bar indicator */
      .gcm-model-status {
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
      .gcm-model-status:hover {
        background: var(--theia-statusBarItem-hoverBackground, rgba(255,255,255,0.12));
      }
      .gcm-model-status .gcm-model-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
      }
      .gcm-model-status .gcm-model-dot.premium  { background: #f5a623; }
      .gcm-model-status .gcm-model-dot.standard  { background: #4fc3f7; }
      .gcm-model-status .gcm-model-dot.economy   { background: #81c784; }

      /* Floating model picker popup (alternative to optgroup select) */
      .gcm-model-popup {
        position: fixed;
        z-index: 100000;
        background: var(--theia-editorWidget-background, #252526);
        border: 1px solid var(--theia-editorWidget-border, #454545);
        border-radius: 6px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        max-height: 420px;
        width: 280px;
        overflow-y: auto;
        padding: 4px 0;
        font-family: var(--theia-ui-font-family, system-ui, -apple-system, sans-serif);
      }
      .gcm-model-popup::-webkit-scrollbar { width: 6px; }
      .gcm-model-popup::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
      .gcm-model-popup .gcm-popup-group {
        padding: 6px 12px 2px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--theia-descriptionForeground, rgba(255,255,255,0.45));
        user-select: none;
      }
      .gcm-model-popup .gcm-popup-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        cursor: pointer;
        font-size: 12px;
        color: var(--theia-foreground, #cccccc);
        border-radius: 3px;
        margin: 0 4px;
        transition: background 0.1s;
      }
      .gcm-model-popup .gcm-popup-item:hover {
        background: var(--theia-list-hoverBackground, rgba(255,255,255,0.08));
      }
      .gcm-model-popup .gcm-popup-item.active {
        background: var(--theia-list-activeSelectionBackground, #04395e);
        color: var(--theia-list-activeSelectionForeground, #ffffff);
      }
      .gcm-model-popup .gcm-popup-item .gcm-item-emoji {
        font-size: 14px;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
      }
      .gcm-model-popup .gcm-popup-item .gcm-item-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .gcm-model-popup .gcm-popup-item .gcm-item-tier {
        font-size: 9px;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 500;
        flex-shrink: 0;
      }
      .gcm-model-popup .gcm-popup-item .gcm-item-tier.premium {
        background: rgba(245,166,35,0.15);
        color: #f5a623;
      }
      .gcm-model-popup .gcm-popup-item .gcm-item-tier.standard {
        background: rgba(79,195,247,0.15);
        color: #4fc3f7;
      }
      .gcm-model-popup .gcm-popup-item .gcm-item-tier.economy {
        background: rgba(129,199,132,0.15);
        color: #81c784;
      }
      /* Multiplier badges (owner only) */
      .gcm-model-popup .gcm-popup-item .gcm-multiplier-badge {
        font-size: 9px;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 600;
        flex-shrink: 0;
        cursor: pointer;
        background: rgba(255,152,0,0.15);
        color: #ff9800;
        border: 1px solid transparent;
        transition: background 0.15s, border-color 0.15s;
        min-width: 24px;
        text-align: center;
        margin-left: 4px;
      }
      .gcm-model-popup .gcm-popup-item .gcm-multiplier-badge:hover {
        background: rgba(255,152,0,0.3);
        border-color: #ff9800;
      }
      .gcm-multiplier-editor {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        margin: 0 4px 2px;
        background: rgba(255,152,0,0.06);
        border-radius: 3px;
        flex-wrap: wrap;
      }
      .gcm-multiplier-editor input {
        width: 54px;
        background: var(--theia-input-background, #3c3c3c);
        color: var(--theia-input-foreground, #cccccc);
        border: 1px solid var(--theia-input-border, #555);
        border-radius: 3px;
        padding: 2px 6px;
        font-size: 11px;
        text-align: center;
        outline: none;
      }
      .gcm-multiplier-editor input:focus {
        border-color: #ff9800;
      }
      .gcm-multiplier-editor .gcm-mult-preset {
        padding: 2px 6px;
        font-size: 9px;
        background: rgba(255,152,0,0.15);
        color: #ff9800;
        border: 1px solid rgba(255,152,0,0.3);
        border-radius: 3px;
        cursor: pointer;
        font-weight: 600;
        font-family: inherit;
        line-height: 1.3;
      }
      .gcm-multiplier-editor .gcm-mult-preset:hover {
        background: rgba(255,152,0,0.35);
      }
      /* Cost warning banner */
      .gcm-cost-warning {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        margin: 2px 4px;
        background: rgba(244,67,54,0.12);
        border: 1px solid rgba(244,67,54,0.3);
        border-radius: 4px;
        font-size: 10px;
        color: #ff6b6b;
        line-height: 1.3;
      }
      .gcm-cost-warning.moderate {
        background: rgba(255,152,0,0.10);
        border-color: rgba(255,152,0,0.3);
        color: #ff9800;
      }
      .gcm-cost-info {
        font-size: 9px;
        color: rgba(255,255,255,0.4);
        padding: 0 12px;
        margin-top: -2px;
      }
      /* Auto mode special item */
      .gcm-model-popup .gcm-popup-auto {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        color: var(--theia-foreground, #cccccc);
        border-bottom: 1px solid var(--theia-editorWidget-border, #454545);
        margin-bottom: 4px;
        transition: background 0.1s;
      }
      .gcm-model-popup .gcm-popup-auto:hover {
        background: var(--theia-list-hoverBackground, rgba(255,255,255,0.08));
      }
      .gcm-model-popup .gcm-popup-auto.active {
        background: var(--theia-list-activeSelectionBackground, #04395e);
        color: var(--theia-list-activeSelectionForeground, #ffffff);
      }

      /* Credit balance row */
      .gcm-credit-balance {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        font-size: 11px;
        color: var(--theia-descriptionForeground, #888);
        border-bottom: 1px solid var(--theia-editorWidget-border, #454545);
        margin-bottom: 2px;
      }
      .gcm-credit-icon { font-size: 13px; }
      .gcm-credit-amount {
        margin-left: auto;
        font-weight: 700;
        color: #81c784;
        font-family: monospace;
      }
      .gcm-credit-amount.low {
        color: #ef5350;
        animation: gcm-pulse 1.5s ease-in-out infinite;
      }
      @keyframes gcm-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
      }

      /* Model trigger button in chat input */
      .gcm-model-trigger {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 1px 8px 1px 6px;
        font-size: 11px;
        color: var(--theia-foreground, #cccccc);
        background: var(--theia-input-background, #3c3c3c);
        border: 1px solid var(--theia-input-border, #3c3c3c);
        border-radius: 4px;
        cursor: pointer;
        outline: none;
        font-family: var(--theia-ui-font-family, system-ui, -apple-system, sans-serif);
        white-space: nowrap;
        transition: border-color 0.15s;
        user-select: none;
        margin-left: 4px;
      }
      .gcm-model-trigger:hover {
        border-color: var(--theia-focusBorder, #007fd4);
      }
      .gcm-model-trigger .gcm-trigger-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
      }
      .gcm-model-trigger .gcm-trigger-chevron {
        font-size: 9px;
        opacity: 0.6;
        margin-left: 2px;
      }
    `;
    document.head.appendChild(style);
  }

  // ── API calls ──────────────────────────────────────────────────────
  function getAuthHeaders() {
    const token = window.__gcmSessionToken;
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = 'Bearer ' + token;
    return headers;
  }

  async function fetchCurrentModel() {
    try {
      const r = await fetch('/middleware/api/usage/current-model', {
        headers: getAuthHeaders(),
        credentials: 'include'
      });
      const d = await r.json();
      if (d.model) currentModel = d.model;
      else if (d.mode) currentModel = d.mode;
      else currentModel = 'auto';
    } catch {
      currentModel = 'auto';
    }
    return currentModel;
  }

  async function setModel(modelId) {
    currentModel = modelId;
    try {
      await fetch('/middleware/api/usage/set-model', {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({ model: modelId }),
        credentials: 'include'
      });
    } catch (e) {
      console.warn('[GCM] Failed to set model:', e);
    }
    // Update all visible selectors
    updateAllSelectors();
    updateStatusIndicator();
  }

  // ── Multiplier API (owner-only) ─────────────────────────────────────
  async function fetchMultipliers() {
    try {
      const r = await fetch('/middleware/api/usage/multipliers', {
        headers: getAuthHeaders(),
        credentials: 'include'
      });
      if (r.status === 401 || r.status === 403) { isOwner = false; return; }
      const d = await r.json();
      if (d.ok && d.multipliers) {
        multipliers = d.multipliers;
        isOwner = true;
      }
    } catch { isOwner = false; }
  }

  // ── Credit Balance ─────────────────────────────────────────────────
  async function fetchCreditBalance() {
    try {
      const r = await fetch('/middleware/api/usage/credits', {
        headers: getAuthHeaders(),
        credentials: 'include'
      });
      if (!r.ok) return;
      const d = await r.json();
      if (d.ok && typeof d.balance === 'number') {
        creditBalance = d.balance;
      }
    } catch { /* credit display is optional */ }
  }

  async function setMultiplierValue(modelKey, value) {
    try {
      const r = await fetch('/middleware/api/usage/multiplier/' + encodeURIComponent(modelKey), {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify({ multiplier: value }),
        credentials: 'include'
      });
      const d = await r.json();
      if (d.ok) {
        if (!multipliers[modelKey]) multipliers[modelKey] = {};
        multipliers[modelKey].multiplier = value;
        multipliers[modelKey].source = 'override';
      }
    } catch (e) {
      console.warn('[GCM] Failed to set multiplier:', e);
    }
  }

  // ── Cost estimation (per average message) ─────────────────────────
  // Based on average message: ~2000 input tokens, ~800 output tokens
  const MODEL_PRICING = {
    'claude-opus-4-6':   { input: 15.0,  output: 75.0,  defaultMult: 5 },
    'claude-sonnet-4-6': { input: 3.0,   output: 15.0,  defaultMult: 3 },
    'claude-haiku-4-5':  { input: 0.80,  output: 4.0,   defaultMult: 1 },
    'gpt-4.1':           { input: 2.0,   output: 8.0,   defaultMult: 2 },
    'gpt-4.1-mini':      { input: 0.40,  output: 1.60,  defaultMult: 0.5 },
    'gpt-4.1-nano':      { input: 0.10,  output: 0.40,  defaultMult: 0.25 },
    'gpt-4o':            { input: 2.50,  output: 10.0,  defaultMult: 2 },
    'o3':                { input: 10.0,  output: 40.0,  defaultMult: 4 },
    'o3-mini':           { input: 1.10,  output: 4.40,  defaultMult: 1 },
    'o4-mini':           { input: 1.10,  output: 4.40,  defaultMult: 1 },
  };
  const AVG_INPUT = 2000, AVG_OUTPUT = 800;

  function estimateCostPerMessage(modelId) {
    const p = MODEL_PRICING[modelId];
    if (!p) return null;
    const mData = multipliers[modelId];
    const mult = mData ? (mData.multiplier || p.defaultMult) : p.defaultMult;
    // API cost per message
    const apiCost = (AVG_INPUT / 1e6) * p.input + (AVG_OUTPUT / 1e6) * p.output;
    // Plan tokens burned per message (output * multiplier)
    const planTokensBurned = AVG_OUTPUT * mult;
    return { apiCost, planTokensBurned, multiplier: mult };
  }

  function getCostLevel(modelId) {
    const est = estimateCostPerMessage(modelId);
    if (!est) return 'normal';
    // High multiplier warning thresholds
    if (est.multiplier >= 100) return 'extreme';
    if (est.multiplier >= 30) return 'expensive';
    if (est.multiplier >= 10) return 'moderate';
    return 'normal';
  }

  // ── Find model info ────────────────────────────────────────────────
  function findModelInfo(id) {
    for (const g of MODEL_GROUPS) {
      for (const m of g.models) {
        if (m.id === id) return { ...m, provider: g.label };
      }
    }
    return null;
  }

  function getDisplayName(id) {
    if (id === 'auto') return 'Auto';
    const info = findModelInfo(id);
    return info ? info.name : id;
  }

  function getModelTier(id) {
    if (id === 'auto') return 'standard';
    const info = findModelInfo(id);
    return info ? info.tier : 'standard';
  }

  // ── Build popup model picker ───────────────────────────────────────
  function showModelPopup(anchor) {
    // Remove existing popup
    closeModelPopup();

    const popup = document.createElement('div');
    popup.className = 'gcm-model-popup';
    popup.id = 'gcm-model-popup';

    // Auto option
    const autoItem = document.createElement('div');
    autoItem.className = 'gcm-popup-auto' + (currentModel === 'auto' ? ' active' : '');
    autoItem.innerHTML = '<span style="font-size:14px">🤖</span><span>Auto (Smart Routing)</span>';
    autoItem.addEventListener('click', () => { setModel('auto'); closeModelPopup(); });
    popup.appendChild(autoItem);

    // Credit balance display
    if (creditBalance !== null) {
      const balRow = document.createElement('div');
      balRow.className = 'gcm-credit-balance';
      const isLow = creditBalance < 5;
      balRow.innerHTML = `<span class="gcm-credit-icon">${isLow ? '⚠️' : '💳'}</span>`
        + `<span>Credit Balance</span>`
        + `<span class="gcm-credit-amount${isLow ? ' low' : ''}">$${creditBalance.toFixed(2)}</span>`;
      popup.appendChild(balRow);
    }

    // Groups
    for (const group of MODEL_GROUPS) {
      const header = document.createElement('div');
      header.className = 'gcm-popup-group';
      header.textContent = group.emoji + ' ' + group.label;
      popup.appendChild(header);

      for (const m of group.models) {
        const item = document.createElement('div');
        item.className = 'gcm-popup-item' + (currentModel === m.id ? ' active' : '');
        item.innerHTML = `
          <span class="gcm-item-emoji">${m.emoji}</span>
          <span class="gcm-item-name">${m.name}</span>
          <span class="gcm-item-tier ${m.tier}">${TIER_LABELS[m.tier]}</span>
        `;

        // Multiplier badge (owner only)
        if (isOwner) {
          const mData = multipliers[m.id];
          const mult = mData ? mData.multiplier : 1;
          const badge = document.createElement('span');
          badge.className = 'gcm-multiplier-badge';
          badge.textContent = mult + '\u00d7';
          badge.title = 'Click to set multiplier';
          badge.addEventListener('click', (e) => {
            e.stopPropagation();
            // Remove any existing editors
            popup.querySelectorAll('.gcm-multiplier-editor').forEach(el => el.remove());
            // Build inline editor
            const editor = document.createElement('div');
            editor.className = 'gcm-multiplier-editor';
            editor.addEventListener('click', (ev) => ev.stopPropagation());
            const input = document.createElement('input');
            input.type = 'number'; input.min = '0'; input.max = '999'; input.step = '1';
            input.value = mult;
            input.addEventListener('keydown', async (ev) => {
              if (ev.key === 'Enter') {
                ev.stopPropagation();
                const val = parseFloat(input.value);
                if (!isNaN(val) && val >= 0 && val <= 999) {
                  await setMultiplierValue(m.id, val);
                  badge.textContent = val + '\u00d7';
                }
                editor.remove();
              } else if (ev.key === 'Escape') { editor.remove(); }
            });
            editor.appendChild(input);
            [1, 12, 30, 60, 100, 600].forEach(p => {
              const btn = document.createElement('button');
              btn.className = 'gcm-mult-preset';
              btn.textContent = p + '\u00d7';
              btn.addEventListener('click', async (ev) => {
                ev.stopPropagation();
                await setMultiplierValue(m.id, p);
                badge.textContent = p + '\u00d7';
                editor.remove();
              });
              editor.appendChild(btn);
            });
            item.after(editor);
            input.focus();
            input.select();
          });
          item.appendChild(badge);
        }

        // Cost warning for expensive multipliers (visible to everyone)
        const costLevel = getCostLevel(m.id);

        item.addEventListener('click', () => { setModel(m.id); closeModelPopup(); });
        popup.appendChild(item);

        // Append cost warnings after the item in the popup
        if (costLevel === 'extreme') {
          const warn = document.createElement('div');
          warn.className = 'gcm-cost-warning';
          const est = estimateCostPerMessage(m.id);
          warn.innerHTML = '\u26a0\ufe0f <strong>Very Expensive</strong> \u2014 ' + est.multiplier + '\u00d7 multiplier burns ~' + est.planTokensBurned.toLocaleString() + ' tokens/msg. Add credits first!';
          popup.appendChild(warn);
        } else if (costLevel === 'expensive') {
          const warn = document.createElement('div');
          warn.className = 'gcm-cost-warning moderate';
          const est = estimateCostPerMessage(m.id);
          warn.innerHTML = '\u26a0 <strong>Expensive</strong> \u2014 ' + est.multiplier + '\u00d7 costs ~' + est.planTokensBurned.toLocaleString() + ' tokens/msg. Auto mode saves more.';
          popup.appendChild(warn);
        }
      }
    }

    // Position popup above the anchor
    document.body.appendChild(popup);
    const rect = anchor.getBoundingClientRect();
    const popupH = popup.offsetHeight;
    const popupW = popup.offsetWidth;

    let top = rect.top - popupH - 4;
    let left = rect.left;

    // If would go off top, show below
    if (top < 8) top = rect.bottom + 4;
    // Keep within viewport horizontally
    if (left + popupW > window.innerWidth - 8) left = window.innerWidth - popupW - 8;
    if (left < 8) left = 8;

    popup.style.top = top + 'px';
    popup.style.left = left + 'px';

    // Close on outside click
    setTimeout(() => {
      document.addEventListener('mousedown', onOutsideClick);
      document.addEventListener('keydown', onEscKey);
    }, 0);
  }

  function closeModelPopup() {
    const existing = document.getElementById('gcm-model-popup');
    if (existing) existing.remove();
    document.removeEventListener('mousedown', onOutsideClick);
    document.removeEventListener('keydown', onEscKey);
  }

  function onOutsideClick(e) {
    const popup = document.getElementById('gcm-model-popup');
    if (popup && !popup.contains(e.target) && !e.target.closest('.gcm-model-trigger')) {
      closeModelPopup();
    }
  }

  function onEscKey(e) {
    if (e.key === 'Escape') closeModelPopup();
  }

  // ── Inject model trigger button into chat input ────────────────────
  function injectModelTrigger(container) {
    if (injectedSelectors.has(container)) return;
    injectedSelectors.add(container);

    const trigger = document.createElement('button');
    trigger.className = 'gcm-model-trigger';
    trigger.dataset.gcmTrigger = 'true';

    function updateTrigger() {
      const tier = getModelTier(currentModel);
      const name = getDisplayName(currentModel);
      trigger.innerHTML = `
        <span class="gcm-trigger-dot" style="background:${TIER_COLORS[tier] || '#4fc3f7'}"></span>
        <span>${name}</span>
        <span class="gcm-trigger-chevron">▾</span>
      `;
    }
    updateTrigger();
    trigger._gcmUpdate = updateTrigger;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const popup = document.getElementById('gcm-model-popup');
      if (popup) { closeModelPopup(); return; }
      showModelPopup(trigger);
    });

    // Insert after mode selector or as first child
    const modeSelector = container.querySelector('.theia-ChatInput-ModeSelector');
    if (modeSelector) {
      modeSelector.parentNode.insertBefore(trigger, modeSelector.nextSibling);
    } else {
      container.appendChild(trigger);
    }
  }

  // ── Update all injected triggers ───────────────────────────────────
  function updateAllSelectors() {
    document.querySelectorAll('.gcm-model-trigger').forEach(el => {
      if (el._gcmUpdate) el._gcmUpdate();
    });
  }

  // ── Status bar indicator ───────────────────────────────────────────
  function injectStatusBarIndicator() {
    // Wait for status bar to exist
    const statusBar = document.querySelector('#theia-statusBar .area.right') ||
                      document.querySelector('#theia-statusBar .right');
    if (!statusBar || document.getElementById('gcm-model-status')) return;

    statusIndicator = document.createElement('div');
    statusIndicator.id = 'gcm-model-status';
    statusIndicator.className = 'gcm-model-status element';
    statusIndicator.title = 'AI Model — Click to change';
    updateStatusIndicator();

    statusIndicator.addEventListener('click', (e) => {
      e.stopPropagation();
      showModelPopup(statusIndicator);
    });

    // Insert at the beginning of right area
    statusBar.insertBefore(statusIndicator, statusBar.firstChild);
  }

  function updateStatusIndicator() {
    if (!statusIndicator) return;
    const tier = getModelTier(currentModel);
    const name = getDisplayName(currentModel);
    statusIndicator.innerHTML = `
      <span class="gcm-model-dot ${tier}"></span>
      <span>${name}</span>
    `;
  }

  // ── MutationObserver: watch for chat input rendering ───────────────
  function startObserver() {
    const observer = new MutationObserver((mutations) => {
      // Check for new chat input option containers
      const containers = document.querySelectorAll('.theia-ChatInputOptions-left');
      containers.forEach(container => {
        if (!injectedSelectors.has(container)) {
          injectModelTrigger(container);
        }
      });

      // Check for status bar if not yet injected
      if (!document.getElementById('gcm-model-status')) {
        injectStatusBarIndicator();
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    // Also try immediately in case DOM is already there
    setTimeout(() => {
      const containers = document.querySelectorAll('.theia-ChatInputOptions-left');
      containers.forEach(container => injectModelTrigger(container));
      injectStatusBarIndicator();
    }, 2000);

    // Retry a few more times for slower load
    setTimeout(() => {
      const containers = document.querySelectorAll('.theia-ChatInputOptions-left');
      containers.forEach(container => injectModelTrigger(container));
      injectStatusBarIndicator();
    }, 5000);
  }

  // ── Initialize ─────────────────────────────────────────────────────
  async function init() {
    injectStyles();

    // Fetch current model + multipliers + credit balance
    await fetchCurrentModel();
    await fetchMultipliers();
    await fetchCreditBalance();

    // Start watching for chat input
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', startObserver);
    } else {
      startObserver();
    }

    // Periodically sync model + credit balance (in case changed from settings panel)
    setInterval(async () => {
      const prev = currentModel;
      await fetchCurrentModel();
      await fetchCreditBalance();
      if (prev !== currentModel) {
        updateAllSelectors();
        updateStatusIndicator();
      }
    }, 30000); // Every 30 seconds

    console.log('[GoCodeMe] Model selector initialized — current model:', currentModel);
  }

  // Wait for Theia to load, then init
  if (document.readyState === 'complete') {
    setTimeout(init, 1500);
  } else {
    window.addEventListener('load', () => setTimeout(init, 1500));
  }
})();
