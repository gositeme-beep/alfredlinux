<?php
$page_title = 'Spacetime Simulator — Quadratic Gravity Visualizer — GoSiteMe';
$page_description = 'Interactive visualization of the quadratic gravity action S[φ] = ∫d⁴x√(-g)[R/16πG + L_matter + αR² + βRμνRμν]. See spacetime curvature, geodesics, gravitational waves, scalar fields, and Starobinsky inflation — live.';
$page_canonical = 'https://root.com/spacetime-simulator.php';
$page_og_title = 'Spacetime Simulator — Quadratic Gravity Live Demo';
$page_og_description = 'Watch Einstein\'s gravity come alive with quantum corrections. Particles orbit, energy sparks, spacetime warps. Built by GoSiteMe.';

$noGlobalMain = true;
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ═══════════════════════════════════════
   SPACETIME SIMULATOR — Quadratic Gravity
   ═══════════════════════════════════════ */
:root {
    --st-bg: #020208;
    --st-surface: #0a0a18;
    --st-surface-2: #12122a;
    --st-border: rgba(0,212,255,0.12);
    --st-accent: #00D4FF;
    --st-accent-2: #7D00FF;
    --st-energy: #00FF88;
    --st-inflation: #FFB800;
    --st-matter: #ec4899;
    --st-text: #e8e8f0;
    --st-text-muted: #6a7a8a;
    --st-radius: 12px;
}

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

.st-page {
    min-height: 100vh;
    background: var(--st-bg);
    color: var(--st-text);
    font-family: 'Inter', -apple-system, system-ui, sans-serif;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* ── Top Bar ── */
.st-topbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 1rem;
    background: var(--st-surface);
    border-bottom: 1px solid var(--st-border);
    flex-shrink: 0;
    z-index: 100;
}
.st-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    font-size: 1rem;
    color: var(--st-accent);
    text-decoration: none;
}
.st-logo i { font-size: 1.2rem; }
.st-topbar-title {
    font-size: 0.8rem;
    color: var(--st-text-muted);
    padding-left: 1rem;
    border-left: 1px solid var(--st-border);
}
.st-topbar-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
    align-items: center;
    flex-wrap: wrap;
}
.st-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.75rem;
    border-radius: 8px;
    border: 1px solid var(--st-border);
    background: var(--st-surface-2);
    color: var(--st-text);
    font-size: 0.78rem;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    font-family: inherit;
}
.st-btn:hover { border-color: var(--st-accent); background: rgba(0,212,255,0.08); }
.st-btn-active { border-color: var(--st-accent); background: rgba(0,212,255,0.12); color: var(--st-accent); }
.st-btn-primary {
    background: linear-gradient(135deg, var(--st-accent), var(--st-accent-2));
    border-color: transparent;
    color: #fff;
    font-weight: 600;
}
.st-btn-primary:hover { opacity: 0.9; }
.st-btn-danger {
    background: rgba(255,51,102,0.15);
    border-color: rgba(255,51,102,0.3);
    color: #FF3366;
}

/* ── Main Layout ── */
.st-main {
    display: flex;
    flex: 1;
    overflow: hidden;
    height: calc(100vh - 48px);
}

/* ── Canvas Area ── */
.st-canvas-wrap {
    flex: 1;
    position: relative;
    background: var(--st-bg);
    overflow: hidden;
}
.st-canvas-wrap canvas {
    display: block;
    width: 100%;
    height: 100%;
}

/* ── Side Panel ── */
.st-panel {
    width: 320px;
    background: var(--st-surface);
    border-left: 1px solid var(--st-border);
    overflow-y: auto;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
}
.st-panel-section {
    padding: 1rem;
    border-bottom: 1px solid var(--st-border);
}
.st-panel-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--st-text-muted);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.st-panel-title i { color: var(--st-accent); }

/* ── Sliders ── */
.st-slider-group {
    margin-bottom: 0.75rem;
}
.st-slider-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.78rem;
    color: var(--st-text);
    margin-bottom: 0.3rem;
}
.st-slider-value {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: 0.75rem;
    color: var(--st-accent);
    min-width: 45px;
    text-align: right;
}
.st-slider {
    -webkit-appearance: none;
    width: 100%;
    height: 4px;
    border-radius: 2px;
    background: var(--st-surface-2);
    outline: none;
    cursor: pointer;
}
.st-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--st-accent);
    cursor: pointer;
    box-shadow: 0 0 10px rgba(0,212,255,0.4);
}
.st-slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--st-accent);
    cursor: pointer;
    border: none;
}
.st-slider[data-color="inflation"]::-webkit-slider-thumb { background: var(--st-inflation); box-shadow: 0 0 10px rgba(255,184,0,0.4); }
.st-slider[data-color="quantum"]::-webkit-slider-thumb { background: var(--st-energy); box-shadow: 0 0 10px rgba(0,255,136,0.4); }
.st-slider[data-color="matter"]::-webkit-slider-thumb { background: var(--st-matter); box-shadow: 0 0 10px rgba(236,72,153,0.4); }
.st-slider[data-color="inflation"]::-moz-range-thumb { background: var(--st-inflation); border: none; }
.st-slider[data-color="quantum"]::-moz-range-thumb { background: var(--st-energy); border: none; }
.st-slider[data-color="matter"]::-moz-range-thumb { background: var(--st-matter); border: none; }

