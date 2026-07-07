<?php
$page_title = 'Circuit Simulator v5.0 — GoSiteMe';
$page_description = 'Advanced MNA-based circuit simulator with Newton-Raphson convergence, adaptive timestep, FFT analysis, Bode plots, transient simulation, SPICE-level device models, ZPE research, and 48+ components. Free, real-time simulation powered by GoSiteMe.';
$page_canonical = 'https://gositeme.com/circuit-simulator.php';
$page_og_title = 'GoSiteMe Circuit Simulator v5.0 — SPICE Models, MNA Engine, FFT, Bode & ZPE';
$page_og_description = 'Full-featured circuit simulator with Modified Nodal Analysis, Newton-Raphson solver, adaptive timestep, FFT, Bode plots, Tesla coils, ZPE experiments, and 48+ components.';
$page_og_image = 'https://gositeme.com/assets/images/og-circuit-sim.png';
$page_og_image_alt = 'GoSiteMe Circuit Simulator v5.0';
$page_twitter_description = 'MNA-based circuit simulator — Newton-Raphson, FFT, Bode, Tesla coils, ZPE research. Free by GoSiteMe.';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
?>

<style>
/* ═══════════════════════════════════════
   CIRCUIT SIMULATOR v5.0 — SPICE Models + MNA Engine
   ═══════════════════════════════════════ */
:root {
    --cs-bg: #060612;
    --cs-surface: #0e0e1a;
    --cs-surface-2: #161628;
    --cs-border: rgba(0,212,255,0.12);
    --cs-accent: #00D4FF;
    --cs-accent-2: #7D00FF;
    --cs-energy: #00FF88;
    --cs-warn: #FFB800;
    --cs-danger: #FF3366;
    --cs-text: #e8e8f0;
    --cs-text-muted: #6a7a8a;
    --cs-radius: 12px;
    --cs-glow: 0 0 20px rgba(0,212,255,0.15);
    --cs-tesla: #FF3366;
    --cs-zpe: #00FFCC;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

.cs-page {
    min-height: 100vh;
    background: var(--cs-bg);
    color: var(--cs-text);
    font-family: 'Inter', -apple-system, system-ui, sans-serif;
}

/* ── Top Bar ── */
.cs-topbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 1rem;
    background: var(--cs-surface);
    border-bottom: 1px solid var(--cs-border);
    z-index: 100;
    position: relative;
}
.cs-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    font-size: 1rem;
    color: var(--cs-accent);
    text-decoration: none;
}
.cs-logo svg { width: 24px; height: 24px; }
.cs-topbar-title {
    font-size: 0.85rem;
    color: var(--cs-text-muted);
    padding-left: 1rem;
    border-left: 1px solid var(--cs-border);
}
.cs-topbar-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
    align-items: center;
    flex-wrap: wrap;
}
.cs-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    border: 1px solid var(--cs-border);
    background: var(--cs-surface-2);
    color: var(--cs-text);
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    white-space: nowrap;
}
.cs-btn:hover { border-color: var(--cs-accent); background: rgba(0,212,255,0.08); }
.cs-btn-primary {
    background: linear-gradient(135deg, var(--cs-accent), var(--cs-accent-2));
    border-color: transparent;
    color: #fff;
    font-weight: 600;
}
.cs-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.cs-btn-energy {
    background: linear-gradient(135deg, var(--cs-energy), #00cc66);
    border-color: transparent;
    color: #000;
    font-weight: 600;
}
.cs-btn-tesla {
    background: linear-gradient(135deg, #FF3366, #FF6699);
    border-color: transparent;
    color: #fff;
    font-weight: 600;
}
.cs-btn-zpe {
    background: linear-gradient(135deg, #00FFCC, #00D4FF);
    border-color: transparent;
    color: #000;
    font-weight: 600;
}
.cs-btn-sm { padding: 0.3rem 0.5rem; font-size: 0.72rem; }

/* ── Energy Bar ── */
.cs-energy-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.7rem;
    background: rgba(0,255,136,0.08);
    border: 1px solid rgba(0,255,136,0.2);
    border-radius: 20px;
    font-size: 0.75rem;
    color: var(--cs-energy);
}
.cs-energy-icon { font-size: 1rem; }
.cs-energy-level {
    width: 60px;
    height: 6px;
    background: rgba(0,255,136,0.15);
    border-radius: 3px;
    overflow: hidden;
}
.cs-energy-fill {
    height: 100%;
    background: var(--cs-energy);
    border-radius: 3px;
    width: 0%;
    transition: width 0.5s ease;
    box-shadow: 0 0 8px var(--cs-energy);
}

/* ── Main Layout ── */
.cs-main {
    display: grid;
    grid-template-columns: 240px 1fr 300px;
    height: calc(100vh - 49px);
    overflow: hidden;
}

/* ── Component Palette ── */
.cs-palette {
    background: var(--cs-surface);
    border-right: 1px solid var(--cs-border);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}
.cs-palette-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 0.8rem;
}
.cs-palette-title {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--cs-text-muted);
    margin-bottom: 0.5rem;
    padding: 0 0.3rem;
}
.cs-palette-group { margin-bottom: 1rem; }
.cs-component {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.45rem 0.6rem;
    border-radius: 8px;
    cursor: grab;
    transition: all 0.2s;
    font-size: 0.82rem;
    user-select: none;
}
.cs-component:hover { background: var(--cs-surface-2); }
.cs-component:active { cursor: grabbing; }
.cs-component-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--cs-surface-2);
    border-radius: 6px;
    font-size: 1.1rem;
    border: 1px solid var(--cs-border);
    flex-shrink: 0;
}
.cs-component-info { flex: 1; min-width: 0; }
.cs-component-name { font-weight: 600; font-size: 0.78rem; }
.cs-component-val { font-size: 0.68rem; color: var(--cs-text-muted); }
.cs-badge {
    display: inline-block;
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.cs-badge-new { background: rgba(0,255,136,0.15); color: var(--cs-energy); }
.cs-badge-zpe { background: rgba(0,255,204,0.15); color: var(--cs-zpe); }
.cs-badge-tesla { background: rgba(255,51,102,0.15); color: var(--cs-tesla); }

/* ── Palette Tabs ── */
.cs-palette-tabs {
    display: flex;
    border-bottom: 1px solid var(--cs-border);
    padding: 0;
    background: var(--cs-surface-2);
    flex-shrink: 0;
}
.cs-palette-tab {
    flex: 1;
    padding: 0.6rem 0.3rem;
    text-align: center;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    color: var(--cs-text-muted);
}
.cs-palette-tab:hover { color: var(--cs-text); background: rgba(0,212,255,0.04); }
.cs-palette-tab.active { color: var(--cs-accent); border-bottom-color: var(--cs-accent); }
.cs-palette-tab.tab-zpe.active { color: var(--cs-zpe); border-bottom-color: var(--cs-zpe); }
.cs-palette-panel { display: none; }
.cs-palette-panel.active { display: block; }

/* ── Canvas Area ── */
.cs-canvas-wrap {
    position: relative;
    background: var(--cs-bg);
    overflow: hidden;
}
.cs-canvas-wrap canvas#circuitCanvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
.cs-canvas-grid {
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.3;
    background-image:
        linear-gradient(rgba(0,212,255,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,212,255,0.05) 1px, transparent 1px);
    background-size: 20px 20px;
}
.cs-canvas-overlay {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.3rem;
    background: var(--cs-surface);
    border: 1px solid var(--cs-border);
    border-radius: 10px;
    padding: 0.3rem;
    z-index: 10;
}
.cs-canvas-overlay .cs-btn { border: none; background: transparent; }
.cs-canvas-overlay .cs-btn:hover { background: var(--cs-surface-2); }
.cs-canvas-overlay .cs-btn.active { background: rgba(0,212,255,0.15); color: var(--cs-accent); }
.cs-drop-hint {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 1rem;
    pointer-events: none;
    opacity: 1;
    transition: opacity 0.3s;
}
.cs-drop-hint.hidden { opacity: 0; }
.cs-drop-hint-icon { font-size: 3rem; opacity: 0.3; }
.cs-drop-hint-text {
    font-size: 0.9rem;
    color: var(--cs-text-muted);
    text-align: center;
    line-height: 1.6;
}
.cs-drop-hint-text kbd {
    display: inline-block;
    padding: 0.15rem 0.4rem;
    background: var(--cs-surface-2);
    border: 1px solid var(--cs-border);
    border-radius: 4px;
    font-size: 0.75rem;
    font-family: monospace;
}

/* ── Properties Panel ── */
.cs-properties {
    background: var(--cs-surface);
    border-left: 1px solid var(--cs-border);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    padding: 0.8rem;
}
.cs-prop-section {
    background: var(--cs-surface-2);
    border: 1px solid var(--cs-border);
    border-radius: var(--cs-radius);
    padding: 0.8rem;
}
.cs-prop-title {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--cs-text-muted);
    margin-bottom: 0.5rem;
}
.cs-prop-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
    font-size: 0.82rem;
}
.cs-prop-label { color: var(--cs-text-muted); }
.cs-prop-value { font-weight: 600; font-family: 'JetBrains Mono', monospace; }
.cs-prop-value.positive { color: var(--cs-energy); }
.cs-prop-value.warning { color: var(--cs-warn); }
.cs-prop-value.danger { color: var(--cs-danger); }
.cs-prop-input {
    width: 80px;
    padding: 0.25rem 0.4rem;
    background: var(--cs-bg);
    border: 1px solid var(--cs-border);
    border-radius: 6px;
    color: var(--cs-text);
    font-size: 0.8rem;
    font-family: 'JetBrains Mono', monospace;
    text-align: right;
}
.cs-prop-input:focus { outline: none; border-color: var(--cs-accent); }
.cs-prop-select {
    padding: 0.25rem 0.4rem;
    background: var(--cs-bg);
    border: 1px solid var(--cs-border);
    border-radius: 6px;
    color: var(--cs-text);
    font-size: 0.8rem;
}