/* ── Presets ── */
.st-presets {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.4rem;
}
.st-preset {
    padding: 0.5rem;
    border-radius: 8px;
    border: 1px solid var(--st-border);
    background: var(--st-surface-2);
    color: var(--st-text);
    font-size: 0.72rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    font-family: inherit;
}
.st-preset:hover { border-color: var(--st-accent); background: rgba(0,212,255,0.08); }
.st-preset-active { border-color: var(--st-accent); background: rgba(0,212,255,0.12); }
.st-preset-label { font-weight: 600; display: block; margin-bottom: 2px; }
.st-preset-desc { color: var(--st-text-muted); font-size: 0.65rem; }

/* ── Formula Cards ── */
.st-formula-card {
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--st-border);
    background: var(--st-surface-2);
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}
.st-formula-card:hover { border-color: rgba(0,212,255,0.3); }
.st-formula-name {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--st-text);
    margin-bottom: 0.25rem;
}
.st-formula-eq {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: 0.78rem;
    color: var(--st-accent);
    margin-bottom: 0.25rem;
    word-break: break-all;
}
.st-formula-desc {
    font-size: 0.68rem;
    color: var(--st-text-muted);
    line-height: 1.4;
}

/* ── Toggles ── */
.st-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.4rem 0;
}
.st-toggle-label {
    font-size: 0.78rem;
    color: var(--st-text);
}
.st-toggle {
    position: relative;
    width: 36px;
    height: 20px;
}
.st-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.st-toggle-track {
    position: absolute;
    inset: 0;
    background: var(--st-surface-2);
    border-radius: 10px;
    border: 1px solid var(--st-border);
    cursor: pointer;
    transition: all 0.2s;
}
.st-toggle-track::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--st-text-muted);
    transition: all 0.2s;
}
.st-toggle input:checked + .st-toggle-track {
    background: rgba(0,212,255,0.2);
    border-color: var(--st-accent);
}
.st-toggle input:checked + .st-toggle-track::after {
    transform: translateX(16px);
    background: var(--st-accent);
}

/* ── Mobile ── */
@media (max-width: 768px) {
    .st-main {
        flex-direction: column;
        height: auto;
    }
    .st-canvas-wrap {
        height: 55vh;
        min-height: 340px;
    }
    .st-panel {
        width: 100%;
        border-left: none;
        border-top: 1px solid var(--st-border);
        max-height: 45vh;
    }
    .st-topbar {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .st-topbar-title { display: none; }
}

@media (pointer: coarse) {
    .st-slider::-webkit-slider-thumb {
        width: 24px;
        height: 24px;
    }
    .st-btn { padding: 0.5rem 1rem; font-size: 0.85rem; }
}

/* ── Scrollbar ── */
.st-panel::-webkit-scrollbar { width: 5px; }
.st-panel::-webkit-scrollbar-track { background: transparent; }
.st-panel::-webkit-scrollbar-thumb { background: rgba(0,212,255,0.2); border-radius: 3px; }
</style>

<div class="st-page">
    <!-- Top Bar -->
    <div class="st-topbar">
        <a href="/circuit-simulator.php" class="st-logo">
            <i class="fas fa-atom"></i>
            <span>Spacetime Simulator</span>
        </a>
        <span class="st-topbar-title">Quadratic Gravity — S[φ] = ∫ d⁴x √(-g) [ R/(16πG) + ℒ + αR² + βRμνRμν ]</span>
        <div class="st-topbar-actions">
            <button class="st-btn" id="btnToggleGrid" title="Toggle spacetime grid">
                <i class="fas fa-border-all"></i> Grid
            </button>
            <button class="st-btn" id="btnToggleField" title="Toggle scalar field">
                <i class="fas fa-wave-square"></i> Field
            </button>
            <button class="st-btn" id="btnToggleCurvature" title="Toggle curvature heatmap">
                <i class="fas fa-fire"></i> Curvature
            </button>
            <button class="st-btn st-btn-primary" id="btnPlayPause">
                <i class="fas fa-play" id="playIcon"></i> Start
            </button>
            <button class="st-btn st-btn-danger" id="btnReset">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
    </div>

    <!-- Main Area -->
    <div class="st-main">
        <!-- Canvas -->
        <div class="st-canvas-wrap" id="canvasWrap">
            <canvas id="spacetimeCanvas"></canvas>
        </div>

        <!-- Side Panel -->
        <div class="st-panel">

            <!-- The Action -->
            <div class="st-panel-section">
                <div class="st-panel-title"><i class="fas fa-scroll"></i> The Action</div>
                <div class="st-formula-card" style="border-color: rgba(0,212,255,0.3);">
                    <div class="st-formula-eq" style="font-size: 0.85rem; line-height: 1.6;">
                        S[φ] = ∫ d⁴x √(-g) [<br>
                        &nbsp;&nbsp;<span style="color:#00D4FF">R/(16πG)</span>
                        + <span style="color:#ec4899">ℒ(φ,∇φ)</span><br>
                        &nbsp;&nbsp;+ <span style="color:#FFB800">αR²</span>
                        + <span style="color:#00FF88">βR<sub>μν</sub>R<sup>μν</sup></span>
                        ]
                    </div>
                    <div class="st-formula-desc">
                        The complete quadratic gravity action with matter. Each term has its own color in the visualization.
                    </div>
                </div>
            </div>

            <!-- Parameters -->
            <div class="st-panel-section">
                <div class="st-panel-title"><i class="fas fa-sliders-h"></i> Parameters</div>

                <div class="st-slider-group">
                    <div class="st-slider-label">
                        <span><i class="fas fa-circle" style="color:#e8e8f0;font-size:8px"></i> Mass (M)</span>
                        <span class="st-slider-value" id="valMass">3.00</span>
                    </div>
                    <input type="range" class="st-slider" id="sliderMass" min="0.5" max="10" step="0.1" value="3">
                </div>

                <div class="st-slider-group">
                    <div class="st-slider-label">
                        <span><i class="fas fa-circle" style="color:#FFB800;font-size:8px"></i> α (R² coupling)</span>
                        <span class="st-slider-value" id="valAlpha" style="color:#FFB800">0.50</span>
                    </div>
                    <input type="range" class="st-slider" data-color="inflation" id="sliderAlpha" min="0" max="3" step="0.01" value="0.5">
                </div>

                <div class="st-slider-group">
                    <div class="st-slider-label">
                        <span><i class="fas fa-circle" style="color:#00FF88;font-size:8px"></i> β (R<sub>μν</sub>R<sup>μν</sup> coupling)</span>
                        <span class="st-slider-value" id="valBeta" style="color:#00FF88">0.20</span>
                    </div>
                    <input type="range" class="st-slider" data-color="quantum" id="sliderBeta" min="0" max="3" step="0.01" value="0.2">
                </div>

                <div class="st-slider-group">
                    <div class="st-slider-label">
                        <span><i class="fas fa-circle" style="color:#ec4899;font-size:8px"></i> φ₀ (scalar field)</span>
                        <span class="st-slider-value" id="valPhi0" style="color:#ec4899">1.00</span>
                    </div>
                    <input type="range" class="st-slider" data-color="matter" id="sliderPhi0" min="0" max="3" step="0.01" value="1.0">
                </div>

                <div class="st-slider-group">
                    <div class="st-slider-label">
                        <span><i class="fas fa-tachometer-alt"></i> Speed</span>
                        <span class="st-slider-value" id="valSpeed">1.0×</span>
                    </div>
                    <input type="range" class="st-slider" id="sliderSpeed" min="0.1" max="5" step="0.1" value="1">
                </div>
            </div>

            <!-- Presets -->
            <div class="st-panel-section">
                <div class="st-panel-title"><i class="fas fa-star"></i> Presets</div>
                <div class="st-presets">
                    <button class="st-preset" data-preset="pure-einstein">
                        <span class="st-preset-label">Pure Einstein</span>
                        <span class="st-preset-desc">α=0, β=0 — standard GR</span>
                    </button>
                    <button class="st-preset" data-preset="starobinsky-inflation">
                        <span class="st-preset-label">Starobinsky</span>
                        <span class="st-preset-desc">αR² — cosmic inflation</span>
                    </button>
                    <button class="st-preset" data-preset="stelle-gravity">
                        <span class="st-preset-label">Stelle Gravity</span>
                        <span class="st-preset-desc">Renormalizable quantum gravity</span>
                    </button>
                    <button class="st-preset" data-preset="full-action">
                        <span class="st-preset-label">Full Action</span>
                        <span class="st-preset-desc">All terms active</span>
                    </button>
                    <button class="st-preset" data-preset="strong-quantum">
                        <span class="st-preset-label">Strong Quantum</span>
                        <span class="st-preset-desc">Large α, β — extreme</span>
                    </button>
                    <button class="st-preset" data-preset="black-hole">
                        <span class="st-preset-label">Black Hole</span>
                        <span class="st-preset-desc">High mass, subtle corrections</span>
                    </button>
                </div>
            </div>

            <!-- Display Toggles -->
            <div class="st-panel-section">
                <div class="st-panel-title"><i class="fas fa-eye"></i> Display</div>

                <div class="st-toggle-row">
                    <span class="st-toggle-label">Spacetime Grid</span>
                    <label class="st-toggle">
                        <input type="checkbox" id="toggleGrid" checked>
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
                <div class="st-toggle-row">
                    <span class="st-toggle-label">Curvature Heatmap</span>
                    <label class="st-toggle">
                        <input type="checkbox" id="toggleCurvature" checked>
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
                <div class="st-toggle-row">
                    <span class="st-toggle-label">Scalar Field φ</span>
                    <label class="st-toggle">
                        <input type="checkbox" id="toggleField" checked>
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
                <div class="st-toggle-row">
                    <span class="st-toggle-label">Action Formula</span>
                    <label class="st-toggle">
                        <input type="checkbox" id="toggleFormula" checked>
                        <span class="st-toggle-track"></span>
                    </label>
                </div>
            </div>

            <!-- Physics Reference -->
            <div class="st-panel-section">
                <div class="st-panel-title"><i class="fas fa-book"></i> Physics</div>

                <div class="st-formula-card">
                    <div class="st-formula-name" style="color:#00D4FF">Einstein-Hilbert</div>
                    <div class="st-formula-eq">S<sub>EH</sub> = (1/16πG) ∫ R √(-g) d⁴x</div>
                    <div class="st-formula-desc">Standard general relativity. The Ricci scalar R encodes spacetime curvature from mass-energy. This is what bends the grid.</div>
                </div>

                <div class="st-formula-card">
                    <div class="st-formula-name" style="color:#FFB800">Starobinsky R²</div>
                    <div class="st-formula-eq">S<sub>R²</sub> = α ∫ R² √(-g) d⁴x</div>
                    <div class="st-formula-desc">Drives cosmic inflation — the rapid expansion of the early universe. Best-fit to Planck CMB data. The golden ripples expanding outward.</div>
                </div>

                <div class="st-formula-card">
                    <div class="st-formula-name" style="color:#00FF88">Ricci Tensor Squared</div>
                    <div class="st-formula-eq">S<sub>Ric</sub> = β ∫ R<sub>μν</sub>R<sup>μν</sup> √(-g) d⁴x</div>
                    <div class="st-formula-desc">Makes gravity renormalizable at the quantum level (Stelle, 1977). Introduces a massive spin-2 graviton. The green ripples emanating from the center.</div>
                </div>

                <div class="st-formula-card">
                    <div class="st-formula-name" style="color:#ec4899">Scalar Field</div>
                    <div class="st-formula-eq">ℒ = ½(∂<sub>μ</sub>φ)(∂<sup>μ</sup>φ) - ½m²φ²</div>
                    <div class="st-formula-desc">The matter content — a Klein-Gordon scalar field φ oscillating in spacetime. The purple glow pulsing around the mass.</div>
                </div>

                <div class="st-formula-card">
                    <div class="st-formula-name" style="color:#e8e8f0">Modified Potential</div>
                    <div class="st-formula-eq">Φ(r) = -GM/r [1 + ⅓e<sup>-m₀r</sup> - ⁴⁄₃e<sup>-m₂r</sup>]</div>
                    <div class="st-formula-desc">Yukawa corrections to Newton's gravity from the quadratic terms. Particles follow orbits shaped by this potential.</div>
                </div>

                <div class="st-formula-card">
                    <div class="st-formula-name" style="color:#a8b2d1">Geodesic Equation</div>
                    <div class="st-formula-eq">d²x<sup>μ</sup>/dτ² + Γ<sup>μ</sup><sub>αβ</sub> dx<sup>α</sup>/dτ dx<sup>β</sup>/dτ = 0</div>
                    <div class="st-formula-desc">The paths of free-falling particles through curved spacetime. Every orbiting dot follows this equation.</div>
                </div>
            </div>

            <!-- Credits -->
            <div class="st-panel-section" style="border-bottom:none;">
                <div style="font-size:0.68rem;color:var(--st-text-muted);text-align:center;line-height:1.5;">
                    Built by <a href="/" style="color:var(--st-accent);text-decoration:none;">GoSiteMe</a><br>
                    Danny William Perez<br>
                    <span style="color:var(--st-accent)">S[φ] = ∫ d⁴x √(-g) [ R/(16πG) + ℒ + αR² + βRμνRμν ]</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/gravity-field-engine.js"></script>
<script>
(() => {
    'use strict';

    const { SpacetimeSimulator } = window.GravityFieldEngine;
    const sim = new SpacetimeSimulator('spacetimeCanvas');

    // ── Play / Pause ──
    const btnPlay = document.getElementById('btnPlayPause');
    const playIcon = document.getElementById('playIcon');
    let playing = false;

    function togglePlay() {
        playing = !playing;
        if (playing) {
            sim.start();
            playIcon.className = 'fas fa-pause';
            btnPlay.innerHTML = '<i class="fas fa-pause"></i> Pause';
        } else {
            sim.stop();
            playIcon.className = 'fas fa-play';
            btnPlay.innerHTML = '<i class="fas fa-play"></i> Start';
        }
    }
    btnPlay.addEventListener('click', togglePlay);

    // Auto-start after 500ms
    setTimeout(() => {
        if (!playing) togglePlay();
    }, 500);

    // ── Reset ──
    document.getElementById('btnReset').addEventListener('click', () => {
        sim.reset();
        syncSlidersToSim();
    });

    // ── Sliders ──
    function bindSlider(id, display, setter, formatter) {
        const el = document.getElementById(id);
        const val = document.getElementById(display);
        el.addEventListener('input', () => {
            const v = parseFloat(el.value);
            setter(v);
            val.textContent = formatter(v);
        });
    }

    bindSlider('sliderMass', 'valMass', v => sim.setMass(v), v => v.toFixed(2));
    bindSlider('sliderAlpha', 'valAlpha', v => sim.setAlpha(v), v => v.toFixed(2));
    bindSlider('sliderBeta', 'valBeta', v => sim.setBeta(v), v => v.toFixed(2));
    bindSlider('sliderPhi0', 'valPhi0', v => sim.setPhi0(v), v => v.toFixed(2));
    bindSlider('sliderSpeed', 'valSpeed', v => sim.setSpeed(v), v => v.toFixed(1) + '×');

    function syncSlidersToSim() {
        document.getElementById('sliderMass').value = sim.M;
        document.getElementById('valMass').textContent = sim.M.toFixed(2);
        document.getElementById('sliderAlpha').value = sim.alpha;
        document.getElementById('valAlpha').textContent = sim.alpha.toFixed(2);
        document.getElementById('sliderBeta').value = sim.beta;
        document.getElementById('valBeta').textContent = sim.beta.toFixed(2);
        document.getElementById('sliderPhi0').value = sim.phi0;
        document.getElementById('valPhi0').textContent = sim.phi0.toFixed(2);
    }

    // ── Presets ──
    document.querySelectorAll('.st-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.st-preset').forEach(b => b.classList.remove('st-preset-active'));
            btn.classList.add('st-preset-active');
            sim.loadPreset(btn.dataset.preset);
            syncSlidersToSim();
        });
    });

    // ── Toggle Buttons (topbar) ──
    function makeToggle(btnId, checkId, simMethod) {
        const btn = document.getElementById(btnId);
        const chk = document.getElementById(checkId);
        btn.addEventListener('click', () => {
            simMethod.call(sim);
            btn.classList.toggle('st-btn-active');
            if (chk) chk.checked = !chk.checked;
        });
        if (chk) {
            chk.addEventListener('change', () => {
                simMethod.call(sim);
                btn.classList.toggle('st-btn-active');
            });
        }
        // Init active state
        btn.classList.add('st-btn-active');
    }

    makeToggle('btnToggleGrid', 'toggleGrid', sim.toggleGrid);
    makeToggle('btnToggleField', 'toggleField', sim.toggleField);
    makeToggle('btnToggleCurvature', 'toggleCurvature', sim.toggleCurvature);

    // Formula toggle (panel only)
    document.getElementById('toggleFormula').addEventListener('change', () => {
        sim.toggleFormula();
    });

    // ── Keyboard shortcuts ──
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT') return;
        switch (e.key) {
            case ' ': e.preventDefault(); togglePlay(); break;
            case 'r': sim.reset(); syncSlidersToSim(); break;
            case 'g': sim.toggleGrid(); break;
            case 'f': sim.toggleField(); break;
            case 'c': sim.toggleCurvature(); break;
        }
    });
})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