/* ── Simulation Readout ── */
.cs-readout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.4rem;
}
.cs-readout-item {
    text-align: center;
    padding: 0.35rem;
    background: var(--cs-bg);
    border-radius: 8px;
    border: 1px solid var(--cs-border);
}
.cs-readout-val {
    font-size: 1rem;
    font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
    color: var(--cs-accent);
}
.cs-readout-label {
    font-size: 0.6rem;
    color: var(--cs-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ── Oscilloscope ── */
.cs-oscilloscope {
    border: 1px solid rgba(0,255,136,0.2);
    border-radius: 8px;
    overflow: hidden;
    background: #0a0a0f;
}
.cs-oscilloscope canvas {
    width: 100%;
    height: 140px;
    display: block;
}
.cs-osc-controls {
    display: flex;
    gap: 0.3rem;
    padding: 0.3rem;
    background: rgba(0,0,0,0.3);
    border-top: 1px solid rgba(0,255,136,0.1);
}

/* ── Formula Display ── */
.cs-formula-panel {
    max-height: 200px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.cs-formula-line {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.72rem;
    padding: 0.25rem 0.5rem;
    background: rgba(0,212,255,0.04);
    border-radius: 4px;
    border-left: 2px solid var(--cs-accent);
    line-height: 1.4;
}
.cs-formula-val { color: var(--cs-accent); font-weight: 600; }
.cs-formula-unit { color: var(--cs-energy); }
.cs-formula-sym { color: var(--cs-warn); }
.cs-formula-warn {
    font-size: 0.72rem;
    padding: 0.25rem 0.5rem;
    background: rgba(255,184,0,0.08);
    border-radius: 4px;
    border-left: 2px solid var(--cs-warn);
    color: var(--cs-warn);
}
.cs-formula-empty {
    font-size: 0.72rem;
    color: var(--cs-text-muted);
    text-align: center;
    padding: 1rem 0;
}

/* ── Prebuilt Circuits ── */
.cs-prebuilt {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.cs-prebuilt-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem;
    background: var(--cs-bg);
    border: 1px solid var(--cs-border);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.78rem;
}
.cs-prebuilt-item:hover { border-color: var(--cs-accent); background: rgba(0,212,255,0.05); }
.cs-prebuilt-item.zpe-item { border-color: rgba(0,255,204,0.15); }
.cs-prebuilt-item.zpe-item:hover { border-color: var(--cs-zpe); background: rgba(0,255,204,0.05); }
.cs-prebuilt-item.tesla-item { border-color: rgba(255,51,102,0.15); }
.cs-prebuilt-item.tesla-item:hover { border-color: var(--cs-tesla); background: rgba(255,51,102,0.05); }
.cs-prebuilt-icon { font-size: 1.1rem; flex-shrink: 0; }

/* ── ZPE Info Card ── */
.cs-zpe-card {
    background: linear-gradient(135deg, rgba(0,255,204,0.06), rgba(0,212,255,0.06));
    border: 1px solid rgba(0,255,204,0.15);
    border-radius: var(--cs-radius);
    padding: 0.7rem;
}
.cs-zpe-card h4 {
    font-size: 0.75rem;
    color: var(--cs-zpe);
    margin-bottom: 0.3rem;
}
.cs-zpe-card p {
    font-size: 0.7rem;
    color: var(--cs-text-muted);
    line-height: 1.4;
}
.cs-researcher {
    font-size: 0.65rem;
    color: var(--cs-warn);
    font-style: italic;
}

/* ── Exclusive Banner ── */
.cs-exclusive-banner {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.6rem 0.8rem;
    background: linear-gradient(135deg, rgba(0,212,255,0.08), rgba(125,0,255,0.08));
    border: 1px solid rgba(0,212,255,0.15);
    border-radius: 10px;
    margin: 0.8rem;
    flex-shrink: 0;
}
.cs-exclusive-banner-text { font-size: 0.75rem; line-height: 1.4; }
.cs-exclusive-banner-text strong { color: var(--cs-accent); }
.cs-exclusive-banner-text a { color: var(--cs-accent); text-decoration: none; }

/* ── Right Panel Tabs ── */
.cs-right-tabs {
    display: flex;
    border-bottom: 1px solid var(--cs-border);
    background: var(--cs-surface-2);
    flex-shrink: 0;
}
.cs-right-tab {
    flex: 1;
    padding: 0.5rem 0.3rem;
    text-align: center;
    font-size: 0.68rem;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    color: var(--cs-text-muted);
}
.cs-right-tab:hover { color: var(--cs-text); }
.cs-right-tab.active { color: var(--cs-accent); border-bottom-color: var(--cs-accent); }
.cs-right-panel { display: none; }
.cs-right-panel.active { display: flex; flex-direction: column; gap: 0.8rem; }

/* ── Responsive ── */
@media (max-width: 1200px) {
    .cs-main { grid-template-columns: 200px 1fr 260px; }
}
@media (max-width: 1024px) {
    .cs-main { grid-template-columns: 1fr; grid-template-rows: auto 1fr auto; }
    .cs-palette {
        border-right: none;
        border-bottom: 1px solid var(--cs-border);
        max-height: 120px;
    }
    .cs-palette-scroll {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding: 0.5rem;
    }
    .cs-palette-group { display: flex; gap: 0.3rem; margin-bottom: 0; }
    .cs-palette-title { display: none; }
    .cs-component { flex-direction: column; padding: 0.4rem; min-width: 60px; }
    .cs-component-val { display: none; }
    .cs-properties {
        flex-direction: row;
        overflow-x: auto;
        border-left: none;
        border-top: 1px solid var(--cs-border);
        max-height: 220px;
    }
    .cs-prop-section { min-width: 200px; }
}

/* ── Animations ── */
@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 5px rgba(0,212,255,0.2); }
    50% { box-shadow: 0 0 20px rgba(0,212,255,0.5); }
}
@keyframes tesla-glow {
    0%, 100% { box-shadow: 0 0 5px rgba(255,51,102,0.2); }
    50% { box-shadow: 0 0 25px rgba(255,51,102,0.6); }
}
@keyframes zpe-glow {
    0%, 100% { box-shadow: 0 0 5px rgba(0,255,204,0.2); }
    50% { box-shadow: 0 0 20px rgba(0,255,204,0.5); }
}
.cs-flowing { animation: pulse-glow 2s ease-in-out infinite; }
.cs-tesla-active { animation: tesla-glow 1.5s ease-in-out infinite; }
.cs-zpe-active { animation: zpe-glow 2.5s ease-in-out infinite; }

/* ── v3.0: Context Menu ── */
.cs-context-menu {
    position: fixed;
    z-index: 1000;
    background: var(--cs-surface);
    border: 1px solid var(--cs-border);
    border-radius: 10px;
    padding: 0.3rem 0;
    min-width: 160px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    display: none;
}
.cs-context-menu.show { display: block; }
.cs-context-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.5rem 0.8rem;
    font-size: 0.8rem;
    cursor: pointer;
    color: var(--cs-text);
    transition: background 0.15s;
}
.cs-context-item:hover { background: rgba(0,212,255,0.08); }
.cs-context-item .ctx-key {
    margin-left: auto;
    font-size: 0.65rem;
    color: var(--cs-text-muted);
    font-family: monospace;
}
.cs-context-sep {
    height: 1px;
    background: var(--cs-border);
    margin: 0.2rem 0.5rem;
}

/* ── v3.0: Multi-select highlight ── */
.cs-select-box {
    position: absolute;
    border: 1px dashed rgba(0,212,255,0.5);
    background: rgba(0,212,255,0.06);
    pointer-events: none;
    z-index: 5;
}

/* ── v3.0: Component Search ── */
.cs-palette-search {
    display: flex;
    padding: 0.5rem 0.8rem;
    border-bottom: 1px solid var(--cs-border);
    flex-shrink: 0;
}
.cs-palette-search input {
    width: 100%;
    padding: 0.35rem 0.6rem;
    background: var(--cs-bg);
    border: 1px solid var(--cs-border);
    border-radius: 8px;
    color: var(--cs-text);
    font-size: 0.8rem;
    outline: none;
}
.cs-palette-search input:focus { border-color: var(--cs-accent); }
.cs-palette-search input::placeholder { color: var(--cs-text-muted); }

/* ── v3.0: Keyboard Help Overlay ── */
.cs-kb-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    background: rgba(0,0,0,0.8);
    display: none;
    align-items: center;
    justify-content: center;
}
.cs-kb-overlay.show { display: flex; }
.cs-kb-box {
    background: var(--cs-surface);
    border: 1px solid var(--cs-border);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    max-width: 520px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}
.cs-kb-box h3 { color: var(--cs-accent); margin-bottom: 1rem; font-size: 1rem; }
.cs-kb-row {
    display: flex;
    justify-content: space-between;
    padding: 0.3rem 0;
    font-size: 0.82rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.cs-kb-row kbd {
    display: inline-block;
    padding: 0.15rem 0.45rem;
    background: var(--cs-surface-2);
    border: 1px solid var(--cs-border);
    border-radius: 4px;
    font-size: 0.75rem;
    font-family: monospace;
    color: var(--cs-accent);
    min-width: 24px;
    text-align: center;
}

/* ── v3.0: Junction dots ── */
.cs-junction-dot {
    fill: #00D4FF;
    stroke: none;
}

/* ── v3.0: Bode Plot Canvas ── */
.cs-bode-wrap {
    border: 1px solid rgba(125,0,255,0.2);
    border-radius: 8px;
    overflow: hidden;
    background: #0a0a14;
}
.cs-bode-wrap canvas {
    width: 100%;
    height: 200px;
    display: block;
}

/* ── v3.0: Scope mode buttons ── */
.cs-scope-modes {
    display: flex;
    gap: 0.3rem;
    padding: 0.3rem;
    background: rgba(0,0,0,0.2);
    border-top: 1px solid rgba(0,255,136,0.08);
}
.cs-scope-mode-btn {
    padding: 0.2rem 0.5rem;
    font-size: 0.68rem;
    border-radius: 6px;
    border: 1px solid var(--cs-border);
    background: transparent;
    color: var(--cs-text-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.cs-scope-mode-btn:hover { border-color: var(--cs-accent); color: var(--cs-text); }
.cs-scope-mode-btn.active { background: rgba(0,212,255,0.15); color: var(--cs-accent); border-color: var(--cs-accent); }

/* ── v3.0: PNG Export button style ── */
.cs-btn-export { background: linear-gradient(135deg, #7D00FF, #00D4FF); border-color: transparent; color: #fff; font-weight: 600; }

/* ── v3.0: Touch friendly ── */
@media (pointer: coarse) {
    .cs-component { padding: 0.6rem; min-height: 44px; }
    .cs-btn { padding: 0.5rem 1rem; min-height: 44px; }
    .cs-canvas-overlay .cs-btn { min-width: 44px; min-height: 44px; }
}
</style>

<div class="cs-page">

    <!-- Top Bar -->
    <div class="cs-topbar">
        <a href="/" class="cs-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
            GoSiteMe
        </a>
        <span class="cs-topbar-title">Circuit Simulator <span style="color:var(--cs-accent);font-size:0.7rem;">v5.0</span> <span class="cs-badge cs-badge-new">SPICE</span> <span class="cs-badge cs-badge-new">MNA</span> <span class="cs-badge cs-badge-zpe">ZPE</span> <span class="cs-badge cs-badge-new">FFT</span></span>

        <div class="cs-topbar-actions">
            <div class="cs-energy-bar" title="Ecosystem Energy">
                <span class="cs-energy-icon">⚡</span>
                <span id="energyLabel">0 eV</span>
                <div class="cs-energy-level"><div class="cs-energy-fill" id="energyFill"></div></div>
            </div>
            <button class="cs-btn cs-btn-sm" onclick="csApp.undo()" title="Undo (Ctrl+Z)">↩</button>
            <button class="cs-btn cs-btn-sm" onclick="csApp.redo()" title="Redo (Ctrl+Y)">↪</button>
            <button class="cs-btn cs-btn-sm" onclick="csApp.clearAll()" title="Clear canvas">🗑️</button>
            <button class="cs-btn cs-btn-sm" onclick="csApp.exportCircuit()" title="Export JSON">📤</button>
            <button class="cs-btn cs-btn-sm" onclick="csApp.importCircuit()" title="Import JSON">📥</button>
            <button class="cs-btn cs-btn-sm" onclick="csApp.shareCircuit()" title="Share link">🔗</button>
            <button class="cs-btn cs-btn-sm" onclick="csApp.exportNetlist()" title="Export SPICE Netlist">📋 SPICE</button>
            <button class="cs-btn cs-btn-sm cs-btn-export" onclick="csApp.exportPNG()" title="Export PNG">🖼️ PNG</button>
            <button class="cs-btn cs-btn-energy" onclick="csApp.toggleSimulation()" id="simToggle" title="Run simulation (Space)">▶ Simulate</button>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="cs-main">

        <!-- Component Palette -->
        <div class="cs-palette" id="palette">
            <div class="cs-palette-tabs">
                <div class="cs-palette-tab active" onclick="csApp.switchPaletteTab('standard')">Standard</div>
                <div class="cs-palette-tab tab-zpe" onclick="csApp.switchPaletteTab('advanced')">Advanced</div>
                <div class="cs-palette-tab tab-zpe" onclick="csApp.switchPaletteTab('zpe')">ZPE</div>
            </div>
            <div class="cs-palette-search">
                <input type="text" id="paletteSearch" placeholder="🔍 Search components..." oninput="csApp.filterPalette(this.value)">
            </div>
            <div class="cs-palette-scroll">
                <!-- STANDARD TAB -->
                <div class="cs-palette-panel active" id="paletteStandard">
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">⚡ Sources</div>
                        <div class="cs-component" draggable="true" data-type="battery" data-value="9">
                            <div class="cs-component-icon">🔋</div>
                            <div class="cs-component-info"><div class="cs-component-name">Battery</div><div class="cs-component-val">9V DC</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="ac_source" data-value="120">
                            <div class="cs-component-icon">〰️</div>
                            <div class="cs-component-info"><div class="cs-component-name">AC Source</div><div class="cs-component-val">120V 60Hz</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="signal_generator" data-value="5">
                            <div class="cs-component-icon" style="color:#FFAA00;">⎍</div>
                            <div class="cs-component-info"><div class="cs-component-name">Signal Gen</div><div class="cs-component-val">5V 1kHz</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="ground" data-value="0">
                            <div class="cs-component-icon">⏚</div>
                            <div class="cs-component-info"><div class="cs-component-name">Ground</div><div class="cs-component-val">0V Ref</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">🔧 Passive</div>
                        <div class="cs-component" draggable="true" data-type="resistor" data-value="1000">
                            <div class="cs-component-icon" style="color:#FFB800;">Ω</div>
                            <div class="cs-component-info"><div class="cs-component-name">Resistor</div><div class="cs-component-val">1kΩ</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="capacitor" data-value="0.0001">
                            <div class="cs-component-icon" style="color:#00D4FF;">⫠</div>
                            <div class="cs-component-info"><div class="cs-component-name">Capacitor</div><div class="cs-component-val">100µF</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="inductor" data-value="0.01">
                            <div class="cs-component-icon" style="color:#7D00FF;">⌇</div>
                            <div class="cs-component-info"><div class="cs-component-name">Inductor</div><div class="cs-component-val">10mH</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="potentiometer" data-value="10000">
                            <div class="cs-component-icon" style="color:#FF8800;">◎</div>
                            <div class="cs-component-info"><div class="cs-component-name">Pot</div><div class="cs-component-val">10kΩ</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="thermistor" data-value="10000">
                            <div class="cs-component-icon" style="color:#FF8844;">🌡️</div>
                            <div class="cs-component-info"><div class="cs-component-name">Thermistor</div><div class="cs-component-val">10kΩ NTC</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">💡 Output</div>
                        <div class="cs-component" draggable="true" data-type="led" data-value="2">
                            <div class="cs-component-icon" style="color:#FF3366;">💡</div>
                            <div class="cs-component-info"><div class="cs-component-name">LED</div><div class="cs-component-val">2V Red</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="bulb" data-value="60">
                            <div class="cs-component-icon" style="color:#FFD700;">💡</div>
                            <div class="cs-component-info"><div class="cs-component-name">Bulb</div><div class="cs-component-val">60W</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="motor" data-value="12">
                            <div class="cs-component-icon">⚙️</div>
                            <div class="cs-component-info"><div class="cs-component-name">DC Motor</div><div class="cs-component-val">12V</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="buzzer" data-value="5">
                            <div class="cs-component-icon">🔊</div>
                            <div class="cs-component-info"><div class="cs-component-name">Buzzer</div><div class="cs-component-val">5V</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="speaker" data-value="8">
                            <div class="cs-component-icon" style="color:#FF6600;">🔈</div>
                            <div class="cs-component-info"><div class="cs-component-name">Speaker</div><div class="cs-component-val">8Ω</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">🔀 Control</div>
                        <div class="cs-component" draggable="true" data-type="switch" data-value="1">
                            <div class="cs-component-icon">🔲</div>
                            <div class="cs-component-info"><div class="cs-component-name">Switch</div><div class="cs-component-val">SPST</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="push_button" data-value="1">
                            <div class="cs-component-icon">⊡</div>
                            <div class="cs-component-info"><div class="cs-component-name">Push Button</div><div class="cs-component-val">Momentary</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="diode" data-value="0.7">
                            <div class="cs-component-icon" style="color:#FF6600;">▷</div>
                            <div class="cs-component-info"><div class="cs-component-name">Diode</div><div class="cs-component-val">0.7V</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="zener_diode" data-value="5.1">
                            <div class="cs-component-icon" style="color:#FF00AA;">⊳</div>
                            <div class="cs-component-info"><div class="cs-component-name">Zener</div><div class="cs-component-val">5.1V</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="fuse" data-value="1">
                            <div class="cs-component-icon" style="color:#FF3366;">⎓</div>
                            <div class="cs-component-info"><div class="cs-component-name">Fuse</div><div class="cs-component-val">1A</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="relay" data-value="12">
                            <div class="cs-component-icon" style="color:#FF9900;">⎔</div>
                            <div class="cs-component-info"><div class="cs-component-name">Relay</div><div class="cs-component-val">12V</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">🔌 Semiconductors</div>
                        <div class="cs-component" draggable="true" data-type="npn_transistor" data-value="100">
                            <div class="cs-component-icon" style="color:#00FF88;">⊳</div>
                            <div class="cs-component-info"><div class="cs-component-name">NPN</div><div class="cs-component-val">β=100</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="pnp_transistor" data-value="100">
                            <div class="cs-component-icon" style="color:#00AAFF;">⊲</div>
                            <div class="cs-component-info"><div class="cs-component-name">PNP</div><div class="cs-component-val">β=100</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="mosfet_n" data-value="10">
                            <div class="cs-component-icon" style="color:#00CC66;">⊳</div>
                            <div class="cs-component-info"><div class="cs-component-name">N-MOSFET</div><div class="cs-component-val">10A</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="op_amp" data-value="100000">
                            <div class="cs-component-icon" style="color:#FFD700;">△</div>
                            <div class="cs-component-info"><div class="cs-component-name">Op-Amp</div><div class="cs-component-val">100k</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="ic_555" data-value="0">
                            <div class="cs-component-icon" style="color:#FFD700;">⊞</div>
                            <div class="cs-component-info"><div class="cs-component-name">555 Timer</div><div class="cs-component-val">IC</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="voltage_regulator" data-value="5">
                            <div class="cs-component-icon" style="color:#00DD00;">▬</div>
                            <div class="cs-component-info"><div class="cs-component-name">V-Reg</div><div class="cs-component-val">5V</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="mosfet_p" data-value="10">
                            <div class="cs-component-icon" style="color:#33CC99;">⊲</div>
                            <div class="cs-component-info"><div class="cs-component-name">P-MOSFET</div><div class="cs-component-val">10A</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="jfet_n" data-value="0.01">
                            <div class="cs-component-icon" style="color:#44BB88;">⊳</div>
                            <div class="cs-component-info"><div class="cs-component-name">N-JFET</div><div class="cs-component-val">10mA</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="jfet_p" data-value="0.01">
                            <div class="cs-component-icon" style="color:#55AA77;">⊲</div>
                            <div class="cs-component-info"><div class="cs-component-name">P-JFET</div><div class="cs-component-val">10mA</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="igbt" data-value="20">
                            <div class="cs-component-icon" style="color:#FF8844;">⊳</div>
                            <div class="cs-component-info"><div class="cs-component-name">IGBT</div><div class="cs-component-val">20A</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="schottky_diode" data-value="1">
                            <div class="cs-component-icon" style="color:#FFCC33;">▸</div>
                            <div class="cs-component-info"><div class="cs-component-name">Schottky</div><div class="cs-component-val">Vf≈0.25V</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="photodiode" data-value="0.5">
                            <div class="cs-component-icon" style="color:#88EEFF;">☀▸</div>
                            <div class="cs-component-info"><div class="cs-component-name">Photodiode</div><div class="cs-component-val">0.5 W/m²</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="current_source" data-value="0.01">
                            <div class="cs-component-icon" style="color:#FF6688;">⊕</div>
                            <div class="cs-component-info"><div class="cs-component-name">I Source</div><div class="cs-component-val">10mA</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">📏 Measurement</div>
                        <div class="cs-component" draggable="true" data-type="voltmeter" data-value="0">
                            <div class="cs-component-icon" style="color:#00D4FF;">Ⓥ</div>
                            <div class="cs-component-info"><div class="cs-component-name">Voltmeter</div><div class="cs-component-val">V</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="ammeter" data-value="0">
                            <div class="cs-component-icon" style="color:#FF8800;">Ⓐ</div>
                            <div class="cs-component-info"><div class="cs-component-name">Ammeter</div><div class="cs-component-val">A</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="wattmeter" data-value="0">
                            <div class="cs-component-icon" style="color:#FFCC00;">Ⓦ</div>
                            <div class="cs-component-info"><div class="cs-component-name">Wattmeter</div><div class="cs-component-val">W</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="oscilloscope" data-value="0">
                            <div class="cs-component-icon" style="color:#00FF88;">〰</div>
                            <div class="cs-component-info"><div class="cs-component-name">Scope Probe</div><div class="cs-component-val">Waveform</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="wire" data-value="0">
                            <div class="cs-component-icon">━</div>
                            <div class="cs-component-info"><div class="cs-component-name">Wire</div><div class="cs-component-val">Connector</div></div>
                        </div>
                    </div>
                </div>

                <!-- ADVANCED TAB -->
                <div class="cs-palette-panel" id="paletteAdvanced">
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">🔄 Transformers</div>
                        <div class="cs-component" draggable="true" data-type="transformer" data-value="100">
                            <div class="cs-component-icon" style="color:#FFD700;">⊣⊢</div>
                            <div class="cs-component-info"><div class="cs-component-name">Transformer</div><div class="cs-component-val">100:1000 turns</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="toroidal_inductor" data-value="0.5">
                            <div class="cs-component-icon" style="color:#6600CC;">◉</div>
                            <div class="cs-component-info"><div class="cs-component-name">Toroid</div><div class="cs-component-val">500mH</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">⚡ High Voltage</div>
                        <div class="cs-component" draggable="true" data-type="high_voltage_cap" data-value="0.000000001">
                            <div class="cs-component-icon" style="color:#FF4444;">⫠</div>
                            <div class="cs-component-info"><div class="cs-component-name">HV Capacitor</div><div class="cs-component-val">1nF 40kV</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="leyden_jar" data-value="0.000000002">
                            <div class="cs-component-icon" style="color:#FFAA00;">🏺</div>
                            <div class="cs-component-info"><div class="cs-component-name">Leyden Jar</div><div class="cs-component-val">2nF</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="spark_gap" data-value="15000">
                            <div class="cs-component-icon" style="color:#FF8800;">⚡</div>
                            <div class="cs-component-info"><div class="cs-component-name">Spark Gap</div><div class="cs-component-val">15kV</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="thyristor" data-value="25">
                            <div class="cs-component-icon" style="color:#FF3300;">⊳</div>
                            <div class="cs-component-info"><div class="cs-component-name">SCR</div><div class="cs-component-val">25A</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">📡 RF / Signal</div>
                        <div class="cs-component" draggable="true" data-type="rf_generator" data-value="50">
                            <div class="cs-component-icon" style="color:#FF00FF;">📡</div>
                            <div class="cs-component-info"><div class="cs-component-name">RF Source</div><div class="cs-component-val">50V 1MHz</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="crystal_osc" data-value="32768">
                            <div class="cs-component-icon" style="color:#66FFCC;">⬡</div>
                            <div class="cs-component-info"><div class="cs-component-name">Crystal Osc</div><div class="cs-component-val">32.768kHz</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="varactor" data-value="0.00000001">
                            <div class="cs-component-icon" style="color:#CC66FF;">◁⫠</div>
                            <div class="cs-component-info"><div class="cs-component-name">Varactor</div><div class="cs-component-val">10nF</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="reed_switch" data-value="1">
                            <div class="cs-component-icon" style="color:#00FFAA;">⌇</div>
                            <div class="cs-component-info"><div class="cs-component-name">Reed Switch</div><div class="cs-component-val">Magnetic</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">🔬 Analysis</div>
                        <div class="cs-component" draggable="true" data-type="em_field_probe" data-value="0">
                            <div class="cs-component-icon" style="color:#FF88FF;">📊</div>
                            <div class="cs-component-info"><div class="cs-component-name">EM Probe</div><div class="cs-component-val">Field sensor</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="spectrum_analyzer" data-value="0">
                            <div class="cs-component-icon" style="color:#FF66CC;">📈</div>
                            <div class="cs-component-info"><div class="cs-component-name">Spectrum</div><div class="cs-component-val">FFT analyzer</div></div>
                        </div>
                    </div>
                </div>

                <!-- ZPE TAB -->
                <div class="cs-palette-panel" id="paletteZpe">
                    <div class="cs-zpe-card" style="margin-bottom:1rem;">
                        <h4>⚛ Zero-Point Energy Research</h4>
                        <p>Components for ZPE, Tesla, Hutchison, Bedini, Don Smith, and advanced electromagnetic experiments.</p>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">⚡ Tesla / Coils</div>
                        <div class="cs-component" draggable="true" data-type="tesla_primary" data-value="0.0001">
                            <div class="cs-component-icon" style="color:#FF3366;">⊣</div>
                            <div class="cs-component-info"><div class="cs-component-name">Tesla Primary</div><div class="cs-component-val">100µH</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="tesla_secondary" data-value="0.025">
                            <div class="cs-component-icon" style="color:#FF6699;">⊢</div>
                            <div class="cs-component-info"><div class="cs-component-name">Tesla Secondary</div><div class="cs-component-val">25mH</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="top_load" data-value="0.2">
                            <div class="cs-component-icon" style="color:#FFD700;">⊙</div>
                            <div class="cs-component-info"><div class="cs-component-name">Top Load</div><div class="cs-component-val">20cm sphere</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="bifilar_coil" data-value="0.1">
                            <div class="cs-component-icon" style="color:#CC00FF;">⊜</div>
                            <div class="cs-component-info"><div class="cs-component-name">Bifilar Coil</div><div class="cs-component-val">100mH</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="caduceus_coil" data-value="0.5">
                            <div class="cs-component-icon" style="color:#9933FF;">☤</div>
                            <div class="cs-component-info"><div class="cs-component-name">Caduceus Coil</div><div class="cs-component-val">500mH</div></div>
                        </div>
                    </div>
                    <div class="cs-palette-group">
                        <div class="cs-palette-title">⚛ ZPE / Quantum</div>
                        <div class="cs-component" draggable="true" data-type="casimir_plates" data-value="0.0000001">
                            <div class="cs-component-icon" style="color:#00FFCC;">≡</div>
                            <div class="cs-component-info"><div class="cs-component-name">Casimir Plates</div><div class="cs-component-val">100nm gap</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="crystal_cell" data-value="0.4">
                            <div class="cs-component-icon" style="color:#88FF00;">💎</div>
                            <div class="cs-component-info"><div class="cs-component-name">Crystal Cell</div><div class="cs-component-val">400mV</div></div>
                        </div>
                        <div class="cs-component" draggable="true" data-type="schumann_antenna" data-value="1">
                            <div class="cs-component-icon" style="color:#00DDFF;">🌍</div>
                            <div class="cs-component-info"><div class="cs-component-name">Schumann Ant.</div><div class="cs-component-val">7.83Hz n=1</div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exclusive Banner at bottom -->
            <div class="cs-exclusive-banner">
                <span style="font-size:1.2rem;">⚡</span>
                <div class="cs-exclusive-banner-text">
                    <strong>GoSiteMe Exclusive</strong><br>
                    <a href="/open-source/">Explore All Tools →</a>
                </div>
            </div>
        </div>

        <!-- Canvas -->
        <div class="cs-canvas-wrap" id="canvasWrap">
            <div class="cs-canvas-grid"></div>
            <canvas id="circuitCanvas"></canvas>
            <div class="cs-drop-hint" id="dropHint">
                <div class="cs-drop-hint-icon">⚡</div>
                <div class="cs-drop-hint-text">
                    Drag components here to build your circuit<br>
                    <kbd>W</kbd> Wire &nbsp; <kbd>Del</kbd> Delete &nbsp; <kbd>R</kbd> Rotate &nbsp; <kbd>Space</kbd> Simulate<br>
                    <span style="color:var(--cs-zpe);font-size:0.75rem;">Try the ZPE tab for Tesla coils & Hutchison experiments</span>
                </div>
            </div>
            <div class="cs-canvas-overlay">
                <button class="cs-btn active" onclick="csApp.setMode('select')" id="modeSelect" title="Select (V)">🖱️</button>
                <button class="cs-btn" onclick="csApp.setMode('wire')" id="modeWire" title="Wire (W)">🔌</button>
                <button class="cs-btn" onclick="csApp.setMode('delete')" id="modeDelete" title="Delete (Del)">🗑️</button>
                <button class="cs-btn" onclick="csApp.zoomIn()" title="Zoom In">🔍+</button>
                <button class="cs-btn" onclick="csApp.zoomOut()" title="Zoom Out">🔍−</button>
                <button class="cs-btn" onclick="csApp.fitView()" title="Fit View">⊞</button>
                <button class="cs-btn" onclick="csApp.toggleNodeVoltages()" id="modeNodeV" title="Show Node Voltages">V⃗</button>
            </div>
        </div>

        <!-- Properties / Analysis Panel -->
        <div class="cs-properties" style="padding:0; gap:0;">
            <div class="cs-right-tabs">
                <div class="cs-right-tab active" onclick="csApp.switchRightTab('sim')">Sim</div>
                <div class="cs-right-tab" onclick="csApp.switchRightTab('scope')">Scope</div>
                <div class="cs-right-tab" onclick="csApp.switchRightTab('bode')">Bode</div>
                <div class="cs-right-tab" onclick="csApp.switchRightTab('formulas')">Formulas</div>
                <div class="cs-right-tab" onclick="csApp.switchRightTab('circuits')">Circuits</div>
            </div>
            <div style="flex:1; overflow-y:auto; padding:0.8rem; display:flex; flex-direction:column; gap:0.8rem;">

                <!-- SIM TAB -->
                <div class="cs-right-panel active" id="rightSim">
                    <!-- Readout -->
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">📊 Simulation</div>
                        <div class="cs-readout">
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readVoltage">0.00</div>
                                <div class="cs-readout-label">Volts</div>
                            </div>
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readCurrent">0.00</div>
                                <div class="cs-readout-label">Amps</div>
                            </div>
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readPower">0.00</div>
                                <div class="cs-readout-label">Watts</div>
                            </div>
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readResistance">0.00</div>
                                <div class="cs-readout-label">Ohms</div>
                            </div>
                        </div>
                        <!-- Additional readouts for AC -->
                        <div class="cs-readout" style="margin-top:0.4rem;" id="acReadouts" style="display:none;">
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readFreq">—</div>
                                <div class="cs-readout-label">Freq (Hz)</div>
                            </div>
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readPhase">—</div>
                                <div class="cs-readout-label">Phase °</div>
                            </div>
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readResonance">—</div>
                                <div class="cs-readout-label">f₀ Res</div>
                            </div>
                            <div class="cs-readout-item">
                                <div class="cs-readout-val" id="readQFactor">—</div>
                                <div class="cs-readout-label">Q Factor</div>
                            </div>
                        </div>
                    </div>

                    <!-- v4.0: MNA Solver Stats -->
                    <div class="cs-prop-section" id="mnaSolverStats" style="display:none; background: linear-gradient(135deg, rgba(0,212,255,0.06), rgba(125,0,255,0.06)); border-color: rgba(0,212,255,0.2);">
                        <div class="cs-prop-title" style="color:var(--cs-accent);">🧮 MNA Solver v5.0</div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Matrix</span>
                            <span class="cs-prop-value" id="mnaMatrixSize">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">N-R Iterations</span>
                            <span class="cs-prop-value" id="mnaNRIter">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Converged</span>
                            <span class="cs-prop-value" id="mnaConverged" style="color:var(--cs-energy);">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Timestep</span>
                            <span class="cs-prop-value" id="mnaTimestep">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Integration</span>
                            <span class="cs-prop-value" style="color:var(--cs-accent);">Trapezoidal</span>
                        </div>
                    </div>

                    <!-- Component Properties -->
                    <div class="cs-prop-section" id="selectedProps" style="display:none;">
                        <div class="cs-prop-title">🔧 Component</div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Type</span>
                            <span class="cs-prop-value" id="propType">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Value</span>
                            <input class="cs-prop-input" id="propValue" type="number" step="any" onchange="csApp.updateSelectedValue(this.value)">
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Unit</span>
                            <span class="cs-prop-value" id="propUnit">—</span>
                        </div>
                        <div class="cs-prop-row" id="propFreqRow" style="display:none;">
                            <span class="cs-prop-label">Freq</span>
                            <input class="cs-prop-input" id="propFreq" type="number" step="any" onchange="csApp.updateSelectedFreq(this.value)">
                        </div>
                        <div class="cs-prop-row" id="propTurnsRow" style="display:none;">
                            <span class="cs-prop-label">Sec. Turns</span>
                            <input class="cs-prop-input" id="propTurns" type="number" step="1" onchange="csApp.updateSelectedTurns(this.value)">
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Rotation</span>
                            <select class="cs-prop-select" id="propRotation" onchange="csApp.updateSelectedRotation(this.value)">
                                <option value="0">0°</option>
                                <option value="90">90°</option>
                                <option value="180">180°</option>
                                <option value="270">270°</option>
                            </select>
                        </div>
                        <div class="cs-prop-row" id="propCurrentRow" style="display:none;">
                            <span class="cs-prop-label">Current</span>
                            <span class="cs-prop-value positive" id="propCurrent">—</span>
                        </div>
                        <div class="cs-prop-row" id="propVoltageRow" style="display:none;">
                            <span class="cs-prop-label">V Drop</span>
                            <span class="cs-prop-value" id="propVoltDrop">—</span>
                        </div>
                    </div>

                    <!-- Circuit Stats -->
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">📋 Circuit</div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Components</span>
                            <span class="cs-prop-value" id="statComponents">0</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Wires</span>
                            <span class="cs-prop-value" id="statWires">0</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Nodes</span>
                            <span class="cs-prop-value" id="statNodes">0</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Status</span>
                            <span class="cs-prop-value" id="statStatus" style="color:var(--cs-text-muted);">Ready</span>
                        </div>
                    </div>

                    <!-- Energy -->
                    <div class="cs-prop-section" style="background:linear-gradient(135deg, rgba(0,255,136,0.05), rgba(0,212,255,0.05));">
                        <div class="cs-prop-title">⚡ Ecosystem Energy</div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Your Energy</span>
                            <span class="cs-prop-value positive" id="energyContrib">0 eV</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Circuits</span>
                            <span class="cs-prop-value" id="energyCircuits">0</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Pool</span>
                            <span class="cs-prop-value positive" id="energyPool">4,013 GSM</span>
                        </div>
                        <div style="margin-top:0.4rem;font-size:0.7rem;color:var(--cs-text-muted);line-height:1.4;">
                            Every circuit charges the battery. <a href="/qgsm-whitepaper.php" style="color:var(--cs-accent);">GSM →</a>
                        </div>
                    </div>
                </div>

                <!-- SCOPE TAB -->
                <div class="cs-right-panel" id="rightScope">
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">📈 Oscilloscope</div>
                        <div class="cs-oscilloscope">
                            <canvas id="scopeCanvas" width="560" height="280"></canvas>
                            <div class="cs-osc-controls">
                                <button class="cs-btn cs-btn-sm" onclick="csApp.scopeZoomIn()">Time −</button>
                                <button class="cs-btn cs-btn-sm" onclick="csApp.scopeZoomOut()">Time +</button>
                                <button class="cs-btn cs-btn-sm" onclick="csApp.scopeVoltUp()">V/div +</button>
                                <button class="cs-btn cs-btn-sm" onclick="csApp.scopeVoltDown()">V/div −</button>
                                <button class="cs-btn cs-btn-sm" onclick="csApp.scopeTogglePause()">⏸</button>
                            </div>
                            <div class="cs-scope-modes">
                                <button class="cs-scope-mode-btn" id="scopeModeNorm" onclick="csApp.scopeSetMode('normal')">Normal</button>
                                <button class="cs-scope-mode-btn" id="scopeModeFFT" onclick="csApp.scopeSetMode('fft')">FFT</button>
                                <button class="cs-scope-mode-btn" id="scopeModeXY" onclick="csApp.scopeSetMode('xy')">XY</button>
                                <button class="cs-scope-mode-btn" id="scopeModePersist" onclick="csApp.scopeTogglePersistence()">Persist</button>
                                <button class="cs-scope-mode-btn" id="scopeModeCursor" onclick="csApp.scopeToggleCursors()">Cursors</button>
                            </div>
                        </div>
                    </div>
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">Channels</div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label" style="color:#00D4FF;">CH1 Voltage</span>
                            <span class="cs-prop-value" id="scopeCH1">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label" style="color:#00FF88;">CH2 Current</span>
                            <span class="cs-prop-value" id="scopeCH2">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Frequency</span>
                            <span class="cs-prop-value" id="scopeFreqRead">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Period</span>
                            <span class="cs-prop-value" id="scopePeriodRead">—</span>
                        </div>
                    </div>
                </div>

                <!-- BODE TAB -->
                <div class="cs-right-panel" id="rightBode">
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">📉 Bode Plot</div>
                        <div class="cs-bode-wrap">
                            <canvas id="bodeCanvas" width="560" height="400"></canvas>
                        </div>
                        <div style="margin-top:0.5rem;font-size:0.72rem;color:var(--cs-text-muted);line-height:1.5;">
                            <span style="color:#00D4FF;">━━</span> Magnitude (dB) &nbsp;
                            <span style="color:#FF8800;">╶╶</span> Phase (deg) &nbsp;
                            <span style="color:#FF3366;">╶╶</span> −3dB line
                        </div>
                    </div>
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">Bode Analysis</div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Cutoff (-3dB)</span>
                            <span class="cs-prop-value" id="bodeCutoff">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">DC Gain</span>
                            <span class="cs-prop-value" id="bodeDCGain">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Phase Margin</span>
                            <span class="cs-prop-value" id="bodePhaseMargin">—</span>
                        </div>
                        <div class="cs-prop-row">
                            <span class="cs-prop-label">Type</span>
                            <span class="cs-prop-value" id="bodeFilterType">—</span>
                        </div>
                    </div>
                    <div style="font-size:0.72rem;color:var(--cs-text-muted);padding:0.5rem;">
                        Bode plot shows frequency response of your circuit (requires R, C and/or L components + AC source). Run simulation to update.
                    </div>
                </div>

                <!-- FORMULAS TAB -->
                <div class="cs-right-panel" id="rightFormulas">
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">📐 Active Formulas</div>
                        <div class="cs-formula-panel" id="formulaPanel">
                            <div class="cs-formula-empty">Run simulation to see formulas</div>
                        </div>
                    </div>
                    <div class="cs-prop-section" style="background:linear-gradient(135deg, rgba(0,255,204,0.04), rgba(125,0,255,0.04));">
                        <div class="cs-prop-title">📖 Reference</div>
                        <div style="font-size:0.72rem; color:var(--cs-text-muted); line-height:1.6;">
                            <strong style="color:var(--cs-accent);">Ohm's Law:</strong> V = IR<br>
                            <strong style="color:var(--cs-accent);">Resonance:</strong> f₀ = 1/(2π√LC)<br>
                            <strong style="color:var(--cs-accent);">Impedance:</strong> Z = √(R² + (X<sub>L</sub>−X<sub>C</sub>)²)<br>
                            <strong style="color:var(--cs-accent);">Q Factor:</strong> Q = (1/R)√(L/C)<br>
                            <strong style="color:var(--cs-tesla);">Tesla:</strong> V<sub>out</sub> ≈ V<sub>in</sub>√(C<sub>p</sub>/C<sub>s</sub>)<br>
                            <strong style="color:var(--cs-zpe);">Casimir:</strong> F = −π²ℏcA/(240d⁴)<br>
                            <strong style="color:var(--cs-zpe);">ZPE:</strong> E₀ = ½ℏω<br>
                            <strong style="color:var(--cs-zpe);">Schumann:</strong> f<sub>n</sub> = (c/2πR)√(n(n+1))
                        </div>
                    </div>
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">🔢 Physics Constants</div>
                        <div style="font-size:0.68rem; font-family:'JetBrains Mono',monospace; color:var(--cs-text-muted); line-height:1.7;">
                            c = 2.998 × 10⁸ m/s<br>
                            ℏ = 1.055 × 10⁻³⁴ J·s<br>
                            e = 1.602 × 10⁻¹⁹ C<br>
                            µ₀ = 1.257 × 10⁻⁶ H/m<br>
                            ε₀ = 8.854 × 10⁻¹² F/m<br>
                            k<sub>B</sub> = 1.381 × 10⁻²³ J/K
                        </div>
                    </div>
                </div>

                <!-- CIRCUITS TAB -->
                <div class="cs-right-panel" id="rightCircuits">
                    <!-- Standard Circuits -->
                    <div class="cs-prop-section">
                        <div class="cs-prop-title">🔬 Standard Circuits</div>
                        <div class="cs-prebuilt">
                            <div class="cs-prebuilt-item" onclick="csApp.loadPrebuilt('led_basic')">
                                <span class="cs-prebuilt-icon">💡</span>
                                <div><div style="font-weight:600;">LED + Resistor</div><div style="font-size:0.65rem;color:var(--cs-text-muted);">Basic current limiting</div></div>
                            </div>
                            <div class="cs-prebuilt-item" onclick="csApp.loadPrebuilt('voltage_divider')">
                                <span class="cs-prebuilt-icon">⚡</span>
                                <div><div style="font-weight:600;">Voltage Divider</div><div style="font-size:0.65rem;color:var(--cs-text-muted);">Two resistors, split voltage</div></div>
                            </div>
                            <div class="cs-prebuilt-item" onclick="csApp.loadPrebuilt('rc_filter')">
                                <span class="cs-prebuilt-icon">〰️</span>
                                <div><div style="font-weight:600;">RC Low-Pass Filter</div><div style="font-size:0.65rem;color:var(--cs-text-muted);">Resistor + capacitor filter</div></div>
                            </div>
                            <div class="cs-prebuilt-item" onclick="csApp.loadPrebuilt('transistor_switch')">
                                <span class="cs-prebuilt-icon">⊳</span>
                                <div><div style="font-weight:600;">Transistor Switch</div><div style="font-size:0.65rem;color:var(--cs-text-muted);">NPN switching circuit</div></div>
                            </div>
                            <div class="cs-prebuilt-item" onclick="csApp.loadPrebuilt('full_bridge')">
                                <span class="cs-prebuilt-icon">🔋</span>
                                <div><div style="font-weight:600;">Full Bridge Rectifier</div><div style="font-size:0.65rem;color:var(--cs-text-muted);">AC to DC conversion</div></div>
                            </div>
                            <div class="cs-prebuilt-item" onclick="csApp.loadPrebuilt('555_timer')">
                                <span class="cs-prebuilt-icon">⏱️</span>
                                <div><div style="font-weight:600;">555 Timer Blinker</div><div style="font-size:0.65rem;color:var(--cs-text-muted);">Astable oscillator</div></div>
                            </div>
                            <div class="cs-prebuilt-item" onclick="csApp.loadPrebuilt('joule_thief')">
                                <span class="cs-prebuilt-icon">🔋</span>
                                <div><div style="font-weight:600;">Joule Thief</div><div style="font-size:0.65rem;color:var(--cs-text-muted);">Voltage booster from dead battery</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- ZPE / Tesla Circuits -->
                    <div class="cs-prop-section" style="border-color:rgba(0,255,204,0.15);">
                        <div class="cs-prop-title" style="color:var(--cs-zpe);">⚛ ZPE / Tesla Research</div>
                        <div class="cs-prebuilt" id="zpeTemplateList">
                            <!-- Populated by JS from ZPE_TEMPLATES -->
                        </div>
                    </div>

                    <div class="cs-prop-section" style="background: linear-gradient(135deg, rgba(125,0,255,0.08), rgba(0,212,255,0.08)); border-color: rgba(125,0,255,0.2);">
                        <div class="cs-prop-title">🎁 Free API — 2026</div>
                        <div style="font-size:0.75rem;line-height:1.5;margin-bottom:0.5rem;">
                            <strong style="color:var(--cs-accent);">Founders Free Tier</strong><br>
                            10,000 API requests/day<br>
                            Valid through Dec 31, 2026
                        </div>
                        <a href="/developer-portal.php" class="cs-btn cs-btn-primary" style="width:100%;justify-content:center;">Get Free API Key →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div class="cs-context-menu" id="contextMenu">
    <div class="cs-context-item" onclick="csApp.ctxRotate()"><span>🔄</span> Rotate <span class="ctx-key">R</span></div>
    <div class="cs-context-item" onclick="csApp.ctxDuplicate()"><span>📋</span> Duplicate <span class="ctx-key">Ctrl+D</span></div>
    <div class="cs-context-item" onclick="csApp.ctxCopy()"><span>📑</span> Copy <span class="ctx-key">Ctrl+C</span></div>
    <div class="cs-context-item" onclick="csApp.ctxPaste()"><span>📌</span> Paste <span class="ctx-key">Ctrl+V</span></div>
    <div class="cs-context-sep"></div>
    <div class="cs-context-item" onclick="csApp.ctxProps()"><span>⚙️</span> Properties</div>
    <div class="cs-context-item" onclick="csApp.ctxDelete()" style="color:var(--cs-danger);"><span>🗑️</span> Delete <span class="ctx-key">Del</span></div>
</div>

<!-- Keyboard Help Overlay -->
<div class="cs-kb-overlay" id="kbOverlay" onclick="csApp.hideKBHelp()">
    <div class="cs-kb-box" onclick="event.stopPropagation()">
        <h3>⌨️ Keyboard Shortcuts</h3>
        <div class="cs-kb-row"><span>Select mode</span><kbd>V</kbd></div>
        <div class="cs-kb-row"><span>Wire mode</span><kbd>W</kbd></div>
        <div class="cs-kb-row"><span>Delete selected</span><kbd>Del</kbd></div>
        <div class="cs-kb-row"><span>Rotate selected</span><kbd>R</kbd></div>
        <div class="cs-kb-row"><span>Toggle simulation</span><kbd>Space</kbd></div>
        <div class="cs-kb-row"><span>Undo</span><kbd>Ctrl+Z</kbd></div>
        <div class="cs-kb-row"><span>Redo</span><kbd>Ctrl+Y</kbd></div>
        <div class="cs-kb-row"><span>Copy selected</span><kbd>Ctrl+C</kbd></div>
        <div class="cs-kb-row"><span>Paste</span><kbd>Ctrl+V</kbd></div>
        <div class="cs-kb-row"><span>Duplicate</span><kbd>Ctrl+D</kbd></div>
        <div class="cs-kb-row"><span>Select all</span><kbd>Ctrl+A</kbd></div>
        <div class="cs-kb-row"><span>Show this help</span><kbd>?</kbd></div>
        <div class="cs-kb-row"><span>Cancel / Deselect</span><kbd>Esc</kbd></div>
        <div style="text-align:center;margin-top:1rem;font-size:0.75rem;color:var(--cs-text-muted);">Click outside or press <kbd>Esc</kbd> to close</div>
    </div>
</div>
<script src="/assets/js/circuit-engine.js"></script>

<script src="/assets/js/circuit-simulator-app.js"></script>


<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
