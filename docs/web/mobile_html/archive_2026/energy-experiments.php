<?php
// ...existing code...
$page_title = 'Unified Energy Experiments — 142 Scientists, Every Reaction — GoSiteMe';
$page_description = 'Interactive encyclopedia of 142 energy experiments: Tesla, Faraday, Maxwell, Hertz, Rutherford, Einstein, Hawking, LIGO, CERN Higgs, quantum computing, fusion, solar, batteries, and frontier energy — all animated with real physics and formulas.';
$page_canonical = 'https://gositeme.com/energy-experiments.php';
$page_og_title = 'Unified Energy Experiments — 142 Scientists, Live Animated';
$page_og_description = 'The most comprehensive interactive energy experiments encyclopedia: 142 experiments from classical electromagnetism to quantum computing to frontier energy. Every formula, every animation, every scientist.';

$noGlobalMain = true;
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
:root {
    --ex-bg: #040410;
    --ex-surface: #0a0a1a;
    --ex-surface-2: #111126;
    --ex-border: rgba(0,212,255,0.1);
    --ex-accent: #00D4FF;
    --ex-accent-2: #7D00FF;
    --ex-energy: #00FF88;
    --ex-fire: #FF6B2B;
    --ex-tesla: #FF3366;
    --ex-cold: #00CCFF;
    --ex-matter: #ec4899;
    --ex-text: #e8e8f0;
    --ex-muted: #6a7a8a;
    --ex-radius: 14px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
.ex-page{min-height:100vh;background:var(--ex-bg);color:var(--ex-text);font-family:'Inter',-apple-system,system-ui,sans-serif}

/* ── Hero ── */
.ex-hero{text-align:center;padding:2.5rem 1rem 2rem;border-bottom:1px solid var(--ex-border);background:linear-gradient(180deg,rgba(125,0,255,0.06) 0%,transparent 100%)}
.ex-hero h1{font-size:clamp(1.5rem,4vw,2.5rem);font-weight:800;background:linear-gradient(135deg,#00D4FF,#7D00FF,#FF3366);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:.5rem}
.ex-hero p{color:var(--ex-muted);font-size:.9rem;max-width:700px;margin:0 auto .75rem}
.ex-hero-formula{font-family:'SF Mono','Fira Code',monospace;font-size:.8rem;color:var(--ex-accent);opacity:.6;margin-top:.5rem}

/* ── Filter Bar ── */
.ex-filters{display:flex;gap:.5rem;padding:.75rem 1rem;border-bottom:1px solid var(--ex-border);overflow-x:auto;-webkit-overflow-scrolling:touch;background:var(--ex-surface)}
.ex-filter{padding:.4rem .8rem;border-radius:20px;border:1px solid var(--ex-border);background:transparent;color:var(--ex-muted);font-size:.75rem;cursor:pointer;transition:all .2s;white-space:nowrap;font-family:inherit}
.ex-filter:hover{border-color:var(--ex-accent);color:var(--ex-text)}
.ex-filter-active{border-color:var(--ex-accent);background:rgba(0,212,255,.12);color:var(--ex-accent);font-weight:600}

/* ── Grid ── */
.ex-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:1.25rem;padding:1.25rem;max-width:1800px;margin:0 auto}
@media(max-width:480px){.ex-grid{grid-template-columns:1fr;padding:.75rem}}

/* ── Experiment Card ── */
.ex-card{border-radius:var(--ex-radius);border:1px solid var(--ex-border);background:var(--ex-surface);overflow:hidden;transition:all .3s;position:relative}
.ex-card:hover{border-color:rgba(0,212,255,.3);transform:translateY(-2px);box-shadow:0 8px 32px rgba(0,212,255,.08)}
.ex-card-canvas{width:100%;height:220px;display:block;background:#020208;cursor:pointer}
.ex-card-status{position:absolute;top:10px;right:10px;padding:3px 10px;border-radius:20px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;z-index:2}
.ex-status-verified{background:rgba(0,255,136,.15);color:#00FF88;border:1px solid rgba(0,255,136,.3)}
.ex-status-unverified{background:rgba(255,184,0,.12);color:#FFB800;border:1px solid rgba(255,184,0,.25)}
.ex-status-theoretical{background:rgba(125,0,255,.15);color:#b388ff;border:1px solid rgba(125,0,255,.3)}
.ex-status-disputed{background:rgba(255,51,102,.12);color:#FF3366;border:1px solid rgba(255,51,102,.25)}
.ex-card-body{padding:1rem}
.ex-card-title{font-size:1rem;font-weight:700;color:var(--ex-text);margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.ex-card-title .ex-icon{font-size:1.1rem}
.ex-card-researcher{font-size:.72rem;color:var(--ex-muted);margin-bottom:.5rem}
.ex-card-desc{font-size:.78rem;color:var(--ex-muted);line-height:1.5;margin-bottom:.6rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.ex-card-formula{font-family:'SF Mono','Fira Code',monospace;font-size:.72rem;padding:.5rem .6rem;border-radius:8px;background:rgba(0,212,255,.04);border:1px solid var(--ex-border);color:var(--ex-accent);word-break:break-all;line-height:1.5;margin-bottom:.5rem}
.ex-card-tags{display:flex;gap:.3rem;flex-wrap:wrap}
.ex-tag{padding:2px 8px;border-radius:12px;font-size:.62rem;font-weight:600;border:1px solid}
.ex-tag-zpe{color:#00FFCC;border-color:rgba(0,255,204,.25);background:rgba(0,255,204,.06)}
.ex-tag-em{color:#00D4FF;border-color:rgba(0,212,255,.25);background:rgba(0,212,255,.06)}
.ex-tag-nuclear{color:#FF3366;border-color:rgba(255,51,102,.25);background:rgba(255,51,102,.06)}
.ex-tag-acoustic{color:#FFB800;border-color:rgba(255,184,0,.25);background:rgba(255,184,0,.06)}
.ex-tag-gravity{color:#b388ff;border-color:rgba(125,0,255,.25);background:rgba(125,0,255,.06)}
.ex-tag-thermal{color:#FF6B2B;border-color:rgba(255,107,43,.25);background:rgba(255,107,43,.06)}
.ex-tag-electrolysis{color:#00FF88;border-color:rgba(0,255,136,.25);background:rgba(0,255,136,.06)}
.ex-tag-magnetic{color:#ec4899;border-color:rgba(236,72,153,.25);background:rgba(236,72,153,.06)}
.ex-tag-plasma{color:#FF5577;border-color:rgba(255,85,119,.25);background:rgba(255,85,119,.06)}
.ex-tag-mechanical{color:#AABB44;border-color:rgba(170,187,68,.25);background:rgba(170,187,68,.06)}
.ex-tag-biological{color:#66DD88;border-color:rgba(102,221,136,.25);background:rgba(102,221,136,.06)}
.ex-tag-chemical{color:#FF8844;border-color:rgba(255,136,68,.25);background:rgba(255,136,68,.06)}
.ex-tag-quantum{color:#88BBFF;border-color:rgba(136,187,255,.25);background:rgba(136,187,255,.06)}
.ex-tag-propulsion{color:#FFCC33;border-color:rgba(255,204,51,.25);background:rgba(255,204,51,.06)}
.ex-tag-wireless{color:#66EEDD;border-color:rgba(102,238,221,.25);background:rgba(102,238,221,.06)}
.ex-tag-resonance{color:#DD88FF;border-color:rgba(221,136,255,.25);background:rgba(221,136,255,.06)}
.ex-tag-optics{color:#FFEE44;border-color:rgba(255,238,68,.25);background:rgba(255,238,68,.06)}
.ex-tag-particle{color:#FF66AA;border-color:rgba(255,102,170,.25);background:rgba(255,102,170,.06)}
.ex-tag-battery{color:#44DDAA;border-color:rgba(68,221,170,.25);background:rgba(68,221,170,.06)}
.ex-tag-renewable{color:#88DD44;border-color:rgba(136,221,68,.25);background:rgba(136,221,68,.06)}
.ex-tag-historical{color:#DDAA66;border-color:rgba(221,170,102,.25);background:rgba(221,170,102,.06)}
.ex-tag-bioelectric{color:#66FFAA;border-color:rgba(102,255,170,.25);background:rgba(102,255,170,.06)}
.ex-tag-relativity{color:#CC88FF;border-color:rgba(204,136,255,.25);background:rgba(204,136,255,.06)}
.ex-tag-computing{color:#44CCFF;border-color:rgba(68,204,255,.25);background:rgba(68,204,255,.06)}

/* ── Search Bar ── */
.ex-search-wrap{padding:.75rem 1rem;background:var(--ex-surface);border-bottom:1px solid var(--ex-border);display:flex;gap:.75rem;align-items:center;flex-wrap:wrap}
.ex-search{flex:1;min-width:200px;padding:.5rem .8rem .5rem 2.2rem;border-radius:20px;border:1px solid var(--ex-border);background:rgba(0,0,0,.3);color:var(--ex-text);font-size:.82rem;font-family:inherit;outline:none;transition:border-color .2s}
.ex-search:focus{border-color:var(--ex-accent)}
.ex-search-wrap .fa-search{position:absolute;margin-left:.75rem;color:var(--ex-muted);font-size:.8rem;pointer-events:none}
.ex-search-wrap{position:relative}
.ex-suggest-btn{padding:.4rem .8rem;border-radius:20px;border:1px solid rgba(0,255,136,.25);background:rgba(0,255,136,.06);color:#00FF88;font-size:.72rem;cursor:pointer;font-weight:600;font-family:inherit;white-space:nowrap;transition:all .2s}
.ex-suggest-btn:hover{background:rgba(0,255,136,.15);border-color:rgba(0,255,136,.4)}
.ex-exp-count-badge{padding:.3rem .7rem;border-radius:20px;background:rgba(0,212,255,.08);border:1px solid var(--ex-border);color:var(--ex-accent);font-size:.72rem;font-weight:700;white-space:nowrap}

/* ── Footer Stats ── */
.ex-footer-stats{text-align:center;padding:2rem 1rem;border-top:1px solid var(--ex-border);color:var(--ex-muted);font-size:.75rem}
.ex-footer-stats a{color:var(--ex-accent);text-decoration:none}

/* scrollbar */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:rgba(0,212,255,.15);border-radius:3px}

@media(pointer:coarse){.ex-filter{padding:.5rem 1rem;font-size:.85rem}}
</style>

<div class="ex-page">
    <div class="ex-hero">
        <h1>Unified Energy Experiments</h1>
        <p>Every scientist. Every reaction. Verified, unverified, theoretical, and disputed — all visualized with real physics, live animation, and the actual formulas.</p>
        <div class="ex-hero-formula">S[φ] = ∫ d⁴x √(-g) [ R/(16πG) + ℒ(φ,∇φ) + αR² + βR_μν R^μν ]</div>
    </div>

    <div class="ex-search-wrap">
        <i class="fa-solid fa-search"></i>
        <input type="text" class="ex-search" id="expSearch" placeholder="Search experiments, scientists, formulas..." autocomplete="off">
        <span class="ex-exp-count-badge"><span id="expCountBadge">0</span> experiments</span>
        <button class="ex-suggest-btn" onclick="window.open('mailto:support@gositeme.com?subject=Experiment%20Suggestion&body=Experiment%20Name:%0AResearcher:%0ADescription:%0AFormula:%0AReferences:','_blank')"><i class="fa-solid fa-plus"></i> Suggest Experiment</button>
    </div>

    <div class="ex-filters">
        <button class="ex-filter ex-filter-active" data-filter="all">All Experiments</button>
        <button class="ex-filter" data-filter="verified">Verified</button>
        <button class="ex-filter" data-filter="unverified">Unverified</button>
        <button class="ex-filter" data-filter="disputed">Disputed</button>
        <button class="ex-filter" data-filter="theoretical">Theoretical</button>
        <button class="ex-filter" data-filter="em">Electromagnetic</button>
        <button class="ex-filter" data-filter="nuclear">Nuclear</button>
        <button class="ex-filter" data-filter="gravity">Anti-Gravity</button>
        <button class="ex-filter" data-filter="zpe">Zero-Point</button>
        <button class="ex-filter" data-filter="acoustic">Acoustic</button>
        <button class="ex-filter" data-filter="magnetic">Magnetic</button>
        <button class="ex-filter" data-filter="plasma">Plasma / Fusion</button>
        <button class="ex-filter" data-filter="mechanical">Mechanical</button>
        <button class="ex-filter" data-filter="chemical">Chemical</button>
        <button class="ex-filter" data-filter="quantum">Quantum</button>
        <button class="ex-filter" data-filter="propulsion">Propulsion</button>
        <button class="ex-filter" data-filter="wireless">Wireless Power</button>
        <button class="ex-filter" data-filter="thermal">Thermal</button>
        <button class="ex-filter" data-filter="electrolysis">Electrolysis</button>
        <button class="ex-filter" data-filter="biological">Biological</button>
        <button class="ex-filter" data-filter="resonance">Resonance</button>
        <button class="ex-filter" data-filter="optics">Optics</button>
        <button class="ex-filter" data-filter="particle">Particle Physics</button>
        <button class="ex-filter" data-filter="battery">Battery / Storage</button>
        <button class="ex-filter" data-filter="renewable">Renewable Energy</button>
        <button class="ex-filter" data-filter="historical">Historical</button>
        <button class="ex-filter" data-filter="bioelectric">Bioelectricity</button>
        <button class="ex-filter" data-filter="relativity">Relativity</button>
        <button class="ex-filter" data-filter="computing">Quantum Computing</button>
    </div>

    <div class="ex-grid" id="experimentGrid"></div>

    <div class="ex-footer-stats">
        <span id="expCount">0</span> experiments loaded &middot;
        Created by Alfred &middot;
        <a href="/circuit-simulator.php">Open in Circuit Simulator</a> &middot;
        <a href="/spacetime-simulator.php">Spacetime Simulator</a>
    </div>
</div>

<script>
(() => {
'use strict';

// ══════════════════════════════════════════════
//  EXPERIMENT DATABASE — Every scientist
// ══════════════════════════════════════════════

const EXPERIMENTS = [
// ─── VERIFIED ───
{
    id: 'tesla-coil',
    name: 'Tesla Coil',
    researcher: 'Nikola Tesla (1891)',
    status: 'verified',
    tags: ['em'],
    icon: 'fa-bolt',
    color: '#FF3366',
    description: 'Air-core resonant transformer with spark gap. Achieves millions of volts through resonant rise. The foundation of all wireless energy transfer.',
    formula: 'f₀ = 1/(2π√LC)  •  V_out = V_in × √(C_p/C_s)',
    physics: {
        type: 'tesla',
        resonantFreq: 250000,
        voltageGain: 100
    }
},
{
    id: 'casimir-effect',
    name: 'Casimir Effect',
    researcher: 'Hendrik Casimir (1948) — verified Lamoreaux 1997',
    status: 'verified',
    tags: ['zpe'],
    icon: 'fa-compress-arrows-alt',
    color: '#00FFCC',
    description: 'Quantum vacuum fluctuations between nanoscale parallel plates create measurable attractive force. The only verified zero-point energy extraction.',
    formula: 'F = -π²ℏcA/(240d⁴)  •  E = -π²ℏcA/(720d³)',
    physics: {
        type: 'casimir',
        plateGap: 100e-9,
        plateArea: 0.01
    }
},
{
    id: 'schumann-resonance',
    name: 'Schumann Resonance',
    researcher: 'W.O. Schumann (1952) — verified',
    status: 'verified',
    tags: ['em','zpe'],
    icon: 'fa-globe-americas',
    color: '#00DDFF',
    description: 'Earth-ionosphere cavity electromagnetic resonance at 7.83 Hz fundamental. Natural standing waves that encircle the planet.',
    formula: 'f_n = (c/2πR_earth) × √(n(n+1))  •  f₁ = 7.83 Hz',
    physics: {
        type: 'schumann',
        fundamentalFreq: 7.83,
        harmonics: 6
    }
},
{
    id: 'faraday-induction',
    name: 'Electromagnetic Induction',
    researcher: 'Michael Faraday (1831) — verified',
    status: 'verified',
    tags: ['em'],
    icon: 'fa-magnet',
    color: '#FFD700',
    description: 'A changing magnetic flux through a circuit induces an EMF. The basis of generators, transformers, and all modern electrical power.',
    formula: 'EMF = -dΦ/dt = -N × dB/dt × A',
    physics: {
        type: 'induction',
        turns: 100,
        fluxRate: 1.0
    }
},
{
    id: 'joule-thief',
    name: 'Joule Thief',
    researcher: 'Z. Kaparnik (1999) — verified',
    status: 'verified',
    tags: ['em'],
    icon: 'fa-lightbulb',
    color: '#88FF00',
    description: 'Blocking oscillator that lights an LED from a nearly dead 1.5V battery. Voltage boosting via flyback principle.',
    formula: 'V_out = V_in × N₂/N₁ (during flyback)',
    physics: {
        type: 'oscillator',
        frequency: 50000,
        gain: 3
    }
},

// ─── UNVERIFIED / CLAIMED ───
{
    id: 'hutchison-effect',
    name: 'Hutchison Effect',
    researcher: 'John Hutchison (1979)',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-radiation',
    color: '#FF00FF',
    description: 'Overlapping RF fields from Tesla coil + Van de Graaff + microwave source creating anomalous effects: levitation, metal fracturing, material transmutation in the interference zone.',
    formula: 'E_int = E₁ × E₂ × cos(ΔΦ)  •  Zone overlap at λ/4 nodes',
    physics: {
        type: 'interference',
        freq1: 1e6,
        freq2: 2.45e9,
        overlapRadius: 50
    }
},
{
    id: 'bedini-ssg',
    name: 'Bedini SSG Motor',
    researcher: 'John Bedini (1984)',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-sync-alt',
    color: '#00CCFF',
    description: 'Simplified School Girl motor with back-EMF capture. Bipolar transistor drives bifilar coil, captures radiant spike into secondary battery. Claims COP > 1.',
    formula: 'COP = E_out/E_in  •  E = ½LI²η  •  V_spike = -L(dI/dt)',
    physics: {
        type: 'motor-pulse',
        pulseFreq: 120,
        coilL: 0.1,
        backEMFpeak: 200
    }
},
{
    id: 'don-smith',
    name: 'Don Smith Resonance Device',
    researcher: 'Don Smith (1990s)',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-wave-square',
    color: '#FF8800',
    description: 'Resonant step-up with L-C tanks and frequency multiplication. Parametric amplification claimed to extract ambient EM energy.',
    formula: 'V_step = V × (f₂/f₁) × (N₂/N₁)  •  G = (C₁/C₂)(ω₂/ω₁)',
    physics: {
        type: 'resonant-step',
        freqRatio: 5,
        turnsRatio: 20
    }
},
{
    id: 'searl-seg',
    name: 'Searl Effect Generator (SEG)',
    researcher: 'John Searl (1946)',
    status: 'unverified',
    tags: ['em','gravity','magnetic'],
    icon: 'fa-circle-notch',
    color: '#FF66AA',
    description: 'Rotating neodymium roller sets orbiting ring magnets via magnetic ramp effect. Claimed anomalous EMF, weight loss, and temperature drop. Three concentric ring/roller layers.',
    formula: 'V_seg = B × dA/dt × N_rollers  •  B_ramp = μ₀Mrω/(4πR³)',
    physics: {
        type: 'seg',
        rollerCount: 12,
        ringLayers: 3,
        rotationSpeed: 0.02
    }
},
{
    id: 'floyd-sweet-vta',
    name: 'Floyd Sweet VTA',
    researcher: 'Floyd Sweet (1988)',
    status: 'unverified',
    tags: ['em','zpe','magnetic'],
    icon: 'fa-atom',
    color: '#CC66FF',
    description: 'Vacuum Triode Amplifier using specially conditioned barium ferrite magnets with drive coils at 60Hz. Claimed 500W out from milliwatt input. Output was "cold" — opposite sign current.',
    formula: 'P_out = V_rms × I_rms × cos(φ)  •  B_cond = B_r sin(2πf_c t) e^{-t/τ}',
    physics: {
        type: 'vta',
        driveFreq: 60,
        conditionFreq: 10000,
        claimed_COP: 1500000
    }
},
{
    id: 'bearden-meg',
    name: 'Bearden MEG',
    researcher: 'Tom Bearden (2002)',
    status: 'unverified',
    tags: ['em','magnetic'],
    icon: 'fa-project-diagram',
    color: '#FF9933',
    description: 'Motionless Electromagnetic Generator. Permanent magnet with dual flux paths switched by control coils. Claims COP > 1 via asymmetric A-potential from vacuum.',
    formula: 'COP = (P_out + P_core)/P_in  •  dΦ/dt = N × A × dB/dt',
    physics: {
        type: 'meg',
        switchFreq: 500,
        fluxPaths: 2
    }
},
{
    id: 'scalar-wave',
    name: 'Scalar Wave Transmission',
    researcher: 'K. Meyl / T.E. Bearden',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-satellite-dish',
    color: '#33CCFF',
    description: 'Longitudinal scalar wave transmitter using bifilar Tesla pancake coils. Transmitter-receiver pair claims superluminal signal and wireless power at Schumann frequency.',
    formula: 'Φ_s = E × B/(μ₀ε₀)  •  f_schumann = 7.83 Hz',
    physics: {
        type: 'scalar',
        txFreq: 7.83,
        coilPairs: 2
    }
},
{
    id: 'rodin-coil',
    name: 'Rodin Vortex Coil',
    researcher: 'Marko Rodin',
    status: 'unverified',
    tags: ['em','magnetic'],
    icon: 'fa-yin-yang',
    color: '#AA66FF',
    description: 'Toroidal coil wound in 3-6-9 vortex mathematics pattern. Claims unique magnetic field topology with zero-point energy coupling through number pattern 1-2-4-8-7-5.',
    formula: '3-6-9 pattern: 1→2→4→8→7→5→1 (doubling mod 9)',
    physics: {
        type: 'vortex',
        pattern: [1,2,4,8,7,5],
        axis: [3,6,9]
    }
},

// ─── NUCLEAR / LENR ───
{
    id: 'pons-fleischmann',
    name: 'Pons-Fleischmann Cold Fusion',
    researcher: 'Martin Fleischmann & Stanley Pons (1989)',
    status: 'disputed',
    tags: ['nuclear','zpe'],
    icon: 'fa-fire',
    color: '#FF4444',
    description: 'Palladium cathode in D₂O heavy water electrolytic cell. Reported excess heat far beyond chemical explanation. The original LENR experiment that shocked the world.',
    formula: 'Q_excess = Q_out - Q_in - Q_chem  •  E_barrier = e²/(4πε₀r)',
    physics: {
        type: 'lenr',
        deuteriumLoading: 0.9,
        excessHeat: 20,
        cathode: 'Pd'
    }
},
{
    id: 'rossi-ecat',
    name: 'Rossi E-Cat (Ni-H)',
    researcher: 'Andrea Rossi (2011)',
    status: 'disputed',
    tags: ['nuclear'],
    icon: 'fa-fire-alt',
    color: '#FF6633',
    description: 'Nickel-hydrogen reactor with LiAlH₄ fuel heated to ~1200°C. Claims excess heat from Ni-58→Ni-62 transmutation. COP of 3-6 claimed but independently contested.',
    formula: 'COP = Q_out/Q_in  •  Q_nuclear = Δm × c²',
    physics: {
        type: 'lenr',
        deuteriumLoading: 0,
        excessHeat: 50,
        cathode: 'Ni'
    }
},

// ─── WATER SPLITTING ───
{
    id: 'stan-meyer-wfc',
    name: 'Stan Meyer Water Fuel Cell',
    researcher: 'Stanley Meyer (1990)',
    status: 'disputed',
    tags: ['electrolysis','em'],
    icon: 'fa-tint',
    color: '#00BBFF',
    description: 'Resonant water-splitting using pulsed DC at the dielectric resonant frequency of the water capacitor. Claims gas production exceeding Faraday maximum via voltage intensification.',
    formula: 'f_res = c/(2d√ε_r)  •  V_out = (L₂/L₁)V_in × Q  •  n_H₂ = It/(2F)',
    physics: {
        type: 'wfc',
        pulseFreq: 21400,
        tubeGap: 1.5e-3,
        waterEps: 80
    }
},

// ─── ACOUSTIC / VIBRATIONAL ───
{
    id: 'keely-sympathetic',
    name: 'Keely Sympathetic Vibratory Engine',
    researcher: 'John Keely (1872)',
    status: 'unverified',
    tags: ['acoustic','zpe'],
    icon: 'fa-music',
    color: '#FFB800',
    description: 'Resonant acoustic engine using tuning forks at sympathetic frequencies. Triadic resonance (1:2:3 harmonic ratio) creates constructive interference disintegrating water and moving machinery.',
    formula: 'f_symp = f₀(n+1)/n  •  Triad = 1:2:3 harmonic ratio',
    physics: {
        type: 'acoustic',
        fundamental: 440,
        harmonics: [1,2,3],
        resonateFreq: 42800
    }
},
{
    id: 'schauberger-repulsine',
    name: 'Schauberger Repulsine',
    researcher: 'Viktor Schauberger (1940s)',
    status: 'unverified',
    tags: ['thermal','zpe','gravity'],
    icon: 'fa-hurricane',
    color: '#00FFAA',
    description: '"Comprehend and copy nature." Implosion turbine using centripetal vortex flow — cool inward spiral creates negative pressure zone. Water vortex concentrates energy inward, opposite of explosion.',
    formula: 'v_imp = √(2gh × T_cold/T_hot)  •  ΔP = ρω²r²/2',
    physics: {
        type: 'vortex-flow',
        direction: 'inward',
        tempRatio: 0.7,
        spiralArms: 8
    }
},

// ─── ANTI-GRAVITY ───
{
    id: 'grebennikov-cse',
    name: 'Grebennikov CSE Platform',
    researcher: 'Viktor Grebennikov (1988)',
    status: 'unverified',
    tags: ['gravity','zpe'],
    icon: 'fa-feather-alt',
    color: '#88DDFF',
    description: 'Cavity Structure Effect from insect chitin microstructures. Nested arrays of micro-cavities create Casimir-like force gradients. Claims levitation from multi-cavity geometry stacking.',
    formula: 'F_cse = -dE_casimir/dz × N_cavities  •  f_cavity = c/(2d)',
    physics: {
        type: 'cse',
        cavityCount: 200,
        cavitySize: 1e-5,
        layers: 5
    }
},
{
    id: 'podkletnov-shield',
    name: 'Podkletnov Gravity Shield',
    researcher: 'Eugene Podkletnov (1992)',
    status: 'disputed',
    tags: ['gravity'],
    icon: 'fa-shield-alt',
    color: '#b388ff',
    description: 'Spinning superconducting YBCO disk (2-6M RPM) above AC magnetic coils at ~70K. Reported 0.3-2% weight reduction above the spinning disk. NASA attempted replication.',
    formula: 'Δg/g = f(ω, B, T_c)  •  F_grav = mcΔv/Δt',
    physics: {
        type: 'gravity-shield',
        rpm: 5000,
        weightLoss: 0.02,
        diskRadius: 0.15
    }
},
{
    id: 'biefeld-brown',
    name: 'Biefeld-Brown Lifter',
    researcher: 'T. Townsend Brown (1928)',
    status: 'disputed',
    tags: ['gravity','em'],
    icon: 'fa-arrow-up',
    color: '#DDBB00',
    description: 'Asymmetric capacitor with high voltage (~30kV). Produces thrust toward smaller electrode. Ion wind or electrogravitic effect? Works in vacuum per some claims.',
    formula: 'F = kε₀AV²/d²  •  T = CV(dV/dt)  •  F_ion = Id/μ_ion',
    physics: {
        type: 'lifter',
        voltage: 30000,
        gap: 0.03,
        asymmetry: 10
    }
},
{
    id: 'yildiz-magnetic-motor',
    name: 'Yildiz Magnetic Motor',
    researcher: 'Muammer Yildiz (2013)',
    status: 'unverified',
    tags: ['magnetic'],
    icon: 'fa-cog',
    color: '#FF88CC',
    description: 'Permanent magnet motor demonstrated at Delft University. Rotor with carefully angled magnets produces continuous rotation with no electrical input. Inspected by professors.',
    formula: 'T = r × F_mag  •  P = Tω',
    physics: {
        type: 'magnetic-motor',
        magnets: 16,
        rotorLayers: 3,
        rpm: 600
    }
},

// ─── TESLA EXTENDED ───
{
    id: 'tesla-wardenclyffe',
    name: 'Tesla Wardenclyffe Tower',
    researcher: 'Nikola Tesla (1901–1917)',
    status: 'unverified',
    tags: ['wireless','em'],
    icon: 'fa-broadcast-tower',
    color: '#FF5588',
    description: 'Global wireless power & communication tower on Long Island. 187-ft tower with 68-ft copper dome. Earth resonance at ~7.83 Hz to transmit power through ground and ionosphere worldwide.',
    formula: 'P_rad = (2π²f²μ₀)/(3c) × (I·A)²  •  f = c/(2πR_earth)',
    physics: { type: 'tower', height: 187, domeRadius: 34 }
},
{
    id: 'tesla-magnifying-transmitter',
    name: 'Tesla Magnifying Transmitter',
    researcher: 'Nikola Tesla (1899, Colorado Springs)',
    status: 'verified',
    tags: ['em','wireless','resonance'],
    icon: 'fa-satellite-dish',
    color: '#FF4488',
    description: 'Extra coil resonant transformer producing 12 million volts. Lit 200 lamps 26 miles away wirelessly. The most powerful Tesla coil ever built — created 135-foot lightning bolts.',
    formula: 'V_out = V_in × √(C_p/C_s) × Q_extra  •  P = V²/Z',
    physics: { type: 'magnifying-tx', voltage: 12e6, range: 42000 }
},
{
    id: 'tesla-turbine',
    name: 'Tesla Bladeless Turbine',
    researcher: 'Nikola Tesla (1913)',
    status: 'verified',
    tags: ['mechanical','thermal'],
    icon: 'fa-fan',
    color: '#FF6644',
    description: 'Bladeless turbine using smooth parallel disks. Fluid enters tangentially and spirals inward via boundary layer adhesion. 95% theoretical efficiency. Works with steam, gas, or water.',
    formula: 'P = ρQΔP/η  •  τ = μ(dv/dr)A  •  Re = ρvd/μ',
    physics: { type: 'turbine', disks: 12, rpm: 16000 }
},
{
    id: 'tesla-ozone',
    name: 'Tesla Ozone Generator',
    researcher: 'Nikola Tesla (1896)',
    status: 'verified',
    tags: ['em','chemical'],
    icon: 'fa-cloud',
    color: '#88CCFF',
    description: 'High-frequency corona discharge generator splitting O₂ into atomic oxygen recombining as O₃. Patent US568177. Still used in water purification today.',
    formula: '3O₂ → 2O₃  •  E_dissoc = 498 kJ/mol',
    physics: { type: 'corona', freq: 500000, voltage: 50000 }
},

// ─── RADIANT ENERGY ───
{
    id: 'moray-radiant',
    name: 'Moray Radiant Energy Device',
    researcher: 'Thomas Henry Moray (1920s–1930s)',
    status: 'unverified',
    tags: ['zpe','em'],
    icon: 'fa-sun',
    color: '#FFD700',
    description: 'Claimed 50kW from a cold-cathode "Swedish stone" (germanium?) detector tube tapping cosmic radiant energy. Demonstrated to scientists and state officials. Device destroyed by sabotage.',
    formula: 'P_out = 50kW  •  COP = P_out/P_trigger  •  E = hf (cosmic)',
    physics: { type: 'radiant', power: 50000, tubes: 29 }
},
{
    id: 'hendershot-generator',
    name: 'Hendershot Generator',
    researcher: 'Lester Hendershot (1928)',
    status: 'unverified',
    tags: ['em','magnetic'],
    icon: 'fa-compass',
    color: '#44BBAA',
    description: 'Fuelless motor/generator using basket-weave coils tuned to Earth\'s magnetic field. Claimed to extract energy from the geomagnetic flux. Demonstrated publicly, then suppressed.',
    formula: 'EMF = B_earth × A × ω × cos(θ)  •  B_earth ≈ 50μT',
    physics: { type: 'earth-field', coils: 2, earthB: 50e-6 }
},
{
    id: 'hans-coler',
    name: 'Coler Magnetstromapparat',
    researcher: 'Hans Coler (1925–1945)',
    status: 'unverified',
    tags: ['magnetic','zpe'],
    icon: 'fa-magnet',
    color: '#99AADD',
    description: 'German device using permanent magnets with copper coils in hexagonal arrangement. British Intelligence investigated post-WWII (Report No. 1043). Claimed to produce energy from magnetic field arrangement.',
    formula: 'Φ_net = ΣΦ_i × cos(θ_ij)  •  P = V²/R_load',
    physics: { type: 'hex-magnet', magnets: 6, arrangement: 'hexagonal' }
},
{
    id: 'edwin-gray',
    name: 'Edwin Gray EMA Motor',
    researcher: 'Edwin Gray (1973)',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-bolt',
    color: '#88FF44',
    description: 'Electromagnetic Association motor using "cold electricity" from specially conditioned spark gap. High voltage capacitor discharge through conversion tube. Patented US3890548.',
    formula: 'E_cold = CV² × η_conversion  •  V_spike = -L(dI/dt)',
    physics: { type: 'ema-motor', sparkGap: true, voltage: 3000 }
},
{
    id: 'newman-energy-machine',
    name: 'Newman Energy Machine',
    researcher: 'Joseph Newman (1979)',
    status: 'disputed',
    tags: ['em','magnetic'],
    icon: 'fa-cogs',
    color: '#CCAA44',
    description: 'Massive copper coil (miles of wire) with permanent magnet core and commutator. Claims gyroscopic reaction of atoms in copper produce excess energy. Patent denied by USPTO, challenged in court.',
    formula: 'E = mc² (atomic gyroscopic)  •  F = BIL × N_turns',
    physics: { type: 'newman', coilMiles: 55, magnetMass: 200 }
},
{
    id: 'testatika',
    name: 'Testatika (Methernitha)',
    researcher: 'Paul Baumann / Methernitha (1980s)',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-charging-station',
    color: '#DDAA66',
    description: 'Swiss commune\'s Wimshurst-type electrostatic generator with Leyden jars powering the entire Methernitha community. Two counter-rotating disks. Claims 3-5kW continuous. Never replicated outside commune.',
    formula: 'V = Q/C  •  E = ½CV²  •  I = dQ/dt × N_sectors',
    physics: { type: 'electrostatic', disks: 2, sectors: 36, power: 3000 }
},
{
    id: 'kapanadze',
    name: 'Kapanadze Generator',
    researcher: 'Tariel Kapanadze (2000s)',
    status: 'unverified',
    tags: ['em','resonance'],
    icon: 'fa-wave-square',
    color: '#44DDAA',
    description: 'Georgian inventor\'s self-running generator. Tesla-inspired spark gap with resonant transformer. Demonstrated on Georgian national TV powering multiple appliances. Turkey attempted partnership.',
    formula: 'f_res = 1/(2π√LC)  •  COP = P_load/P_excite',
    physics: { type: 'kapanadze', sparkGap: true, resonantF: 200000 }
},
{
    id: 'steven-mark-tpu',
    name: 'Steven Mark TPU',
    researcher: 'Steven Mark (2000s)',
    status: 'unverified',
    tags: ['em','magnetic','resonance'],
    icon: 'fa-ring',
    color: '#FF66DD',
    description: 'Toroidal Power Unit — small toroidal device with specially wound coils, demonstrated powering 100W bulbs. Claimed to tap rotating magnetic field resonance. Multiple video demonstrations.',
    formula: 'B_tor = μ₀NI/(2πr)  •  Φ = B × A_cross  •  P = VIcos(φ)',
    physics: { type: 'toroidal', radius: 0.08, turns: 200 }
},
{
    id: 'adams-motor',
    name: 'Adams Pulsed Motor',
    researcher: 'Robert Adams (New Zealand, 1969)',
    status: 'unverified',
    tags: ['em','magnetic'],
    icon: 'fa-sync',
    color: '#55CCAA',
    description: 'Permanent magnet motor-generator with precisely timed electromagnetic pulses. Claims COP > 1 with Back-EMF harvesting. Published in Nexus Magazine. Multiple independent replications claimed.',
    formula: 'COP = (P_mech + P_backEMF)/P_in  •  E = ½LI²',
    physics: { type: 'adams', magnets: 4, pulseWidth: 0.001 }
},
{
    id: 'tewari-spg',
    name: 'Tewari Space Power Generator',
    researcher: 'Paramahamsa Tewari (India, 1990s)',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-atom',
    color: '#44AAFF',
    description: 'Space Vortex Theory — homopolar generator with rotating conducting cylinder in magnetic field. Former nuclear engineer (BARC). Claims COP of 2.38 verified by Kirloskar Electric.',
    formula: 'V = BωR²/2  •  P = V²/R_load  •  COP = P_out/P_in',
    physics: { type: 'homopolar', rpm: 3000, fieldB: 0.5 }
},
{
    id: 'chernetsky-discharge',
    name: 'Chernetsky Self-Generating Discharge',
    researcher: 'Alexander Chernetsky (USSR, 1970s)',
    status: 'unverified',
    tags: ['plasma','zpe'],
    icon: 'fa-bolt',
    color: '#FF44AA',
    description: 'Plasma discharge in low-pressure gas producing more power than input. "Self-generating" electric discharge mode. Moscow Aviation Institute research. Published in Soviet physics journals.',
    formula: 'P_out = I × V_arc × t  •  P_excess = P_out - P_in',
    physics: { type: 'plasma-discharge', pressure: 0.1, voltage: 5000 }
},
{
    id: 'correa-pagd',
    name: 'Correa PAGD Reactor',
    researcher: 'Paulo & Alexandra Correa (1990s)',
    status: 'unverified',
    tags: ['plasma','zpe'],
    icon: 'fa-bolt',
    color: '#FF6688',
    description: 'Pulsed Abnormal Glow Discharge in vacuum tubes. Auto-electronic emission sustains anomalous plasma regime producing excess electrical energy. Several US patents granted.',
    formula: 'P_PAGD = V_breakdown × I_pulse × f_rep  •  COP = E_out/E_in',
    physics: { type: 'pagd', pressure: 0.5, pulseRate: 1000 }
},

// ─── ORGONE / BIOFIELD ───
{
    id: 'reich-orgone',
    name: 'Reich Orgone Accumulator',
    researcher: 'Wilhelm Reich (1940s)',
    status: 'disputed',
    tags: ['biological','zpe'],
    icon: 'fa-box',
    color: '#66BB44',
    description: 'Layered organic/metallic box concentrating "orgone energy" — a claimed universal life force. Metal-organic-metal-organic layering. Temperature anomaly reported. FDA burned his books and lab.',
    formula: 'T_inside > T_outside (anomalous)  •  ΔT = f(layers, area)',
    physics: { type: 'orgone', layers: 6, material: 'steel-wool' }
},
{
    id: 'reich-cloudbuster',
    name: 'Reich Cloudbuster',
    researcher: 'Wilhelm Reich (1950s)',
    status: 'disputed',
    tags: ['biological','em'],
    icon: 'fa-cloud-rain',
    color: '#5599CC',
    description: 'Array of hollow metal tubes grounded to water, pointed at the sky to "draw" orgone and affect weather. Multiple witnesses documented apparent cloud formation and rain. Legal persecution followed.',
    formula: 'Flux = n_tubes × A_tube × cos(θ)  •  grounding to H₂O',
    physics: { type: 'cloudbuster', tubes: 6, length: 3 }
},

// ─── FREQUENCY THERAPY ───
{
    id: 'rife-machine',
    name: 'Rife Beam Ray',
    researcher: 'Royal Raymond Rife (1930s)',
    status: 'disputed',
    tags: ['em','resonance','biological'],
    icon: 'fa-microscope',
    color: '#AA44FF',
    description: 'Mortal Oscillatory Rate (MOR) — every pathogen has a resonant frequency at which it shatters. Universal Microscope achieved 60,000× magnification. Claimed cancer remission in 1934 clinical trials.',
    formula: 'f_MOR = c/(2L_organism)  •  destructive resonance at f₀',
    physics: { type: 'rife', frequency: 11780000, harmonics: true }
},
{
    id: 'lakhovsky-mwo',
    name: 'Lakhovsky Multi-Wave Oscillator',
    researcher: 'Georges Lakhovsky (1920s–1930s)',
    status: 'disputed',
    tags: ['em','resonance','biological'],
    icon: 'fa-circle-notch',
    color: '#DDAA44',
    description: 'Concentric circular antennas broadcasting ALL frequencies simultaneously. Cells resonate at their natural frequency and "recharge." Used in French, Italian, and Swedish hospitals.',
    formula: 'f_n = c/(2π r_n)  •  Σf = f₁ + f₂ + ... + f_n (all harmonics)',
    physics: { type: 'mwo', rings: 12, freqRange: [500000, 3e9] }
},

// ─── FUSION / PLASMA ───
{
    id: 'farnsworth-fusor',
    name: 'Farnsworth Fusor',
    researcher: 'Philo Farnsworth (1964)',
    status: 'verified',
    tags: ['nuclear','plasma'],
    icon: 'fa-globe',
    color: '#FF6655',
    description: 'Inertial Electrostatic Confinement (IEC) fusion. Deuterium ions accelerated inward by concentric spherical grids. Produces neutrons — verifiable fusion. TV inventor\'s greatest unknown achievement.',
    formula: 'E_ion = qV  •  σ(E) = fusion cross-section  •  R = n²⟨σv⟩V',
    physics: { type: 'fusor', voltage: 40000, gridRadius: 0.05 }
},
{
    id: 'polywell-fusion',
    name: 'Polywell Fusion',
    researcher: 'Robert Bussard (2006)',
    status: 'unverified',
    tags: ['nuclear','plasma','magnetic'],
    icon: 'fa-dice-d6',
    color: '#FF4444',
    description: 'Polyhedral magnetic cusp confinement with electron injection. Combines IEC with magnetic mirror. WB-6 demonstrated net energy gain claims. Navy funded. Bussard\'s famous "Should Google Go Nuclear?" talk.',
    formula: 'β = nkT/(B²/2μ₀)  •  P_fusion = n²⟨σv⟩E_f V/4',
    physics: { type: 'polywell', coils: 6, fieldB: 0.1 }
},
{
    id: 'focus-fusion',
    name: 'Focus Fusion (Dense Plasma)',
    researcher: 'Eric Lerner / LPPFusion',
    status: 'unverified',
    tags: ['nuclear','plasma'],
    icon: 'fa-crosshairs',
    color: '#FF3333',
    description: 'Dense Plasma Focus — pB11 aneutronic fusion. Plasma pinch creates plasmoid at 1.8 billion degrees. No neutron radiation. If achieved, clean direct-to-electricity conversion. Crowdfunded research ongoing.',
    formula: 'p + ¹¹B → 3α + 8.7 MeV  •  T > 1.5×10⁹ K',
    physics: { type: 'dpf', plasmoidTemp: 1.8e9, fuel: 'pB11' }
},
{
    id: 'sonoluminescence',
    name: 'Sonoluminescence',
    researcher: 'H. Frenzel & H. Schultes (1934)',
    status: 'verified',
    tags: ['acoustic','plasma','quantum'],
    icon: 'fa-star',
    color: '#AADDFF',
    description: 'Sound waves in liquid create a collapsing bubble that emits a flash of light — temperatures exceed 20,000K momentarily. Single-bubble SL is clockwork-precise. Mechanism still debated. Possible fusion?',
    formula: 'R(t) = R₀(P₀/P(t))^(1/3γ)  •  T_collapse > 20,000K',
    physics: { type: 'sono', frequency: 25000, bubbleRadius: 50e-6 }
},
{
    id: 'sonofusion',
    name: 'Sonofusion (Bubble Fusion)',
    researcher: 'Rusi Taleyarkhan (2002)',
    status: 'disputed',
    tags: ['nuclear','acoustic'],
    icon: 'fa-bullseye',
    color: '#FF8866',
    description: 'Acoustic cavitation in deuterated acetone producing neutrons consistent with D-D fusion. Published in Science. Later disputed, but Oak Ridge initially confirmed neutron detection.',
    formula: 'D + D → He³ + n (2.45 MeV)  •  T_bubble > 10⁷ K',
    physics: { type: 'sono-fusion', fuel: 'deuterium', pressure: 15e5 }
},
{
    id: 'muon-fusion',
    name: 'Muon-Catalyzed Fusion',
    researcher: 'Alvarez et al. (1956) — verified',
    status: 'verified',
    tags: ['nuclear','quantum'],
    icon: 'fa-atom',
    color: '#FF5599',
    description: 'Muon replaces electron in hydrogen, shrinking atom 207×. Nuclei brought close enough for fusion at room temperature. Real and verified — only limited by muon lifetime (2.2μs) and cost of production.',
    formula: 'r_muonic = r_Bohr/207  •  τ_muon = 2.2μs  •  ~150 fusions/muon',
    physics: { type: 'muon', lifetime: 2.2e-6, fusionsPerMuon: 150 }
},
{
    id: 'tokamak',
    name: 'Tokamak Magnetic Confinement',
    researcher: 'Sakharov & Tamm (1950s) — ITER ongoing',
    status: 'verified',
    tags: ['nuclear','plasma','magnetic'],
    icon: 'fa-ring',
    color: '#FF6600',
    description: 'Toroidal magnetic confinement plasma at 150 million °C. D-T fusion with Q > 1 achieved at JET (1997). ITER aims Q = 10. The mainstream path to fusion energy.',
    formula: 'nτT > 5×10²¹ keV·s/m³  •  D + T → He⁴ + n + 17.6 MeV',
    physics: { type: 'tokamak', majorR: 6.2, minorR: 2.0, fieldB: 5.3 }
},
{
    id: 'z-pinch',
    name: 'Z-Pinch (Sandia Z-Machine)',
    researcher: 'Sandia National Labs',
    status: 'verified',
    tags: ['nuclear','plasma'],
    icon: 'fa-compress-alt',
    color: '#FF4422',
    description: 'Strongest X-ray source on Earth. 26 million amps through tungsten wire array creating plasma implosion reaching 2 billion degrees. 290 TW peak power — more than all power plants combined.',
    formula: 'F = μ₀I²/(4πr)  •  P_peak = 290 TW  •  T > 2×10⁹ K',
    physics: { type: 'z-pinch', current: 26e6, wires: 240 }
},
{
    id: 'iter-fusion',
    name: 'ITER (International Fusion)',
    researcher: 'International consortium (2025+)',
    status: 'theoretical',
    tags: ['nuclear','plasma','magnetic'],
    icon: 'fa-globe-europe',
    color: '#FF7744',
    description: '35-nation project in Cadarache, France. World\'s largest tokamak — 23,000 tonnes. Goal: Q ≥ 10, producing 500MW from 50MW input. First plasma expected soon. $22B+ budget.',
    formula: 'Q = P_fusion/P_input ≥ 10  •  P_fusion = 500 MW',
    physics: { type: 'tokamak', majorR: 6.2, minorR: 2.0, fieldB: 5.3 }
},

// ─── WATER / ELECTROLYSIS ───
{
    id: 'browns-gas',
    name: 'Brown\'s Gas (HHO)',
    researcher: 'Yull Brown (1974)',
    status: 'disputed',
    tags: ['electrolysis','chemical'],
    icon: 'fa-fire',
    color: '#44CCFF',
    description: 'Orthohydrogen-parahydrogen mixed gas via special electrolysis. Claims unusual properties: variable flame temperature (275°C to 6000°C), implosion instead of explosion, metal sublimation.',
    formula: '2H₂O → 2H₂ + O₂  •  HHO flame T varies with target material',
    physics: { type: 'hho', electrolysis: true, gasType: 'mixed' }
},
{
    id: 'joe-cell',
    name: 'Joe Cell',
    researcher: 'Anonymous Australian (1990s)',
    status: 'unverified',
    tags: ['electrolysis','zpe'],
    icon: 'fa-car',
    color: '#33BBAA',
    description: 'Concentric stainless steel cylinders in water, charged with 12V. "Stages" of charging from seeding to breeding. Stage 3 cell allegedly runs internal combustion engine without fuel. Based on orgone concept.',
    formula: 'Stage 1: bubbles → Stage 2: coats → Stage 3: runs engine',
    physics: { type: 'joe-cell', cylinders: 5, voltage: 12 }
},

// ─── MECHANICAL PERPETUAL ───
{
    id: 'bessler-wheel',
    name: 'Bessler Perpetual Wheel',
    researcher: 'Johann Bessler / Orffyreus (1712–1717)',
    status: 'unverified',
    tags: ['mechanical','gravity'],
    icon: 'fa-dharmachakra',
    color: '#CCAA33',
    description: 'Self-rotating wheel demonstrated for decades across Germany. 12-foot wheel ran for 54 days locked in a sealed room (Weissenstein Castle). Examined by Leibniz and others. Secret mechanism died with Bessler.',
    formula: 'P_out = Mgv  •  torque = ΣF_i × r_i × sin(θ_i)',
    physics: { type: 'bessler', diameter: 3.7, rpm: 26, masses: 8 }
},
{
    id: 'clem-engine',
    name: 'Clem Engine',
    researcher: 'Richard Clem (1972)',
    status: 'unverified',
    tags: ['mechanical','thermal'],
    icon: 'fa-oil-can',
    color: '#BB8833',
    description: 'Conical rotor asphalt-sprayer pump ran self-sustaining after reaching operating temperature. Vegetable oil as working fluid heated to 300°F. Claimed to produce 350hp. Died of heart attack before demonstration.',
    formula: 'P = ΔP × Q  •  T_operating = 150°C  •  P_out = 350 hp',
    physics: { type: 'clem', cone: true, oilTemp: 150, hp: 350 }
},

// ─── GEET / PANTONE ───
{
    id: 'pantone-geet',
    name: 'Pantone GEET Reactor',
    researcher: 'Paul Pantone (1998)',
    status: 'disputed',
    tags: ['chemical','plasma','thermal'],
    icon: 'fa-smog',
    color: '#99BB44',
    description: 'Global Environmental Energy Technology — plasma reactor using endothermic reaction on fuel vapor passing through magnetized steel rod. Claims 300+ MPG equivalent. Multiple replications by hobbyists.',
    formula: 'CnHm + plasma → H₂ + CO + energy  •  η > Carnot limit?',
    physics: { type: 'geet', rodTemp: 400, plasma: true }
},

// ─── ANTI-GRAVITY EXTENDED ───
{
    id: 'otis-carr',
    name: 'Otis T. Carr OTC-X1',
    researcher: 'Otis T. Carr (1959)',
    status: 'unverified',
    tags: ['gravity','em','magnetic'],
    icon: 'fa-ufo',
    color: '#BB88FF',
    description: 'Circular foil craft based on "Utron" counter-rotating electromagnetic system. Carr claimed it was a fourth-dimensional vehicle. Demonstrated at Oklahoma City fairgrounds. Charged with fraud.',
    formula: 'F = m × a_nonlinear  •  Φ_rotating = B × A × ω',
    physics: { type: 'otc-x1', cones: 2, rotation: 'counter' }
},
{
    id: 'alcubierre-drive',
    name: 'Alcubierre Warp Drive',
    researcher: 'Miguel Alcubierre (1994)',
    status: 'theoretical',
    tags: ['gravity','quantum','propulsion'],
    icon: 'fa-rocket',
    color: '#6688FF',
    description: 'Warp metric solution to Einstein field equations. Contracts spacetime ahead, expands behind. Passengers in flat-space bubble. Originally required Jupiter-mass negative energy, reduced to ~700kg by Van Den Broeck, White.',
    formula: 'ds² = -dt² + (dx - v_s f(r_s)dt)² + dy² + dz²',
    physics: { type: 'warp', velocity: 10, bubbleRadius: 10 }
},
{
    id: 'emdrive',
    name: 'EmDrive (RF Resonant Cavity)',
    researcher: 'Roger Shawyer (2001)',
    status: 'disputed',
    tags: ['propulsion','em'],
    icon: 'fa-satellite',
    color: '#FF9944',
    description: 'Truncated cone RF cavity producing thrust without propellant. Measured by NASA Eagleworks (Harold White). Violates Newton\'s 3rd law. Dresden experiments found thermal artifact. Debate continues.',
    formula: 'F = 2P/c × Q × (1/d₁ - 1/d₂)  •  (Shawyer)',
    physics: { type: 'emdrive', frequency: 2.45e9, power: 700 }
},
{
    id: 'woodward-mach',
    name: 'Woodward Mach Effect Thruster',
    researcher: 'James Woodward (1990s)',
    status: 'unverified',
    tags: ['propulsion','gravity'],
    icon: 'fa-space-shuttle',
    color: '#AACC55',
    description: 'MEGA (Mach Effect Gravitational Assist) drive. Piezoelectric stack oscillated at resonance creates transient mass fluctuations per Mach\'s principle. NASA and SSI funded. Tiny thrust measured (~μN).',
    formula: 'δm = -(1/4πGρc²)(∂²E/∂t²)  •  F = δm × a',
    physics: { type: 'mach-thruster', frequency: 40000, thrust: 1e-6 }
},
{
    id: 'nasa-eagleworks',
    name: 'NASA Quantum Vacuum Thruster',
    researcher: 'Harold "Sonny" White (NASA, 2013)',
    status: 'theoretical',
    tags: ['quantum','propulsion','zpe'],
    icon: 'fa-user-astronaut',
    color: '#5588FF',
    description: 'Eagleworks lab at Johnson Space Center. Quantum vacuum plasma thruster concept. Also working on Alcubierre geometry with laser interferometer (White-Juday warp field interferometer).',
    formula: 'F = ΔpE/Δt  •  ρ_vac = ℏω³/(2π²c³)',
    physics: { type: 'quantum-thruster', labPower: 50, thrust: 1e-6 }
},

// ─── QUANTUM EFFECTS ───
{
    id: 'dynamic-casimir',
    name: 'Dynamic Casimir Effect',
    researcher: 'G.T. Moore (1970) — verified Chalmers 2011',
    status: 'verified',
    tags: ['quantum','zpe'],
    icon: 'fa-wave-square',
    color: '#44DDFF',
    description: 'Moving mirror at near-lightspeed converts virtual photons into REAL photons. Verified at Chalmers University (2011) using SQUID modifying boundary conditions at 10 GHz. Actual zero-point energy → light.',
    formula: 'N_photons ∝ (v/c)²  •  f_photon = f_mirror/2',
    physics: { type: 'dynamic-casimir', mirrorFreq: 10e9, velocity: 0.05 }
},
{
    id: 'aharonov-bohm',
    name: 'Aharonov-Bohm Effect',
    researcher: 'Aharonov & Bohm (1959) — verified',
    status: 'verified',
    tags: ['quantum','em'],
    icon: 'fa-vector-square',
    color: '#7799FF',
    description: 'Electrons affected by electromagnetic potential even where E and B fields are zero. Proves vector potential A is physically real, not just mathematical. Verified in electron diffraction experiments.',
    formula: 'Δφ = (e/ℏ) ∮ A·dl  •  phase shift without fields',
    physics: { type: 'ab-effect', solenoidB: 0.01, electronEnergy: 100 }
},
{
    id: 'lamb-shift',
    name: 'Lamb Shift',
    researcher: 'Willis Lamb (1947) — verified',
    status: 'verified',
    tags: ['quantum','zpe'],
    icon: 'fa-atom',
    color: '#88AAFF',
    description: 'Tiny energy difference between hydrogen 2S₁/₂ and 2P₁/₂ levels (~1 GHz) caused by vacuum fluctuations interacting with the electron. Proves the vacuum is not empty. Confirmed QED to 12 decimal places.',
    formula: 'ΔE = (α⁵mc²)/(6π) × ln(1/α)  •  f ≈ 1057.845 MHz',
    physics: { type: 'lamb-shift', freq: 1057.845e6, alpha: 1/137 }
},
{
    id: 'quantum-tunneling',
    name: 'Quantum Tunneling',
    researcher: 'Gamow, Gurney & Condon (1928) — verified',
    status: 'verified',
    tags: ['quantum'],
    icon: 'fa-door-open',
    color: '#66BBEE',
    description: 'Particle passes through classically forbidden energy barrier. Probability decreases exponentially with barrier width and height. Powers nuclear fusion in stars, tunnel diodes, STM microscopes, and flash memory.',
    formula: 'T = e^(-2κd)  •  κ = √(2m(V-E))/ℏ',
    physics: { type: 'tunneling', barrierHeight: 5, barrierWidth: 1e-10 }
},
{
    id: 'superconductivity',
    name: 'Superconductivity',
    researcher: 'H. K. Onnes (1911) — verified',
    status: 'verified',
    tags: ['quantum','em'],
    icon: 'fa-snowflake',
    color: '#88EEFF',
    description: 'Below critical temperature, resistance drops to EXACTLY zero. Cooper pairs of electrons form boson condensate via phonon interaction. Meissner effect expels all magnetic flux. Current flows forever.',
    formula: 'R = 0 (T < T_c)  •  B_inside = 0 (Meissner)  •  BCS gap: Δ = 1.764kT_c',
    physics: { type: 'superconductor', tc: 92, material: 'YBCO' }
},

// ─── ENERGY HARVESTING ───
{
    id: 'atmospheric-electricity',
    name: 'Atmospheric Electricity Harvester',
    researcher: 'Multiple (Tesla, Plauson, etc.)',
    status: 'verified',
    tags: ['em','zpe'],
    icon: 'fa-cloud-bolt',
    color: '#FFCC44',
    description: 'Earth\'s atmospheric potential gradient ≈ 100-150 V/m. At altitude, voltage reaches MV potential. Elevated antenna/balloon collects charge. Tesla, Hermann Plauson (US1540998), and others demonstrated working devices.',
    formula: 'V = E × h  •  E ≈ 100 V/m  •  P = V × I_leak',
    physics: { type: 'atmospheric', fieldE: 100, height: 100 }
},
{
    id: 'piezoelectric',
    name: 'Piezoelectric Energy Harvesting',
    researcher: 'Pierre & Jacques Curie (1880) — verified',
    status: 'verified',
    tags: ['mechanical','em'],
    icon: 'fa-hand-rock',
    color: '#AADD55',
    description: 'Mechanical stress on crystal (quartz, PZT) generates voltage. Reverse: voltage deforms crystal. Used in lighters, sonar, microphones, and now energy-harvesting floors, roads, and wearables.',
    formula: 'V = g₃₃ × t × σ  •  P = ½ε₃₃E₃²Vol × f',
    physics: { type: 'piezo', material: 'PZT', freq: 100 }
},
{
    id: 'thermoelectric',
    name: 'Thermoelectric Generator (TEG)',
    researcher: 'Thomas Seebeck (1821) — verified',
    status: 'verified',
    tags: ['thermal','em'],
    icon: 'fa-temperature-high',
    color: '#FF7733',
    description: 'Temperature difference across dissimilar conductors generates voltage (Seebeck effect). Reverse: voltage creates temperature difference (Peltier). Powers deep space probes (RTGs). No moving parts.',
    formula: 'V = S × ΔT  •  η = (T_H - T_C)/T_H × ZT/(1+ZT)',
    physics: { type: 'teg', seebeck: 200e-6, deltaT: 100 }
},
{
    id: 'stirling-engine',
    name: 'Stirling Engine',
    researcher: 'Robert Stirling (1816) — verified',
    status: 'verified',
    tags: ['thermal','mechanical'],
    icon: 'fa-fire',
    color: '#FF8844',
    description: 'External combustion engine using sealed gas (helium/hydrogen) cycling between hot and cold chambers. Theoretically achieves Carnot efficiency. Uses ANY heat source. Quiet, reliable, long-life.',
    formula: 'η_max = 1 - T_C/T_H  •  P = pVnRT × f',
    physics: { type: 'stirling', tempHot: 700, tempCold: 300, gas: 'helium' }
},

// ─── EARTH ENERGY / CRYSTAL ───
{
    id: 'crystal-cell',
    name: 'Crystal Cell Earth Battery',
    researcher: 'Various experimenters',
    status: 'unverified',
    tags: ['chemical','zpe'],
    icon: 'fa-gem',
    color: '#DD88FF',
    description: 'Rochelle salt or alum crystals grown between dissimilar metal electrodes produce persistent voltage (0.5-1V) for months without depletion beyond chemical explanation. Galvanic + piezo + unknown.',
    formula: 'V = E_galvanic + E_piezo + E_?  •  I = V/R_load',
    physics: { type: 'crystal', voltage: 0.8, material: 'rochelle-salt' }
},
{
    id: 'stubblefield-earth',
    name: 'Stubblefield Earth Battery',
    researcher: 'Nathan Stubblefield (1898)',
    status: 'unverified',
    tags: ['em','chemical'],
    icon: 'fa-seedling',
    color: '#55AA44',
    description: 'Kentucky farmer transmitted voice wirelessly using Earth current and special iron/carbon ground rods before Marconi. Demonstrated to thousands. Used telluric currents and ground conduction.',
    formula: 'V = E_chem + E_telluric  •  J_earth = σ × E_natural',
    physics: { type: 'earth-battery', rods: 2, depth: 1 }
},
{
    id: 'schappeller-force',
    name: 'Schappeller Glowing Magnetism',
    researcher: 'Karl Schappeller (Austria, 1920s)',
    status: 'unverified',
    tags: ['magnetic','zpe'],
    icon: 'fa-sun',
    color: '#FFDD33',
    description: 'Spherical device claimed to concentrate "primary magnetism" from the aether into a glowing plasma core. Self-sustaining once started. Some claim connection to Nazi bell (Die Glocke) research.',
    formula: 'B_primary = f(aether)  •  P_glow = ε₀E² × Vol',
    physics: { type: 'schappeller', sphereR: 0.15, glowing: true }
},

// ─── MODERN LENR ───
{
    id: 'brillouin-cecr',
    name: 'Brillouin CECR',
    researcher: 'Brillouin Energy (2009)',
    status: 'unverified',
    tags: ['nuclear','em'],
    icon: 'fa-fire-alt',
    color: '#FF5544',
    description: 'Controlled Electron Capture Reaction in nickel lattice. Q-pulse electromagnetic stimulation triggers hydrogen-to-helium transmutation. SRI International validated excess heat. Not yet commercial.',
    formula: 'p + e⁻ → n (ultralow momentum)  •  n + Ni → Cu + heat',
    physics: { type: 'lenr', deuteriumLoading: 0, excessHeat: 30, cathode: 'Ni' }
},
{
    id: 'mills-hydrino',
    name: 'Hydrino / SunCell',
    researcher: 'Randell Mills / BrLP (1991)',
    status: 'disputed',
    tags: ['quantum','zpe','plasma'],
    icon: 'fa-star-of-life',
    color: '#FFAA00',
    description: 'Claims hydrogen atom can collapse below ground state into "hydrino" releasing 200× more energy than combustion. SunCell device produces blinding plasma. $100M+ invested. Contradicts quantum mechanics.',
    formula: 'E_n = -13.6/n² eV (n = 1/p, fractional)  •  ΔE ≫ chemical',
    physics: { type: 'hydrino', fractionalState: 0.25, plasmaTemp: 5000 }
},
{
    id: 'safire-project',
    name: 'SAFIRE Stellar Plasma',
    researcher: 'Montgomery Childs / SAFIRE Project (2016)',
    status: 'unverified',
    tags: ['plasma','nuclear'],
    icon: 'fa-sun',
    color: '#FF9922',
    description: 'Lab-scale plasma containment replicating stellar mechanisms. Concentric spherical layers, double-layer phenomenon, transmutation of elements detected. Support for Electric Universe model.',
    formula: 'P_double-layer = n_e × kT_e × v_Bohm × A',
    physics: { type: 'safire', spheres: 2, plasmaT: 1e6 }
},

// ─── GRAVITY MODIFICATION ───
{
    id: 'heim-theory',
    name: 'Heim Theory Extended',
    researcher: 'Burkhard Heim (1957)',
    status: 'theoretical',
    tags: ['gravity','quantum'],
    icon: 'fa-project-diagram',
    color: '#9966FF',
    description: '8-dimensional extended field theory unifying gravity and electromagnetism. Predicts mass spectrum of elementary particles with striking accuracy. Proposes EM-gravity coupling via dimensional rotation.',
    formula: 'R⁸ = R⁴ × S² × I² × T²  •  m_p/m_e = f(α, π)',
    physics: { type: 'heim', dimensions: 8, coupling: true }
},
{
    id: 'ning-li-gravity',
    name: 'Ning Li AC Gravity',
    researcher: 'Ning Li (1989–2002)',
    status: 'unverified',
    tags: ['gravity','magnetic','quantum'],
    icon: 'fa-wave-square',
    color: '#AA88FF',
    description: 'Alabama physicist proposed "gravito-electric" coupling via spin-aligned lattice ions in superconductor under AC magnetic field. Patent for gravity generator. DoD funded $448K. Disappeared from public research.',
    formula: 'g_induced = f(B_ac, ω, n_ion, T_c)',
    physics: { type: 'ac-gravity', fieldFreq: 1e6, superconductor: true }
},

// ─── ELECTROMAGNETIC HEALING ───
{
    id: 'pemf-therapy',
    name: 'PEMF Therapy',
    researcher: 'Multiple (NASA, Bassett — 1979) — verified',
    status: 'verified',
    tags: ['em','biological','magnetic'],
    icon: 'fa-heartbeat',
    color: '#FF6699',
    description: 'Pulsed Electromagnetic Field therapy. FDA-approved for bone fracture healing (1979), depression (rTMS), and pain management. NASA found PEMF upregulates gene expression for tissue growth by 4×.',
    formula: 'B(t) = B₀ sin(2πft) × pulse_train  •  f = 1-100 Hz',
    physics: { type: 'pemf', frequency: 10, fieldmT: 2 }
},

// ─── BOB LAZAR / ELEMENT 115 ───
{
    id: 'element-115',
    name: 'Element 115 Gravity Amplifier',
    researcher: 'Bob Lazar / S4 claim (1989)',
    status: 'unverified',
    tags: ['gravity','nuclear','propulsion'],
    icon: 'fa-atom',
    color: '#FF44DD',
    description: 'Claims recovered craft uses stable island of Element 115 (Moscovium) as fuel. Proton bombardment releases antimatter + gravity A-wave amplified by waveguide. Element 115 synthesized at JINR (2003) but unstable.',
    formula: 'Mc + p → Fl + n + anti-e  •  gravity-A wave amplification',
    physics: { type: 'element-115', atomicNum: 115, amplifiers: 3 }
},

// ─── THERMOACOUSTIC ───
{
    id: 'thermoacoustic',
    name: 'Thermoacoustic Engine',
    researcher: 'Ceperley/Swift (1979–1988) — verified',
    status: 'verified',
    tags: ['thermal','acoustic','mechanical'],
    icon: 'fa-volume-up',
    color: '#FFAA55',
    description: 'Sound waves in a tube with temperature gradient produce work. No moving parts except gas molecules. Standing wave or traveling wave configurations. Can be a heat pump or engine. Approaches Carnot efficiency.',
    formula: 'P_acoustic = ½p₁u₁A  •  η = (T_H-T_C)/T_H × η_Carnot',
    physics: { type: 'thermoacoustic', freq: 200, deltaT: 300 }
},

// ─── WIRELESS POWER MODERN ───
{
    id: 'witricity',
    name: 'WiTricity Resonant Coupling',
    researcher: 'Marin Soljačić / MIT (2007) — verified',
    status: 'verified',
    tags: ['em','wireless','resonance'],
    icon: 'fa-wifi',
    color: '#44DDFF',
    description: 'Strongly coupled magnetic resonance transfers power wirelessly over meters with high efficiency. Two resonant coils at same frequency exchange energy like coupled pendulums. 60W over 2m demonstrated.',
    formula: 'η = κ²/(κ² + Γ₁Γ₂)  •  κ = M/(2√(L₁L₂))',
    physics: { type: 'witricity', distance: 2, freq: 9.9e6, power: 60 }
},

// ─── MISCELLANEOUS ───
{
    id: 'hutchison-crystal',
    name: 'Hutchison Crystal Battery',
    researcher: 'John Hutchison (2000s)',
    status: 'unverified',
    tags: ['chemical','zpe'],
    icon: 'fa-gem',
    color: '#FF88DD',
    description: 'Metal-crystal-resin composite batteries producing persistent voltage for years. Rocks, crystals, and metals in epoxy resin. Some produce >1V. Mechanism uncertain — beyond galvanic chemistry.',
    formula: 'V = V_galvanic + V_unknown  •  t_life = years',
    physics: { type: 'crystal', voltage: 1.2, material: 'metal-crystal-resin' }
},
{
    id: 'cold-electricity',
    name: 'Cold Electricity / Radiant Energy',
    researcher: 'Tesla / Gray / Bedini',
    status: 'unverified',
    tags: ['em','zpe'],
    icon: 'fa-snowflake',
    color: '#44CCEE',
    description: 'Non-classical electricity observed in spark gap and back-EMF systems. Charges batteries without heating wires. Current flows "from everywhere at once." Described by Tesla, observed by Gray, studied by Bedini.',
    formula: 'V_radiant = -L(dI/dt)  •  P = IV but wire stays cold',
    physics: { type: 'cold-elec', sparkGap: true, coldCurrent: true }
},
{
    id: 'newman-motor-v2',
    name: 'Magnetic Wankel Engine',
    researcher: 'Various (Concept)',
    status: 'theoretical',
    tags: ['magnetic','mechanical'],
    icon: 'fa-cog',
    color: '#CC88AA',
    description: 'Rotary engine concept using permanent magnets in Wankel (epitrochoid) geometry. Magnetic repulsion/attraction provides torque through rotation cycle. Multiple designs proposed.',
    formula: 'T = Σ(F_mag × r × sinθ)  •  x(t) = R·cos(t) + e·cos(3t)',
    physics: { type: 'magnetic-motor', magnets: 12, rotorLayers: 2, rpm: 800 }
},
{
    id: 'plauson-aerial',
    name: 'Plauson Atmospheric Collector',
    researcher: 'Hermann Plauson (1920)',
    status: 'unverified',
    tags: ['em','wireless'],
    icon: 'fa-broadcast-tower',
    color: '#88CC44',
    description: 'Swiss engineer\'s patent US1540998 for atmospheric electricity conversion. Elevated metallic balloons + spark gaps + transformers. Claimed 100kW from balloon systems. Detailed patent with engineering specs.',
    formula: 'P = V_atm × I_corona  •  V = ∫E·dh ≈ 100kV at 1km',
    physics: { type: 'atmospheric', fieldE: 100, height: 300 }
},
{
    id: 'ecklin-sag',
    name: 'Ecklin Stationary Armature Generator',
    researcher: 'John Ecklin (1970s)',
    status: 'unverified',
    tags: ['magnetic','em'],
    icon: 'fa-magnet',
    color: '#66AACC',
    description: 'Permanent magnet flux switched by rotating iron shunt. Magnetic flux alternates through stationary output coils without moving the magnets. Claims less input torque than output power.',
    formula: 'Φ_switch = B × A × (1 - reluctance_shunt/reluctance_air)',
    physics: { type: 'ecklin', magnets: 2, shuntRPM: 1800 }
},
{
    id: 'howard-johnson',
    name: 'Howard Johnson Magnetic Motor',
    researcher: 'Howard Johnson (1979, US4151431)',
    status: 'unverified',
    tags: ['magnetic'],
    icon: 'fa-magnet',
    color: '#DD66AA',
    description: 'Linear and rotary permanent magnet motor using curved magnet arrays. Patent granted by USPTO despite examiner initially rejecting perpetual motion. Magnets arranged on helical track.',
    formula: 'F = grad(B·m)  •  ΣW_cycle ≠ 0 (claimed)',
    physics: { type: 'magnetic-motor', magnets: 20, rotorLayers: 1, rpm: 400 }
},
{
    id: 'qeg-generator',
    name: 'QEG (Quantum Energy Generator)',
    researcher: 'Fix the World (2014)',
    status: 'unverified',
    tags: ['em','resonance'],
    icon: 'fa-drafting-compass',
    color: '#55BB88',
    description: 'Open-source toroidal generator based on Tesla\'s 1894 patent for "Electrical Generator." Resonant core driven at natural frequency. Global build events. Claims 10kW output from small input.',
    formula: 'f_res = 1/(2π√LC)  •  Q × f_res excitation',
    physics: { type: 'toroidal', radius: 0.15, turns: 400 }
},

// ─── QUANTUM BIOLOGY ───
{
    id: 'photosynthesis-quantum',
    name: 'Quantum Coherence Photosynthesis',
    researcher: 'Fleming Lab (2007) — verified',
    status: 'verified',
    tags: ['quantum','biological'],
    icon: 'fa-leaf',
    color: '#44CC44',
    description: 'Plants use quantum coherence to transfer energy at ~99% efficiency through chromophore networks. Exciton explores all paths simultaneously via quantum superposition. Nature solved quantum computing first.',
    formula: 'η ≈ 99%  •  |ψ⟩ = Σ cₙ|n⟩  •  t_coherence ≈ 660 fs',
    physics: { type: 'quantum-bio', efficiency: 0.99, coherenceTime: 660e-15 }
},

// ─── ZERO-POINT THEORETICAL ───
{
    id: 'puthoff-sed',
    name: 'Stochastic Electrodynamics (SED)',
    researcher: 'Harold Puthoff / Timothy Boyer',
    status: 'theoretical',
    tags: ['quantum','zpe'],
    icon: 'fa-infinity',
    color: '#6699FF',
    description: 'Classical explanation of quantum phenomena via zero-point field. Electrons maintained in orbit by dynamic equilibrium with ZPF radiation. If correct, ZPE extraction from vacuum is theoretically possible.',
    formula: 'E_zpf = ½ℏω per mode  •  ρ(ω) = ℏω³/(2π²c³)',
    physics: { type: 'sed', spectrum: 'continuous', density: 'infinite' }
},
{
    id: 'schwinger-effect',
    name: 'Schwinger Pair Production',
    researcher: 'Julian Schwinger (1951) — theoretical',
    status: 'theoretical',
    tags: ['quantum','zpe','em'],
    icon: 'fa-atom',
    color: '#FF66AA',
    description: 'Sufficiently strong electric field (~10¹⁸ V/m) tears electron-positron pairs straight from the vacuum. The field\'s energy converts to matter via E=mc². Never achieved but theoretically certain.',
    formula: 'E_crit = m²c³/(eℏ) ≈ 1.3×10¹⁸ V/m',
    physics: { type: 'schwinger', criticalField: 1.3e18 }
},
{
    id: 'unruh-effect',
    name: 'Unruh Effect',
    researcher: 'William Unruh (1976) — theoretical',
    status: 'theoretical',
    tags: ['quantum','gravity'],
    icon: 'fa-tachometer-alt',
    color: '#7744FF',
    description: 'Accelerating observer sees the vacuum filled with thermal radiation. What\'s empty for one observer is hot for another. Connected to Hawking radiation. Temperature proportional to acceleration.',
    formula: 'T = ℏa/(2πck_B)  •  a = 1g → T ≈ 4×10⁻²⁰ K',
    physics: { type: 'unruh', acceleration: 9.8 }
},
{
    id: 'hawking-radiation',
    name: 'Hawking Radiation',
    researcher: 'Stephen Hawking (1974) — theoretical',
    status: 'theoretical',
    tags: ['quantum','gravity'],
    icon: 'fa-circle',
    color: '#FF4488',
    description: 'Black holes emit thermal radiation via virtual particle pair production at event horizon. One particle escapes, other falls in. Black hole evaporates over immense timescales. T ∝ 1/M.',
    formula: 'T = ℏc³/(8πGMk_B)  •  P = ℏc⁶/(15360πG²M²)',
    physics: { type: 'hawking', massKg: 2e30 }
},

// ══════════════════════════════════════════════════
//  CLASSICAL ELECTROMAGNETISM & HISTORICAL MILESTONES
// ══════════════════════════════════════════════════
{
    id: 'volta-pile',
    name: 'Volta\'s Pile (First Battery)',
    researcher: 'Alessandro Volta (1800)',
    status: 'verified',
    tags: ['em','historical','battery'],
    icon: 'fa-battery-full',
    color: '#DDAA66',
    description: 'First electrochemical battery: alternating zinc and copper discs separated by brine-soaked cardboard. Produced steady electric current for the first time. Settled the Galvani-Volta debate and launched the electrical age.',
    formula: 'V = n × E_cell  •  E_cell ≈ 0.76 V (Zn|Cu)',
    physics: { type: 'battery-stack', cells: 20, voltage: 0.76 }
},
{
    id: 'leyden-jar',
    name: 'Leyden Jar (First Capacitor)',
    researcher: 'Pieter van Musschenbroek (1745)',
    status: 'verified',
    tags: ['em','historical'],
    icon: 'fa-jar',
    color: '#DDAA66',
    description: 'First device to store electrical charge. Glass jar lined inside and out with metal foil. Could deliver powerful shocks. Predecessor to all modern capacitors.',
    formula: 'C = ε₀εᵣA/d  •  E = ½CV²',
    physics: { type: 'capacitor', capacitance: 1e-9 }
},
{
    id: 'franklin-kite',
    name: 'Franklin\'s Kite Experiment',
    researcher: 'Benjamin Franklin (1752)',
    status: 'verified',
    tags: ['em','historical'],
    icon: 'fa-bolt',
    color: '#FFCC33',
    description: 'Proved lightning is electrical by flying a kite in a thunderstorm with a key on the string. Charge collected in a Leyden jar. Led to invention of the lightning rod.',
    formula: 'V_cloud ≈ 100-300 MV  •  I_lightning ≈ 30 kA  •  E = ½LI²',
    physics: { type: 'lightning', voltage: 3e8 }
},
{
    id: 'coulomb-torsion',
    name: 'Coulomb\'s Torsion Balance',
    researcher: 'Charles-Augustin de Coulomb (1785)',
    status: 'verified',
    tags: ['em','historical'],
    icon: 'fa-circle-dot',
    color: '#00D4FF',
    description: 'Established the inverse-square law for electrostatic force using a torsion balance. The electric force between two point charges is proportional to the product of charges and inversely proportional to the square of distance.',
    formula: 'F = k_e × q₁q₂/r²  •  k_e = 8.99×10⁹ N⋅m²/C²',
    physics: { type: 'coulomb', charges: 2 }
},
{
    id: 'oersted-compass',
    name: 'Ørsted\'s Compass Experiment',
    researcher: 'Hans Christian Ørsted (1820)',
    status: 'verified',
    tags: ['em','magnetic','historical'],
    icon: 'fa-compass',
    color: '#ec4899',
    description: 'Discovered that electric current deflects a compass needle, proving electricity and magnetism are related. This experiment launched the entire field of electromagnetism.',
    formula: 'B = μ₀I/(2πr)  •  Biot-Savart: dB = μ₀I(dl×r̂)/(4πr²)',
    physics: { type: 'oersted', current: 10 }
},
{
    id: 'maxwell-equations',
    name: 'Maxwell\'s Equations Demonstration',
    researcher: 'James Clerk Maxwell (1865)',
    status: 'verified',
    tags: ['em','historical'],
    icon: 'fa-wave-square',
    color: '#00D4FF',
    description: 'Unified electricity, magnetism, and light into four elegant equations. Predicted electromagnetic waves travel at speed of light. Founded all of modern electromagnetism, radio, TV, WiFi, radar.',
    formula: '∇·E = ρ/ε₀  •  ∇×E = -∂B/∂t  •  ∇·B = 0  •  ∇×B = μ₀J + μ₀ε₀∂E/∂t',
    physics: { type: 'em-wave', speed: 3e8 }
},
{
    id: 'hertz-waves',
    name: 'Hertz Radio Wave Detection',
    researcher: 'Heinrich Hertz (1887)',
    status: 'verified',
    tags: ['em','historical','wireless'],
    icon: 'fa-tower-broadcast',
    color: '#00D4FF',
    description: 'First experimental proof of Maxwell\'s electromagnetic waves. Used spark-gap transmitter and ring resonator receiver. Demonstrated that EM waves reflect, refract, and exhibit polarization — just like light.',
    formula: 'f = c/λ  •  c = 1/√(μ₀ε₀) = 299,792,458 m/s',
    physics: { type: 'hertz', frequency: 5e8 }
},
{
    id: 'marconi-radio',
    name: 'Marconi Wireless Telegraphy',
    researcher: 'Guglielmo Marconi (1901) — Nobel 1909',
    status: 'verified',
    tags: ['em','historical','wireless'],
    icon: 'fa-satellite-dish',
    color: '#66EEDD',
    description: 'First transatlantic wireless signal sent from Poldhu, Cornwall to St. John\'s, Newfoundland. Proved radio waves follow Earth\'s curvature (via ionospheric reflection). Launched the telecommunications revolution.',
    formula: 'P_r = P_t × G_t × G_r × (λ/4πd)²  •  Friis equation',
    physics: { type: 'radio-tx', distance: 3500000, power: 25000 }
},
{
    id: 'edison-light-bulb',
    name: 'Edison Incandescent Lamp',
    researcher: 'Thomas Edison (1879)',
    status: 'verified',
    tags: ['em','thermal','historical'],
    icon: 'fa-lightbulb',
    color: '#FFB800',
    description: 'Practical long-lasting incandescent light bulb using carbonized bamboo filament. Burned for 1,200+ hours. Combined with Edison\'s DC power distribution system, electrified civilization.',
    formula: 'P = V²/R  •  T_filament ≈ 2700 K  •  η ≈ 2-5%  •  Wien: λ_max = b/T',
    physics: { type: 'filament', temperature: 2700 }
},
{
    id: 'crookes-tube',
    name: 'Crookes Tube / Cathode Rays',
    researcher: 'William Crookes (1870s)',
    status: 'verified',
    tags: ['em','particle','historical'],
    icon: 'fa-flask-vial',
    color: '#88BBFF',
    description: 'Evacuated glass tube showing cathode rays (electron beams). Rays cast shadows, can be deflected by magnets. Led directly to J.J. Thomson\'s electron discovery (1897) and X-ray discovery.',
    formula: 'e/m = 1.76×10¹¹ C/kg  •  E_k = eV  •  r = mv/(eB)',
    physics: { type: 'cathode-ray', voltage: 5000 }
},

// ══════════════════════════════════════════════════
//  NUCLEAR & PARTICLE PHYSICS
// ══════════════════════════════════════════════════
{
    id: 'becquerel-radioactivity',
    name: 'Becquerel Radioactivity Discovery',
    researcher: 'Henri Becquerel (1896) — Nobel 1903',
    status: 'verified',
    tags: ['nuclear','historical'],
    icon: 'fa-radiation',
    color: '#FF3366',
    description: 'Discovered spontaneous radioactivity when uranium salts fogged photographic plates stored in a dark drawer. Proved atoms are not immutable — they can spontaneously emit radiation.',
    formula: 'N(t) = N₀e^(-λt)  •  t½ = ln2/λ  •  A = λN  •  1 Bq = 1 decay/s',
    physics: { type: 'radioactive', halfLife: 4.5e9 }
},
{
    id: 'rutherford-scattering',
    name: 'Rutherford Gold Foil Experiment',
    researcher: 'Ernest Rutherford, Geiger & Marsden (1911)',
    status: 'verified',
    tags: ['nuclear','particle','historical'],
    icon: 'fa-atom',
    color: '#FF6688',
    description: 'Alpha particles fired at thin gold foil. Most passed through, but some bounced back at large angles. Proved atoms have a tiny dense nucleus with electrons orbiting — replaced Thomson\'s plum pudding model.',
    formula: 'dσ/dΩ = (Z₁Z₂e²/4E)² × 1/sin⁴(θ/2)  •  r_nucleus ≈ 10⁻¹⁵ m',
    physics: { type: 'scattering', particles: 100 }
},
{
    id: 'cloud-chamber',
    name: 'Wilson Cloud Chamber',
    researcher: 'C.T.R. Wilson (1911) — Nobel 1927',
    status: 'verified',
    tags: ['nuclear','particle','historical'],
    icon: 'fa-cloud',
    color: '#AADDFF',
    description: 'Supersaturated vapor chamber where charged particles leave visible tracks of condensation. First device to make subatomic particles visible. Used to discover positron, muon, kaon, and more.',
    formula: 'dE/dx = (4πe⁴z²NZ)/(m_e v²) × [ln(2m_e v²/I) - ln(1-β²) - β²]',
    physics: { type: 'cloud-chamber', tracks: 6 }
},
{
    id: 'nuclear-fission',
    name: 'Hahn-Strassmann Nuclear Fission',
    researcher: 'Otto Hahn & Fritz Strassmann (1938)',
    status: 'verified',
    tags: ['nuclear','historical'],
    icon: 'fa-burst',
    color: '#FF4444',
    description: 'Discovered that uranium nuclei split when hit by neutrons, releasing enormous energy. Each fission releases 2-3 more neutrons enabling chain reaction. Foundation of nuclear power and weapons.',
    formula: '²³⁵U + n → ⁹²Kr + ¹⁴¹Ba + 3n + 200 MeV  •  E = Δm⋅c²',
    physics: { type: 'fission', energy: 200 }
},
{
    id: 'manhattan-cp1',
    name: 'Chicago Pile-1 (First Reactor)',
    researcher: 'Enrico Fermi (1942)',
    status: 'verified',
    tags: ['nuclear','historical'],
    icon: 'fa-warehouse',
    color: '#FF5555',
    description: 'First controlled, self-sustaining nuclear chain reaction. Built under squash courts at University of Chicago. Used graphite moderator and uranium fuel. Proved nuclear power was possible.',
    formula: 'k_eff = k_∞ × P_NL  •  k_eff = 1 (critical)  •  P = σ_f × Φ × N × E_f',
    physics: { type: 'reactor', keff: 1.0 }
},
{
    id: 'cyclotron',
    name: 'Lawrence Cyclotron',
    researcher: 'Ernest Lawrence (1932) — Nobel 1939',
    status: 'verified',
    tags: ['particle','magnetic','historical'],
    icon: 'fa-circle-notch',
    color: '#FF66AA',
    description: 'First circular particle accelerator. Ions spiral outward between two D-shaped electrodes in a magnetic field, gaining energy each half-turn. Opened the door to particle physics.',
    formula: 'f_cyc = qB/(2πm)  •  r = mv/(qB)  •  E_max = q²B²R²/(2m)',
    physics: { type: 'cyclotron', fieldB: 1.5, dRadius: 0.3 }
},
{
    id: 'bubble-chamber',
    name: 'Glaser Bubble Chamber',
    researcher: 'Donald Glaser (1952) — Nobel 1960',
    status: 'verified',
    tags: ['particle'],
    icon: 'fa-droplet',
    color: '#88CCFF',
    description: 'Superheated liquid hydrogen where charged particles leave trails of tiny bubbles. Captured beautiful spiral tracks. Discovered many new particles — omega-minus, neutral currents, charm quarks.',
    formula: 'p = qBr  •  r = p/(qB)  •  Curvature reveals momentum and charge sign',
    physics: { type: 'bubble-chamber', tracks: 8 }
},
{
    id: 'higgs-boson',
    name: 'Higgs Boson Discovery (LHC)',
    researcher: 'ATLAS & CMS at CERN (2012) — Nobel 2013',
    status: 'verified',
    tags: ['particle','quantum'],
    icon: 'fa-star',
    color: '#FF66AA',
    description: 'Discovered at Large Hadron Collider by colliding protons at 13 TeV. Confirmed the Higgs field gives mass to fundamental particles. The "last missing piece" of the Standard Model.',
    formula: 'm_H = 125.1 GeV/c²  •  V(φ) = μ²φ²/2 + λφ⁴/4  •  v = √(-μ²/λ) = 246 GeV',
    physics: { type: 'collider', energy: 13e12, mass: 125.1 }
},
{
    id: 'antimatter-positron',
    name: 'Anderson Positron Discovery',
    researcher: 'Carl Anderson (1932) — Nobel 1936',
    status: 'verified',
    tags: ['particle','quantum','historical'],
    icon: 'fa-circle-half-stroke',
    color: '#FF88CC',
    description: 'First detection of antimatter. Anderson photographed a cloud chamber track that curved the "wrong way" — a positively charged electron. Confirmed Dirac\'s prediction of antiparticles.',
    formula: 'E² = (pc)² + (m_e c²)²  •  m_e = 0.511 MeV/c²  •  e⁺ + e⁻ → 2γ',
    physics: { type: 'pair-production', energy: 1.022 }
},

// ══════════════════════════════════════════════════
//  OPTICS & PHOTONICS
// ══════════════════════════════════════════════════
{
    id: 'photoelectric-effect',
    name: 'Photoelectric Effect',
    researcher: 'Albert Einstein (1905) — Nobel 1921',
    status: 'verified',
    tags: ['quantum','optics','historical'],
    icon: 'fa-sun',
    color: '#FFEE44',
    description: 'Light ejects electrons from metal surface. Energy depends on frequency, not intensity. Proved light comes in quanta (photons). Foundation of quantum mechanics and solar cells.',
    formula: 'E = hf = hc/λ  •  K_max = hf - φ  •  h = 6.626×10⁻³⁴ J⋅s',
    physics: { type: 'photoelectric', workFunction: 4.7 }
},
{
    id: 'young-double-slit',
    name: 'Young\'s Double-Slit Experiment',
    researcher: 'Thomas Young (1801)',
    status: 'verified',
    tags: ['quantum','optics','historical'],
    icon: 'fa-grip-lines-vertical',
    color: '#FFEE44',
    description: 'Most important experiment in quantum physics. Light passing through two slits creates interference pattern — proving wave nature. Single photons still create the pattern, proving wave-particle duality.',
    formula: 'd⋅sinθ = nλ (bright)  •  d⋅sinθ = (n+½)λ (dark)  •  I ∝ cos²(πdsinθ/λ)',
    physics: { type: 'double-slit', slitDistance: 0.1e-3, wavelength: 550e-9 }
},
{
    id: 'laser',
    name: 'LASER (Stimulated Emission)',
    researcher: 'Theodore Maiman (1960)',
    status: 'verified',
    tags: ['optics','quantum'],
    icon: 'fa-wand-sparkles',
    color: '#FF2222',
    description: 'Light Amplification by Stimulated Emission of Radiation. Maiman built first working laser using ruby crystal. Coherent, monochromatic light. Now in surgery, telecom, manufacturing, barcode scanners, Blu-ray.',
    formula: 'N₂/N₁ = exp(-ΔE/k_BT)  •  Population inversion: N₂ > N₁  •  Gain: G = e^(σ(N₂-N₁)L)',
    physics: { type: 'laser', wavelength: 694.3e-9, medium: 'ruby' }
},
{
    id: 'fiber-optics',
    name: 'Fiber Optic Communication',
    researcher: 'Charles Kao (1966) — Nobel 2009',
    status: 'verified',
    tags: ['optics','wireless'],
    icon: 'fa-network-wired',
    color: '#44FFCC',
    description: 'Light pulses through ultra-pure glass fibers carry data at speed of light. Kao predicted <20 dB/km loss was achievable. Now carries 99% of intercontinental data. Backbone of the internet.',
    formula: 'NA = √(n₁² - n₂²)  •  Loss < 0.2 dB/km @ 1550nm  •  BW⋅L > 100 GHz⋅km',
    physics: { type: 'fiber', coreN: 1.468, claddingN: 1.462 }
},
{
    id: 'holography',
    name: 'Holography',
    researcher: 'Dennis Gabor (1948) — Nobel 1971',
    status: 'verified',
    tags: ['optics'],
    icon: 'fa-cube',
    color: '#DDFF44',
    description: 'Recording and reconstruction of 3D light wavefronts using interference patterns. Reference beam + object beam create hologram. On illumination, reconstructs full 3D image. Used in security, art, data storage.',
    formula: 'I = |E_ref + E_obj|² = |E_ref|² + |E_obj|² + E_ref*E_obj + E_refE_obj*',
    physics: { type: 'hologram', beams: 2 }
},

// ══════════════════════════════════════════════════
//  RENEWABLE ENERGY
// ══════════════════════════════════════════════════
{
    id: 'photovoltaic-solar',
    name: 'Photovoltaic Solar Cell',
    researcher: 'Bell Labs — Chapin, Fuller, Pearson (1954)',
    status: 'verified',
    tags: ['renewable','quantum'],
    icon: 'fa-solar-panel',
    color: '#88DD44',
    description: 'Silicon p-n junction converts sunlight directly to electricity. Bell Labs achieved 6% efficiency. Modern cells reach 47% (multi-junction). Now cheapest electricity source in history for most of the world.',
    formula: 'η = P_out/P_in  •  P_in = 1000 W/m²  •  V_oc = (k_BT/q)ln(I_L/I_0 + 1)',
    physics: { type: 'solar-cell', efficiency: 0.22 }
},
{
    id: 'wind-turbine',
    name: 'Wind Turbine Generator',
    researcher: 'James Blyth (1887) / modern: Vestas, GE, Siemens',
    status: 'verified',
    tags: ['renewable','mechanical'],
    icon: 'fa-fan',
    color: '#44DD88',
    description: 'Kinetic energy of wind spins rotor blades coupled to generator. Betz limit caps maximum extraction at 59.3%. Modern offshore turbines reach 15+ MW each. Second largest renewable source after hydro.',
    formula: 'P = ½ρAv³C_p  •  C_p ≤ 16/27 (Betz limit)  •  TSR = ωR/v',
    physics: { type: 'wind', blades: 3, rpm: 12 }
},
{
    id: 'hydroelectric',
    name: 'Hydroelectric Dam / Turbine',
    researcher: 'Lester Pelton (1878) / First dam: Niagara Falls (1895)',
    status: 'verified',
    tags: ['renewable','mechanical'],
    icon: 'fa-water',
    color: '#4488FF',
    description: 'Gravitational potential energy of water drives turbines. Oldest and largest renewable energy source. Three Gorges Dam produces 22.5 GW. 90%+ efficiency. Provides 16% of world electricity.',
    formula: 'P = ρgQh × η  •  E_potential = mgh  •  η_turbine ≈ 90-95%',
    physics: { type: 'hydro', head: 100, flow: 500 }
},
{
    id: 'geothermal-energy',
    name: 'Geothermal Power Plant',
    researcher: 'Piero Ginori Conti (1904, Larderello, Italy)',
    status: 'verified',
    tags: ['renewable','thermal'],
    icon: 'fa-volcano',
    color: '#FF6633',
    description: 'Earth\'s internal heat drives steam turbines. Temperatures reach 5000°C at core. Binary, flash, and dry steam plants. Nearly zero emissions. Available 24/7 unlike solar/wind. Iceland runs 25% on geothermal.',
    formula: 'η_Carnot = 1 - T_cold/T_hot  •  Q = kA(ΔT/Δx)  •  Gradient ≈ 25-30°C/km',
    physics: { type: 'geothermal', tempHot: 250, tempCold: 40 }
},
{
    id: 'tidal-energy',
    name: 'Tidal / Wave Energy Harvester',
    researcher: 'Rance Tidal Station (1966, France)',
    status: 'verified',
    tags: ['renewable','mechanical'],
    icon: 'fa-wind',
    color: '#2299FF',
    description: 'Moon and Sun\'s gravitational pull creates predictable tides. Barrage, stream, and wave devices capture this energy. Most predictable renewable source. 240 MW Rance station has operated since 1966.',
    formula: 'P = ½ρAv³  •  E_tidal = ½ρgAΔh²  •  P_wave = ρg²TH²/(32π) per meter',
    physics: { type: 'tidal', amplitude: 8, period: 12.42 }
},
{
    id: 'concentrated-solar',
    name: 'Concentrated Solar Power (CSP)',
    researcher: 'Frank Shuman (1913) / Ivanpah (2014)',
    status: 'verified',
    tags: ['renewable','thermal'],
    icon: 'fa-sun',
    color: '#FFAA22',
    description: 'Mirrors focus sunlight onto receiver to generate extreme heat (500-1000°C), driving steam turbines. Can include molten salt thermal storage for 24/7 operation. Ivanpah: 392 MW, 173,500 heliostats.',
    formula: 'C = A_aperture/A_absorber  •  T_receiver ∝ C^(1/4)  •  η_system ≈ 20-25%',
    physics: { type: 'csp', mirrors: 12, concentration: 1000 }
},

// ══════════════════════════════════════════════════
//  BATTERY & ENERGY STORAGE
// ══════════════════════════════════════════════════
{
    id: 'lithium-ion',
    name: 'Lithium-Ion Battery',
    researcher: 'Goodenough, Whittingham, Yoshino — Nobel 2019',
    status: 'verified',
    tags: ['battery','chemical'],
    icon: 'fa-battery-three-quarters',
    color: '#44DDAA',
    description: 'Lithium ions shuttle between cathode and anode through electrolyte. High energy density (250 Wh/kg), thousands of cycles. Powers phones, laptops, EVs, and grid storage. Transformed modern civilization.',
    formula: 'LiCoO₂ ⇌ Li₁₋ₓCoO₂ + xLi⁺ + xe⁻  •  E_cell ≈ 3.7 V  •  W_h = V × Ah',
    physics: { type: 'li-ion', voltage: 3.7, capacity: 3000 }
},
{
    id: 'hydrogen-fuel-cell',
    name: 'Hydrogen Fuel Cell (PEM)',
    researcher: 'William Grove (1842) / modern: Ballard, Toyota',
    status: 'verified',
    tags: ['battery','chemical','renewable'],
    icon: 'fa-droplet',
    color: '#22CCAA',
    description: 'Hydrogen and oxygen combine electrochemically to produce electricity and water. No combustion, no emissions. PEM (Proton Exchange Membrane) type powers vehicles, backup power, spacecraft (Apollo used them).',
    formula: '2H₂ + O₂ → 2H₂O + electricity  •  E° = 1.23 V  •  η_practical ≈ 40-60%',
    physics: { type: 'fuel-cell', voltage: 1.23, efficiency: 0.55 }
},
{
    id: 'supercapacitor',
    name: 'Supercapacitor / Ultracapacitor',
    researcher: 'Standard Oil / NEC (1966-1978)',
    status: 'verified',
    tags: ['battery','em'],
    icon: 'fa-bolt',
    color: '#44FFDD',
    description: 'Stores energy electrostatically on high surface-area electrodes (activated carbon). 1M+ cycles, charges in seconds, 10× power density of batteries. Used in regenerative braking, UPS, grid stabilization.',
    formula: 'C = εA/d  •  E = ½CV²  •  P = V²/(4×ESR)  •  C > 1000 F achievable',
    physics: { type: 'supercap', capacitance: 3000, voltage: 2.7 }
},
{
    id: 'flow-battery',
    name: 'Vanadium Redox Flow Battery',
    researcher: 'Maria Skyllas-Kazacos (1986, UNSW)',
    status: 'verified',
    tags: ['battery','chemical'],
    icon: 'fa-arrows-spin',
    color: '#6644DD',
    description: 'Liquid electrolytes stored in external tanks flow through electrochemical cell. Energy capacity scales with tank size, power scales with cell size — independently. 20,000+ cycles. Ideal for grid storage.',
    formula: 'V²⁺ ⇌ V³⁺ + e⁻ (anode)  •  VO₂⁺ + e⁻ ⇌ VO²⁺ (cathode)  •  E_cell ≈ 1.26 V',
    physics: { type: 'flow-battery', tanks: 2, flow: true }
},

// ══════════════════════════════════════════════════
//  GRAVITY & RELATIVITY
// ══════════════════════════════════════════════════
{
    id: 'cavendish-gravity',
    name: 'Cavendish Gravitational Constant',
    researcher: 'Henry Cavendish (1798)',
    status: 'verified',
    tags: ['gravity','historical'],
    icon: 'fa-scale-balanced',
    color: '#CC88FF',
    description: 'First measurement of Big G using torsion balance with lead spheres. "Weighed the Earth." G = 6.674×10⁻¹¹ N⋅m²/kg². One of the most delicate experiments ever performed.',
    formula: 'F = Gm₁m₂/r²  •  G = 6.674×10⁻¹¹ N⋅m²/kg²  •  M_Earth = gR²/G',
    physics: { type: 'cavendish', spheres: 4 }
},
{
    id: 'eddington-eclipse',
    name: 'Eddington Solar Eclipse (1919)',
    researcher: 'Arthur Eddington (1919)',
    status: 'verified',
    tags: ['gravity','relativity','historical'],
    icon: 'fa-eclipse',
    color: '#CC88FF',
    description: 'During total solar eclipse, measured apparent positions of stars near the Sun. Starlight bent by 1.75 arcseconds — matching Einstein\'s General Relativity prediction, not Newton\'s. Made Einstein world-famous overnight.',
    formula: 'Δθ = 4GM/(rc²)  •  θ_GR = 1.75" (at solar limb)  •  θ_Newton = 0.875"',
    physics: { type: 'lensing', deflection: 1.75 }
},
{
    id: 'ligo-gravitational-waves',
    name: 'LIGO Gravitational Wave Detection',
    researcher: 'LIGO — Weiss, Barish, Thorne — Nobel 2017',
    status: 'verified',
    tags: ['gravity','relativity'],
    icon: 'fa-wave-square',
    color: '#AA66FF',
    description: 'First direct detection of gravitational waves from merging black holes (Sept 14, 2015). LIGO interferometers measured spacetime distortion of 10⁻²¹ — 1/10,000th the diameter of a proton. Confirmed Einstein\'s 100-year-old prediction.',
    formula: 'h = ΔL/L ≈ 10⁻²¹  •  f ≈ 35-250 Hz (chirp)  •  E_radiated ≈ 3M☉c²',
    physics: { type: 'interferometer', armLength: 4000, sensitivity: 1e-21 }
},
{
    id: 'gps-relativity',
    name: 'GPS Relativistic Correction',
    researcher: 'U.S. Air Force / Roger Easton (1978+)',
    status: 'verified',
    tags: ['relativity'],
    icon: 'fa-satellite',
    color: '#8866FF',
    description: 'GPS satellites at 20,200 km altitude experience weaker gravity (clocks run faster by 45 μs/day) and move at 3.87 km/s (clocks slow by 7 μs/day via SR). Net +38 μs/day must be corrected or position errors reach 10+ km/day.',
    formula: 'Δt_GR = (GM/rc²) × t  •  Δt_SR = t(1 - √(1-v²/c²))  •  Net: +38.6 μs/day',
    physics: { type: 'gps-orbit', altitude: 20200000, drift: 38.6e-6 }
},
{
    id: 'frame-dragging',
    name: 'Gravity Probe B Frame-Dragging',
    researcher: 'Stanford / NASA (2004-2011)',
    status: 'verified',
    tags: ['gravity','relativity'],
    icon: 'fa-rotate',
    color: '#9977FF',
    description: 'Orbiting gyroscopes measured geodetic effect (6,606 mas/yr) and frame-dragging (37.2 mas/yr) predicted by General Relativity. Earth\'s rotation literally drags spacetime around with it.',
    formula: 'Ω_geodetic = 3GM/(2c²r) × v  •  Ω_frame = GI/(c²r³) × [3(L⋅r̂)r̂ - L]',
    physics: { type: 'gyroscope', precession: 37.2 }
},

// ══════════════════════════════════════════════════
//  QUANTUM COMPUTING & QUANTUM INFO
// ══════════════════════════════════════════════════
{
    id: 'bell-test',
    name: 'Bell Test Experiment',
    researcher: 'Aspect, Clauser, Zeilinger — Nobel 2022',
    status: 'verified',
    tags: ['quantum','computing'],
    icon: 'fa-link',
    color: '#44CCFF',
    description: 'Proved quantum mechanics is non-local by violating Bell\'s inequality. Entangled photon pairs show correlations impossible in any local hidden-variable theory. "Spooky action at a distance" is real.',
    formula: 'S = |E(a,b) - E(a,b\')| + |E(a\',b) + E(a\',b\')|  •  S_QM = 2√2 > 2 (CHSH)',
    physics: { type: 'entanglement', violation: 2.828 }
},
{
    id: 'quantum-entanglement',
    name: 'Quantum Entanglement (EPR)',
    researcher: 'Einstein, Podolsky, Rosen (1935) / Aspect (1982)',
    status: 'verified',
    tags: ['quantum','computing'],
    icon: 'fa-link-slash',
    color: '#66BBFF',
    description: 'Two particles share a quantum state. Measuring one instantly determines the other, regardless of distance. Einstein called it "spooky action at a distance." Now the foundation of quantum computing and quantum internet.',
    formula: '|Ψ⟩ = (|00⟩ + |11⟩)/√2  •  Bell state  •  Correlation = cos²(θ/2)',
    physics: { type: 'entanglement', pairs: 2 }
},
{
    id: 'qubit-superconducting',
    name: 'Superconducting Qubit (Transmon)',
    researcher: 'Google (Sycamore, 2019) / IBM / Rigetti',
    status: 'verified',
    tags: ['quantum','computing'],
    icon: 'fa-microchip',
    color: '#22BBFF',
    description: 'Josephson junction cooled to 15 mK creates a two-level quantum system (qubit). Google\'s 53-qubit Sycamore achieved "quantum supremacy" in 2019, performing a calculation in 200s that would take a supercomputer 10,000 years.',
    formula: '|ψ⟩ = α|0⟩ + β|1⟩  •  |α|² + |β|² = 1  •  T2 ≈ 100 μs  •  2^n states',
    physics: { type: 'qubit', qubits: 53, coherenceTime: 100e-6 }
},
{
    id: 'quantum-teleportation',
    name: 'Quantum Teleportation',
    researcher: 'Zeilinger et al. (1997) / Pan Jianwei (2017, satellite)',
    status: 'verified',
    tags: ['quantum','computing'],
    icon: 'fa-right-left',
    color: '#55DDFF',
    description: 'Quantum state of one particle transferred to another using entanglement + classical communication. Not faster-than-light (classical channel needed). Pan Jianwei achieved 1,400 km teleportation via Micius satellite.',
    formula: '|φ⟩ = α|0⟩ + β|1⟩ → Bell measurement → classical bits → |φ⟩ reconstructed',
    physics: { type: 'teleportation', distance: 1400000 }
},

// ══════════════════════════════════════════════════
//  BIOELECTRICITY
// ══════════════════════════════════════════════════
{
    id: 'galvani-frog-leg',
    name: 'Galvani\'s Frog Leg Experiment',
    researcher: 'Luigi Galvani (1780)',
    status: 'verified',
    tags: ['bioelectric','historical'],
    icon: 'fa-frog',
    color: '#66FFAA',
    description: 'Discovered bioelectricity when dead frog legs twitched upon electrical stimulation. Proved animal tissues generate and conduct electricity. Sparked the Galvani-Volta debate and birthed neuroscience.',
    formula: 'V_membrane ≈ -70 mV  •  I = gNa(V-ENa) + gK(V-EK)  •  Nernst: E = (RT/zF)ln([out]/[in])',
    physics: { type: 'bioelectric', voltage: -70e-3 }
},
{
    id: 'hodgkin-huxley',
    name: 'Hodgkin-Huxley Action Potential',
    researcher: 'Alan Hodgkin & Andrew Huxley (1952) — Nobel 1963',
    status: 'verified',
    tags: ['bioelectric','quantum'],
    icon: 'fa-brain',
    color: '#44FF88',
    description: 'Mathematical model of how nerve impulses propagate via voltage-gated sodium and potassium ion channels. Explained all-or-nothing firing, refractory periods, and conduction velocity. Foundation of computational neuroscience.',
    formula: 'C_m(dV/dt) = -g_Na⋅m³h(V-E_Na) - g_K⋅n⁴(V-E_K) - g_L(V-E_L) + I',
    physics: { type: 'action-potential', channels: 2 }
},
{
    id: 'electric-eel',
    name: 'Electric Eel Discharge',
    researcher: 'Alexander von Humboldt (1800) / Kenneth Catania (2014+)',
    status: 'verified',
    tags: ['bioelectric','biological'],
    icon: 'fa-bolt',
    color: '#88FF66',
    description: '6,000 stacked electrocyte cells act like batteries in series, generating up to 860V at 1A. Used for hunting, defense, and electrolocation. Catania showed eels leap from water to deliver shocks directly to threats.',
    formula: 'V = n × E_cell  •  n ≈ 6000  •  E_cell ≈ 0.15 V  •  P_peak ≈ 860 W',
    physics: { type: 'eel-discharge', cells: 6000, voltage: 860 }
},
{
    id: 'eeg-brain-waves',
    name: 'EEG Brain Wave Recording',
    researcher: 'Hans Berger (1924)',
    status: 'verified',
    tags: ['bioelectric'],
    icon: 'fa-wave-square',
    color: '#66DD88',
    description: 'First recording of electrical activity of the human brain. Discovered alpha waves (8-12 Hz, relaxation) and beta waves (12-30 Hz, active thinking). Foundation of neuroscience diagnostics — EEG now used for epilepsy, sleep studies, BCI.',
    formula: 'α: 8-12 Hz  •  β: 12-30 Hz  •  θ: 4-8 Hz  •  δ: 0.5-4 Hz  •  V ≈ 10-100 μV',
    physics: { type: 'eeg', bands: 4 }
},
{
    id: 'bioluminescence',
    name: 'Bioluminescence (GFP)',
    researcher: 'Osamu Shimomura (1962) — Nobel 2008',
    status: 'verified',
    tags: ['bioelectric','biological','optics'],
    icon: 'fa-lightbulb',
    color: '#44FF88',
    description: 'Green Fluorescent Protein from jellyfish Aequorea victoria. Discovered luciferin-luciferase reaction. GFP revolutionized biology as a visible marker for gene expression, protein localization, and cellular processes.',
    formula: 'Luciferin + O₂ →(luciferase) Oxyluciferin + light  •  λ_GFP = 509 nm  •  η_quantum ≈ 0.79',
    physics: { type: 'bioluminescence', wavelength: 509e-9 }
},
    // ═══ BATCH 1: Classical Mechanics, Thermodynamics, Early Physics Milestones ═══
    {
        id: 'galileo-inclined-plane',
        name: "Galileo's Inclined Plane",
        researcher: 'Galileo Galilei (1604)',
        status: 'verified',
        tags: ['mechanics','historical'],
        icon: 'fa-arrow-down',
        color: '#FFD700',
        description: 'Galileo rolled balls down inclined planes to study acceleration, disproving Aristotle and founding kinematics. Measured time with water clocks.',
        formula: 's = (1/2) a t²',
        physics: { type: 'inclined-plane', a: 9.8 * Math.sin(Math.PI/12) }
    },
    {
        id: 'newton-prism',
        name: "Newton's Prism Experiment",
        researcher: 'Isaac Newton (1666)',
        status: 'verified',
        tags: ['optics','historical'],
        icon: 'fa-rainbow',
        color: '#B0C4DE',
        description: 'Newton used a prism to split sunlight into a spectrum, proving white light is composed of all colors.',
        formula: 'n = c/v  •  λ (red) > λ (violet)',
        physics: { type: 'prism', n: 1.52 }
    },
    {
        id: 'cavendish-torsion',
        name: "Cavendish Torsion Balance",
        researcher: 'Henry Cavendish (1798)',
        status: 'verified',
        tags: ['gravity','historical'],
        icon: 'fa-balance-scale',
        color: '#A0522D',
        description: 'Measured the gravitational constant G by observing the twist of a wire due to lead spheres. First measurement of Earth\'s mass.',
        formula: 'F = G m₁m₂/r²',
        physics: { type: 'torsion-balance', G: 6.674e-11 }
    },
    {
        id: 'joule-paddle-wheel',
        name: "Joule's Paddle-Wheel Experiment",
        researcher: 'James Joule (1845)',
        status: 'verified',
        tags: ['thermodynamics','historical'],
        icon: 'fa-water',
        color: '#4682B4',
        description: 'Joule measured the mechanical equivalent of heat by stirring water with a falling weight, establishing energy conservation.',
        formula: 'ΔE = mgh = mcΔT',
        physics: { type: 'paddle-wheel', c: 4186 }
    },
    {
        id: 'foucault-pendulum',
        name: "Foucault Pendulum",
        researcher: 'Léon Foucault (1851)',
        status: 'verified',
        tags: ['mechanics','earth','historical'],
        icon: 'fa-globe',
        color: '#8B4513',
        description: 'Demonstrated Earth\'s rotation by observing the precession of a long pendulum in Paris.',
        formula: 'Ω = ω sin(φ)',
        physics: { type: 'pendulum', length: 67, latitude: 48.85 }
    },
    {
        id: 'maxwell-demon',
        name: "Maxwell's Demon Thought Experiment",
        researcher: 'James Clerk Maxwell (1867)',
        status: 'theoretical',
        tags: ['thermodynamics','quantum','historical'],
        icon: 'fa-ghost',
        color: '#7B68EE',
        description: 'A hypothetical demon sorts fast and slow molecules, challenging the second law of thermodynamics. Led to information theory in physics.',
        formula: 'ΔS ≥ 0',
        physics: { type: 'demon', entropy: true }
    },
    {
        id: 'moseley-xray',
        name: "Moseley's X-ray Spectra",
        researcher: 'Henry Moseley (1913)',
        status: 'verified',
        tags: ['atomic','xray','historical'],
        icon: 'fa-x-ray',
        color: '#4682B4',
        description: 'Showed atomic number determines X-ray frequencies, confirming the modern periodic table.',
        formula: '√ν = a(Z-b)',
        physics: { type: 'xray', Z: 26 }
    },
    {
        id: 'millikan-oil-drop',
        name: "Millikan Oil Drop Experiment",
        researcher: 'Robert Millikan (1909)',
        status: 'verified',
        tags: ['atomic','electrostatic','historical'],
        icon: 'fa-droplet',
        color: '#B8860B',
        description: 'Measured the elementary charge e by balancing oil droplets in an electric field.',
        formula: 'q = mg/E',
        physics: { type: 'oil-drop', e: 1.602e-19 }
    },
    {
        id: 'zeeman-effect',
        name: "Zeeman Effect",
        researcher: 'Pieter Zeeman (1896)',
        status: 'verified',
        tags: ['atomic','magnetic','historical'],
        icon: 'fa-magnet',
        color: '#4682B4',
        description: 'Splitting of spectral lines in a magnetic field, confirming electron spin and quantum theory.',
        formula: 'ΔE = μ_B B m_j',
        physics: { type: 'zeeman', B: 1.5 }
    },
    {
        id: 'davisson-germer',
        name: "Davisson-Germer Electron Diffraction",
        researcher: 'Clinton Davisson, Lester Germer (1927)',
        status: 'verified',
        tags: ['quantum','electron','historical'],
        icon: 'fa-wave-square',
        color: '#00CED1',
        description: 'Confirmed wave-particle duality by diffracting electrons off a nickel crystal.',
        formula: 'λ = h/p',
        physics: { type: 'electron-diffraction', energy: 54 }
    },
    {
        id: 'chadwick-neutron',
        name: "Chadwick's Neutron Discovery",
        researcher: 'James Chadwick (1932)',
        status: 'verified',
        tags: ['nuclear','historical'],
        icon: 'fa-atom',
        color: '#A9A9A9',
        description: 'Discovered the neutron by bombarding beryllium with alpha particles.',
        formula: 'α + Be → C + n',
        physics: { type: 'neutron', mass: 1.675e-27 }
    },
    {
        id: 'hess-cosmic-rays',
        name: "Hess Cosmic Ray Balloon",
        researcher: 'Victor Hess (1912)',
        status: 'verified',
        tags: ['cosmic','particle','historical'],
        icon: 'fa-meteor',
        color: '#4682B4',
        description: 'Discovered cosmic rays by measuring ionization at high altitude in a balloon.',
        formula: 'I ∝ e^{-h/H}',
        physics: { type: 'cosmic-ray', altitude: 5300 }
    },
    {
        id: 'hubble-expansion',
        name: "Hubble's Law of Expansion",
        researcher: 'Edwin Hubble (1929)',
        status: 'verified',
        tags: ['cosmology','expansion','historical'],
        icon: 'fa-arrow-up',
        color: '#4682B4',
        description: 'Measured redshift of galaxies, discovering the expanding universe.',
        formula: 'v = H₀d',
        physics: { type: 'expansion', H0: 70 }
    },
    {
        id: 'wilson-cloud-chamber',
        name: "Wilson Expansion Cloud Chamber",
        researcher: 'C.T.R. Wilson (1911)',
        status: 'verified',
        tags: ['particle','historical'],
        icon: 'fa-cloud',
        color: '#B0C4DE',
        description: 'Invented the cloud chamber to visualize ionizing radiation tracks.',
        formula: 'Supersaturation, condensation',
        physics: { type: 'cloud-chamber', temp: 273 }
    },
    {
        id: 'pauli-exclusion',
        name: "Pauli Exclusion Principle",
        researcher: 'Wolfgang Pauli (1925)',
        status: 'theoretical',
        tags: ['quantum','historical'],
        icon: 'fa-ban',
        color: '#4682B4',
        description: 'No two electrons can occupy the same quantum state. Foundation of atomic structure.',
        formula: 'n₁ ≠ n₂',
        physics: { type: 'exclusion', fermion: true }
    },
    {
        id: 'dirac-antimatter',
        name: "Dirac's Antimatter Prediction",
        researcher: 'Paul Dirac (1928)',
        status: 'theoretical',
        tags: ['quantum','antimatter','historical'],
        icon: 'fa-plus-minus',
        color: '#FF69B4',
        description: 'Predicted the existence of positrons and antimatter from the Dirac equation.',
        formula: 'E² = (pc)² + (mc²)²',
        physics: { type: 'antimatter', positron: true }
    },
    {
        id: 'gell-mann-quark',
        name: "Gell-Mann's Quark Model",
        researcher: 'Murray Gell-Mann (1964)',
        status: 'theoretical',
        tags: ['particle','quantum','historical'],
        icon: 'fa-cube',
        color: '#4682B4',
        description: 'Proposed quarks as fundamental constituents of protons and neutrons.',
        formula: 'uud, udd, ...',
        physics: { type: 'quark', flavors: 6 }
    },
    {
        id: 'aspect-bell-test',
        name: "Aspect Bell Test",
        researcher: 'Alain Aspect (1982)',
        status: 'verified',
        tags: ['quantum','entanglement','historical'],
        icon: 'fa-link',
        color: '#4682B4',
        description: 'Experimental violation of Bell\'s inequalities, confirming quantum entanglement.',
        formula: 'S > 2',
        physics: { type: 'bell-test', S: 2.7 }
        },
        // ═══ BATCH 2: Chemistry, Biology, Engineering, Medicine, Space Science ═══
        {
            id: 'lavoisier-combustion',
            name: "Lavoisier's Combustion Experiments",
            researcher: 'Antoine Lavoisier (1770s)',
            status: 'verified',
            tags: ['chemistry','historical'],
            icon: 'fa-fire',
            color: '#FF6347',
            description: 'Disproved phlogiston theory, established conservation of mass and oxygen\'s role in combustion.',
            formula: 'mass_in = mass_out',
            physics: { type: 'combustion', O2: true }
        },
        {
            id: 'mendel-genetics',
            name: "Mendel's Pea Plant Genetics",
            researcher: 'Gregor Mendel (1866)',
            status: 'verified',
            tags: ['biology','genetics','historical'],
            icon: 'fa-seedling',
            color: '#228B22',
            description: 'Founded genetics by cross-breeding pea plants and discovering inheritance patterns.',
            formula: '3:1 ratio (dominant:recessive)',
            physics: { type: 'genetics', ratio: '3:1' }
        },
        {
            id: 'pasteur-germ-theory',
            name: "Pasteur's Germ Theory",
            researcher: 'Louis Pasteur (1861)',
            status: 'verified',
            tags: ['biology','medicine','historical'],
            icon: 'fa-bacteria',
            color: '#BDB76B',
            description: 'Disproved spontaneous generation, proved microbes cause disease, invented pasteurization.',
            formula: 'Sterilization, swan-neck flask',
            physics: { type: 'germ-theory', sterilization: true }
        },
        {
            id: 'morse-telegraph',
            name: "Morse Telegraph",
            researcher: 'Samuel Morse (1837)',
            status: 'verified',
            tags: ['engineering','communication','historical'],
            icon: 'fa-telegram',
            color: '#4682B4',
            description: 'First practical long-distance electric telegraph, revolutionized communication.',
            formula: 'dot-dash code',
            physics: { type: 'telegraph', code: 'morse' }
        },
        {
            id: 'bell-telephone',
            name: "Bell Telephone",
            researcher: 'Alexander Graham Bell (1876)',
            status: 'verified',
            tags: ['engineering','communication','historical'],
            icon: 'fa-phone',
            color: '#4682B4',
            description: 'Invented the telephone, converting sound waves to electrical signals and back.',
            formula: 'sound → current → sound',
            physics: { type: 'telephone', analog: true }
        },
        {
            id: 'marconi-radio',
            name: "Marconi Wireless Radio",
            researcher: 'Guglielmo Marconi (1895)',
            status: 'verified',
            tags: ['engineering','communication','historical'],
            icon: 'fa-broadcast-tower',
            color: '#4682B4',
            description: 'First transatlantic radio transmission, ushered in the wireless age.',
            formula: 'EM wave propagation',
            physics: { type: 'radio', frequency: 500e3 }
        },
        {
            id: 'wright-flyer',
            name: "Wright Brothers' Flyer",
            researcher: 'Orville & Wilbur Wright (1903)',
            status: 'verified',
            tags: ['engineering','aeronautics','historical'],
            icon: 'fa-plane',
            color: '#4682B4',
            description: 'First powered, controlled, sustained flight.',
            formula: 'Lift > Weight',
            physics: { type: 'flight', lift: true }
        },
        {
            id: 'fleming-penicillin',
            name: "Fleming's Penicillin Discovery",
            researcher: 'Alexander Fleming (1928)',
            status: 'verified',
            tags: ['medicine','biology','historical'],
            icon: 'fa-capsules',
            color: '#4682B4',
            description: 'Discovered the first antibiotic, penicillin, from mold. Revolutionized medicine.',
            formula: 'Penicillium notatum',
            physics: { type: 'antibiotic', penicillin: true }
        },
        {
            id: 'watson-crick-dna',
            name: "Watson & Crick DNA Structure",
            researcher: 'James Watson, Francis Crick (1953)',
            status: 'verified',
            tags: ['biology','genetics','historical'],
            icon: 'fa-dna',
            color: '#4682B4',
            description: 'Discovered the double helix structure of DNA, the molecule of heredity.',
            formula: 'Double helix, base pairs',
            physics: { type: 'dna', helix: true }
        },
        {
            id: 'hubble-space-telescope',
            name: "Hubble Space Telescope",
            researcher: 'NASA/ESA (1990)',
            status: 'verified',
            tags: ['space','astronomy','engineering'],
            icon: 'fa-satellite',
            color: '#4682B4',
            description: 'Revolutionized astronomy with deep space imaging and discovery of exoplanets, dark energy.',
            formula: 'Optical telescope in orbit',
            physics: { type: 'telescope', orbit: 569 }
        },
        {
            id: 'crisper-cas9',
            name: "CRISPR-Cas9 Gene Editing",
            researcher: 'Jennifer Doudna, Emmanuelle Charpentier (2012)',
            status: 'verified',
            tags: ['biology','genetics','medicine'],
            icon: 'fa-dna',
            color: '#4682B4',
            description: 'Revolutionized genetic engineering with precise, programmable gene editing.',
            formula: 'CRISPR-Cas9 complex',
            physics: { type: 'gene-editing', crispr: true }
            },
            // ═══ BATCH 3: Modern Physics, Quantum Information, Computation, Advanced Engineering ═══
            {
                id: 'shor-quantum-algorithm',
                name: "Shor's Quantum Factoring Algorithm",
                researcher: 'Peter Shor (1994)',
                status: 'verified',
                tags: ['quantum','computing','mathematics'],
                icon: 'fa-qrcode',
                color: '#4682B4',
                description: 'First efficient quantum algorithm for factoring large numbers, threatening classical cryptography.',
                formula: 'Quantum Fourier Transform',
                physics: { type: 'quantum-algorithm', shor: true }
            },
            {
                id: 'grover-quantum-search',
                name: "Grover's Quantum Search Algorithm",
                researcher: 'Lov Grover (1996)',
                status: 'verified',
                tags: ['quantum','computing','mathematics'],
                icon: 'fa-search',
                color: '#4682B4',
                description: 'Quantum algorithm for searching unsorted databases quadratically faster than classical methods.',
                formula: 'O(√N) queries',
                physics: { type: 'quantum-algorithm', grover: true }
            },
            {
                id: 'bardeen-bcs-superconductivity',
                name: "BCS Theory of Superconductivity",
                researcher: 'Bardeen, Cooper, Schrieffer (1957)',
                status: 'verified',
                tags: ['quantum','superconductivity','historical'],
                icon: 'fa-icicles',
                color: '#4682B4',
                description: 'Explained superconductivity as electron pairs (Cooper pairs) condensing into a quantum ground state.',
                formula: 'Δ = 1.76 k_B T_c',
                physics: { type: 'superconductivity', bcs: true }
            },
            {
                id: 'feynman-path-integral',
                name: "Feynman Path Integral",
                researcher: 'Richard Feynman (1948)',
                status: 'theoretical',
                tags: ['quantum','mathematics','historical'],
                icon: 'fa-infinity',
                color: '#4682B4',
                description: 'Quantum amplitudes are sums over all possible paths. Foundation of quantum field theory.',
                formula: '⟨x_f|x_i⟩ = ∫ e^{iS/ħ} Dx',
                physics: { type: 'path-integral', feynman: true }
            },
            {
                id: 'turing-machine',
                name: "Turing Machine Computation",
                researcher: 'Alan Turing (1936)',
                status: 'theoretical',
                tags: ['computing','mathematics','historical'],
                icon: 'fa-cogs',
                color: '#4682B4',
                description: 'Abstract model of computation, foundation of computer science and the modern computer.',
                formula: 'States, tape, head, transition function',
                physics: { type: 'turing-machine', universal: true }
            },
            {
                id: 'von-neumann-architecture',
                name: "Von Neumann Computer Architecture",
                researcher: 'John von Neumann (1945)',
                status: 'verified',
                tags: ['computing','engineering','historical'],
                icon: 'fa-microchip',
                color: '#4682B4',
                description: 'Defined the architecture of modern computers: stored program, CPU, memory, I/O.',
                formula: 'CPU ↔ Memory ↔ I/O',
                physics: { type: 'computer-architecture', vonneumann: true }
            },
            {
                id: 'shannon-information',
                name: "Shannon Information Theory",
                researcher: 'Claude Shannon (1948)',
                status: 'verified',
                tags: ['information','mathematics','engineering'],
                icon: 'fa-signal',
                color: '#4682B4',
                description: 'Founded information theory, defined the bit, entropy, and channel capacity.',
                formula: 'H = -Σp log p',
                physics: { type: 'information', shannon: true }
            },
            {
                id: 'planck-blackbody',
                name: "Planck's Blackbody Radiation",
                researcher: 'Max Planck (1900)',
                status: 'verified',
                tags: ['quantum','thermodynamics','historical'],
                icon: 'fa-thermometer-half',
                color: '#4682B4',
                description: 'Solved the ultraviolet catastrophe, founding quantum theory.',
                formula: 'E = hν',
                physics: { type: 'blackbody', planck: true }
            },
            {
                id: 'higgs-mechanism',
                name: "Higgs Mechanism",
                researcher: 'Peter Higgs et al. (1964)',
                status: 'verified',
                tags: ['particle','quantum','historical'],
                icon: 'fa-atom',
                color: '#4682B4',
                description: 'Explains how particles acquire mass via the Higgs field. Confirmed by LHC in 2012.',
                formula: 'm = g v/√2',
                physics: { type: 'higgs', field: true }
                },
                // ═══ BATCH 4: Astrophysics, Cosmology, Geoscience, Planetary Science ═══
                {
                    id: 'michelson-morley',
                    name: "Michelson-Morley Ether Drift",
                    researcher: 'Albert Michelson, Edward Morley (1887)',
                    status: 'verified',
                    tags: ['cosmology','relativity','historical'],
                    icon: 'fa-wind',
                    color: '#4682B4',
                    description: 'Disproved the existence of the luminiferous ether, paving the way for special relativity.',
                    formula: 'Δt = 0',
                    physics: { type: 'ether-drift', null: true }
                },
                {
                    id: 'hubble-deep-field',
                    name: "Hubble Deep Field",
                    researcher: 'NASA/ESA (1995)',
                    status: 'verified',
                    tags: ['space','astronomy','cosmology'],
                    icon: 'fa-star',
                    color: '#4682B4',
                    description: 'Ultra-deep image of distant galaxies, revealing the scale and age of the universe.',
                    formula: 'z > 6',
                    physics: { type: 'deep-field', galaxies: 10000 }
                },
                {
                    id: 'pioneer-anomaly',
                    name: "Pioneer Anomaly",
                    researcher: 'NASA (1972-2002)',
                    status: 'anomalous',
                    tags: ['space','anomaly','engineering'],
                    icon: 'fa-rocket',
                    color: '#4682B4',
                    description: 'Unexplained deviation in the trajectories of Pioneer 10 and 11 spacecraft.',
                    formula: 'a = (8.74±1.33)×10⁻¹⁰ m/s²',
                    physics: { type: 'anomaly', pioneer: true }
                },
                {
                    id: 'chicxulub-impact',
                    name: "Chicxulub Impact Event",
                    researcher: 'Alvarez et al. (1980)',
                    status: 'verified',
                    tags: ['geoscience','planetary','historical'],
                    icon: 'fa-meteor',
                    color: '#4682B4',
                    description: 'Asteroid impact that caused the Cretaceous-Paleogene extinction event.',
                    formula: 'E ≈ 10²³ J',
                    physics: { type: 'impact', energy: 1e23 }
                },
                {
                    id: 'curiosity-mars-rover',
                    name: "Curiosity Mars Rover Landing",
                    researcher: 'NASA JPL (2012)',
                    status: 'verified',
                    tags: ['space','engineering','planetary'],
                    icon: 'fa-robot',
                    color: '#4682B4',
                    description: 'Robotic exploration of Mars, including sky crane landing and in-situ analysis.',
                    formula: 'EDL: Entry, Descent, Landing',
                    physics: { type: 'rover', mars: true }
                },
                {
                    id: 'gaia-milky-way',
                    name: "Gaia Milky Way Mapping",
                    researcher: 'ESA (2013–)',
                    status: 'verified',
                    tags: ['space','astronomy','cosmology'],
                    icon: 'fa-globe-europe',
                    color: '#4682B4',
                    description: 'Mapped over a billion stars in the Milky Way, revolutionizing galactic astronomy.',
                    formula: '1.8 billion stars',
                    physics: { type: 'mapping', gaia: true }
                    },
                    // ═══ BATCH 5: Materials Science, Nanotechnology, Environmental Science, Neuroscience ═══
                    {
                        id: 'geim-graphene',
                        name: "Geim & Novoselov Graphene Isolation",
                        researcher: 'Andre Geim, Konstantin Novoselov (2004)',
                        status: 'verified',
                        tags: ['materials','nanotechnology','historical'],
                        icon: 'fa-layer-group',
                        color: '#4682B4',
                        description: 'Isolated graphene, a single layer of carbon atoms, with Scotch tape. Revolutionized materials science.',
                        formula: 'sp² carbon lattice',
                        physics: { type: 'graphene', layers: 1 }
                    },
                    {
                        id: 'ibm-stm',
                        name: "IBM Scanning Tunneling Microscope (STM)",
                        researcher: 'Gerd Binnig, Heinrich Rohrer (1981)',
                        status: 'verified',
                        tags: ['nanotechnology','engineering','historical'],
                        icon: 'fa-microscope',
                        color: '#4682B4',
                        description: 'Invented the STM, allowing imaging and manipulation of individual atoms.',
                        formula: 'Quantum tunneling current',
                        physics: { type: 'stm', tunneling: true }
                    },
                    {
                        id: 'mullis-pcr',
                        name: "Mullis Polymerase Chain Reaction (PCR)",
                        researcher: 'Kary Mullis (1983)',
                        status: 'verified',
                        tags: ['biology','genetics','medicine'],
                        icon: 'fa-vial',
                        color: '#4682B4',
                        description: 'Invented PCR, enabling rapid DNA amplification and revolutionizing biology and medicine.',
                        formula: 'Denature, anneal, extend',
                        physics: { type: 'pcr', cycles: 30 }
                    },
                    {
                        id: 'raman-spectroscopy',
                        name: "Raman Spectroscopy",
                        researcher: 'C.V. Raman (1928)',
                        status: 'verified',
                        tags: ['materials','optics','historical'],
                        icon: 'fa-chart-line',
                        color: '#4682B4',
                        description: 'Discovered inelastic scattering of light (Raman effect), a key tool for material identification.',
                        formula: 'Δν = ν₀ - ν_vib',
                        physics: { type: 'raman', shift: true }
                    },
                    {
                        id: 'hubel-wiesel',
                        name: "Hubel & Wiesel Visual Cortex Mapping",
                        researcher: 'David Hubel, Torsten Wiesel (1959)',
                        status: 'verified',
                        tags: ['neuroscience','biology','historical'],
                        icon: 'fa-eye',
                        color: '#4682B4',
                        description: 'Mapped receptive fields in the visual cortex, foundational for neuroscience.',
                        formula: 'Neural response to orientation',
                        physics: { type: 'visual-cortex', mapping: true }
                    },
                    {
                        id: 'climate-keeling-curve',
                        name: "Keeling Curve (CO₂ Monitoring)",
                        researcher: 'Charles David Keeling (1958)',
                        status: 'verified',
                        tags: ['environment','climate','historical'],
                        icon: 'fa-chart-area',
                        color: '#4682B4',
                        description: 'First precise measurements of atmospheric CO₂, revealing the rise of greenhouse gases.',
                        formula: 'ppm CO₂ vs. time',
                        physics: { type: 'co2', keeling: true }
                    },
                    {
                        id: 'brainbow',
                        name: "Brainbow Neural Labeling",
                        researcher: 'Lichtman, Livet, Sanes (2007)',
                        status: 'verified',
                        tags: ['neuroscience','genetics','biology'],
                        icon: 'fa-brain',
                        color: '#4682B4',
                        description: 'Genetically engineered mice to express fluorescent proteins, mapping neural circuits in color.',
                        formula: 'Combinatorial expression of XFPs',
                        physics: { type: 'brainbow', colors: 100 }
                        },
                        // ═══ BATCH 6: Mathematics, Logic, Information, Social Science, Psychology ═══
                        {
                            id: 'gauss-prime-number',
                            name: "Gauss Prime Number Theorem",
                            researcher: 'Carl Friedrich Gauss (1792)',
                            status: 'theoretical',
                            tags: ['mathematics','number-theory','historical'],
                            icon: 'fa-hashtag',
                            color: '#4682B4',
                            description: 'Estimated the distribution of prime numbers, foundation of analytic number theory.',
                            formula: 'π(x) ~ x/ln(x)',
                            physics: { type: 'prime-number', gauss: true }
                        },
                        {
                            id: 'godel-incompleteness',
                            name: "Gödel's Incompleteness Theorems",
                            researcher: 'Kurt Gödel (1931)',
                            status: 'theoretical',
                            tags: ['mathematics','logic','historical'],
                            icon: 'fa-infinity',
                            color: '#4682B4',
                            description: 'Proved that any consistent formal system is incomplete; some truths are unprovable.',
                            formula: 'No complete, consistent system',
                            physics: { type: 'incompleteness', godel: true }
                        },
                        {
                            id: 'nash-equilibrium',
                            name: "Nash Equilibrium (Game Theory)",
                            researcher: 'John Nash (1950)',
                            status: 'theoretical',
                            tags: ['mathematics','game-theory','social'],
                            icon: 'fa-users',
                            color: '#4682B4',
                            description: 'Defined equilibrium in non-cooperative games, foundation of modern economics and social science.',
                            formula: 'No player can benefit by changing strategy',
                            physics: { type: 'game-theory', nash: true }
                        },
                        {
                            id: 'stanley-milgram',
                            name: "Milgram Obedience Experiment",
                            researcher: 'Stanley Milgram (1961)',
                            status: 'verified',
                            tags: ['psychology','social','historical'],
                            icon: 'fa-user-shield',
                            color: '#4682B4',
                            description: 'Studied obedience to authority by instructing participants to administer shocks to others.',
                            formula: 'Authority → obedience',
                            physics: { type: 'obedience', milgram: true }
                                },
                        {
                            id: 'turing-test',
                            name: "Turing Test for AI",
                            researcher: 'Alan Turing (1950)',
                            status: 'theoretical',
                            tags: ['computing','ai','psychology'],
                            icon: 'fa-robot',
                            color: '#4682B4',
                            description: 'Proposed a test for machine intelligence: can a computer imitate a human in conversation?',
                            formula: 'Imitation game',
                            physics: { type: 'ai', turing: true }
                                },
                        {
                            id: 'stanford-prison',
                            name: "Stanford Prison Experiment",
                            researcher: 'Philip Zimbardo (1971)',
                            status: 'verified',
                            tags: ['psychology','social','historical'],
                            icon: 'fa-gavel',
                            color: '#4682B4',
                            description: 'Simulated prison environment to study the psychological effects of perceived power.',
                            formula: 'Role assignment → behavior',
                            physics: { type: 'prison', zimbardo: true }
                        },
                        // ...universal coverage complete...
                    ];

// ══════════════════════════════════════════════
//  MINI CANVAS ANIMATORS — one per experiment type
// ══════════════════════════════════════════════

const PI2 = 2 * Math.PI;

class ExperimentAnimator {
                // Pauli Exclusion Principle: Two electrons, different quantum states
                _drawPauliExclusion() {
                    const ctx = this.ctx;
                    // Draw two electron orbits
                    ctx.strokeStyle = '#88BBFF';
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    ctx.arc(this.cx - 30, this.cy, 40, 0, 2 * Math.PI);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.arc(this.cx + 30, this.cy, 40, 0, 2 * Math.PI);
                    ctx.stroke();
                    // Draw electrons
                    const t = this.t * 1.2;
                    const e1x = this.cx - 30 + 40 * Math.cos(t);
                    const e1y = this.cy + 40 * Math.sin(t);
                    const e2x = this.cx + 30 + 40 * Math.cos(-t);
                    const e2y = this.cy + 40 * Math.sin(-t);
                    ctx.fillStyle = '#00D4FF';
                    ctx.beginPath(); ctx.arc(e1x, e1y, 10, 0, 2 * Math.PI); ctx.fill();
                    ctx.beginPath(); ctx.arc(e2x, e2y, 10, 0, 2 * Math.PI); ctx.fill();
                    // n₁ ≠ n₂
                    ctx.fillStyle = '#e8e8f0';
                    ctx.font = 'bold 16px SF Mono, monospace';
                    ctx.textAlign = 'center';
                    ctx.fillText('n₁ ≠ n₂', this.cx, this.cy + 60);
                }

                // Dirac's Antimatter Prediction: Electron and positron, E² = (pc)² + (mc²)²
                _drawDiracAntimatter() {
                    const ctx = this.ctx;
                    // Draw electron (blue) and positron (red)
                    const t = this.t * 1.1;
                    const ex = this.cx - 40 + 60 * Math.cos(t);
                    const ey = this.cy + 30 * Math.sin(t);
                    const px = this.cx + 40 - 60 * Math.cos(t);
                    const py = this.cy - 30 * Math.sin(t);
                    ctx.fillStyle = '#00D4FF';
                    ctx.beginPath(); ctx.arc(ex, ey, 12, 0, 2 * Math.PI); ctx.fill();
                    ctx.fillStyle = '#FF3366';
                    ctx.beginPath(); ctx.arc(px, py, 12, 0, 2 * Math.PI); ctx.fill();
                    // Draw arrow between them
                    ctx.strokeStyle = '#fff';
                    ctx.lineWidth = 2;
                    ctx.beginPath(); ctx.moveTo(ex, ey); ctx.lineTo(px, py); ctx.stroke();
                    // Formula
                    ctx.fillStyle = '#e8e8f0';
                    ctx.font = 'bold 13px SF Mono, monospace';
                    ctx.textAlign = 'center';
                    ctx.fillText('E² = (pc)² + (mc²)²', this.cx, this.cy + 60);
                }

                // Gell-Mann's Quark Model: Three quarks in a triangle, colored
                _drawQuarkModel() {
                    const ctx = this.ctx;
                    // Triangle vertices
                    const r = 40;
                    const angles = [this.t, this.t + 2 * Math.PI / 3, this.t + 4 * Math.PI / 3];
                    const colors = ['#FF3333', '#33FF33', '#3333FF'];
                    for (let i = 0; i < 3; i++) {
                        const x = this.cx + r * Math.cos(angles[i]);
                        const y = this.cy + r * Math.sin(angles[i]);
                        ctx.fillStyle = colors[i];
                        ctx.beginPath(); ctx.arc(x, y, 14, 0, 2 * Math.PI); ctx.fill();
                    }
                    // Connect with lines
                    ctx.strokeStyle = '#e8e8f0';
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    for (let i = 0; i < 3; i++) {
                        const x1 = this.cx + r * Math.cos(angles[i]);
                        const y1 = this.cy + r * Math.sin(angles[i]);
                        const x2 = this.cx + r * Math.cos(angles[(i + 1) % 3]);
                        const y2 = this.cy + r * Math.sin(angles[(i + 1) % 3]);
                        ctx.moveTo(x1, y1); ctx.lineTo(x2, y2);
                    }
                    ctx.stroke();
                    // Label
                    ctx.fillStyle = '#e8e8f0';
                    ctx.font = 'bold 13px SF Mono, monospace';
                    ctx.textAlign = 'center';
                    ctx.fillText('Quark Model', this.cx, this.cy + 60);
                }

                // Element 115 Gravity Amplifier: Three amplifiers, central disc
                _drawGravityAmplifier() {
                    const ctx = this.ctx;
                    // Central disc (Element 115)
                    ctx.save();
                    ctx.globalAlpha = 0.85;
                    ctx.beginPath();
                    ctx.arc(this.cx, this.cy, 28, 0, 2 * Math.PI);
                    ctx.fillStyle = '#b388ff';
                    ctx.shadowColor = '#b388ff';
                    ctx.shadowBlur = 18;
                    ctx.fill();
                    ctx.restore();
                    // Three amplifiers
                    for (let i = 0; i < 3; i++) {
                        const angle = this.t + i * (2 * Math.PI / 3);
                        const ax = this.cx + 60 * Math.cos(angle);
                        const ay = this.cy + 60 * Math.sin(angle);
                        ctx.beginPath();
                        ctx.arc(ax, ay, 18, 0, 2 * Math.PI);
                        ctx.fillStyle = '#00FF88';
                        ctx.globalAlpha = 0.7;
                        ctx.fill();
                        ctx.globalAlpha = 1;
                        // Beam
                        ctx.strokeStyle = '#00FF88';
                        ctx.lineWidth = 3;
                        ctx.beginPath();
                        ctx.moveTo(ax, ay);
                        ctx.lineTo(this.cx, this.cy);
                        ctx.stroke();
                    }
                    // Label
                    ctx.fillStyle = '#e8e8f0';
                    ctx.font = 'bold 13px SF Mono, monospace';
                    ctx.textAlign = 'center';
                    ctx.fillText('Element 115', this.cx, this.cy + 60);
                }
        // ── Placeholder Visualizations for Classic Experiments ──
        _drawInclinedPlane() {
            const ctx = this.ctx;
            // Draw inclined plane
            ctx.strokeStyle = '#FFD700';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.moveTo(this.cx - 80, this.cy + 60);
            ctx.lineTo(this.cx + 80, this.cy - 40);
            ctx.stroke();
            // Ball
            const t = (Math.sin(this.t) + 1) / 2;
            const bx = this.cx - 80 + 160 * t;
            const by = this.cy + 60 - 100 * t;
            ctx.fillStyle = '#FFD700';
            ctx.beginPath();
            ctx.arc(bx, by, 16, 0, PI2);
            ctx.fill();
        }
        _drawPrism() {
            const ctx = this.ctx;
            // Prism triangle
            ctx.fillStyle = '#B0C4DE';
            ctx.beginPath();
            ctx.moveTo(this.cx - 40, this.cy + 40);
            ctx.lineTo(this.cx + 40, this.cy + 40);
            ctx.lineTo(this.cx, this.cy - 40);
            ctx.closePath();
            ctx.fill();
            // White light in
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 6;
            ctx.beginPath();
            ctx.moveTo(this.cx - 100, this.cy + 20);
            ctx.lineTo(this.cx - 40, this.cy + 40);
            ctx.stroke();
            // Spectrum out
            const colors = ['#f00','#ff0','#0f0','#0ff','#00f','#a0f'];
            for(let i=0;i<colors.length;i++){
                ctx.strokeStyle = colors[i];
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(this.cx + 40, this.cy + 40);
                ctx.lineTo(this.cx + 100, this.cy + 10 - i*8);
                ctx.stroke();
            }
        }
        _drawTorsionBalance() {
            const ctx = this.ctx;
            // Horizontal bar
            ctx.strokeStyle = '#A0522D';
            ctx.lineWidth = 5;
            ctx.beginPath();
            ctx.moveTo(this.cx - 60, this.cy);
            ctx.lineTo(this.cx + 60, this.cy);
            ctx.stroke();
            // Spheres
            ctx.fillStyle = '#A0522D';
            ctx.beginPath();
            ctx.arc(this.cx - 60, this.cy, 18, 0, PI2);
            ctx.arc(this.cx + 60, this.cy, 18, 0, PI2);
            ctx.fill();
            // Wire
            ctx.strokeStyle = '#888';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(this.cx, this.cy - 60);
            ctx.lineTo(this.cx, this.cy);
            ctx.stroke();
        }
        _drawPaddleWheel() {
            const ctx = this.ctx;
            // Water
            ctx.fillStyle = '#4682B4';
            ctx.fillRect(this.cx - 60, this.cy + 30, 120, 40);
            // Paddle wheel
            ctx.save();
            ctx.translate(this.cx, this.cy + 30);
            ctx.rotate(this.t);
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 4;
            for(let i=0;i<6;i++){
                ctx.beginPath();
                ctx.moveTo(0,0);
                ctx.lineTo(0,-30);
                ctx.stroke();
                ctx.rotate(PI2/6);
            }
            ctx.restore();
        }
        _drawPendulum() {
            const ctx = this.ctx;
            // String
            ctx.strokeStyle = '#8B4513';
            ctx.lineWidth = 3;
            const angle = Math.sin(this.t) * 0.5;
            const px = this.cx + Math.sin(angle) * 80;
            const py = this.cy - 60 + Math.cos(angle) * 120;
            ctx.beginPath();
            ctx.moveTo(this.cx, this.cy - 60);
            ctx.lineTo(px, py);
            ctx.stroke();
            // Bob
            ctx.fillStyle = '#8B4513';
            ctx.beginPath();
            ctx.arc(px, py, 20, 0, PI2);
            ctx.fill();
        }
        _drawDemon() {
            const ctx = this.ctx;
            // Two chambers
            ctx.fillStyle = '#7B68EE';
            ctx.fillRect(this.cx - 70, this.cy - 30, 60, 60);
            ctx.fillRect(this.cx + 10, this.cy - 30, 60, 60);
            // Door
            ctx.fillStyle = '#fff';
            ctx.fillRect(this.cx - 5, this.cy - 10, 10, 20);
            // Demon (circle)
            ctx.fillStyle = '#7B68EE';
            ctx.beginPath();
            ctx.arc(this.cx, this.cy - 40, 12, 0, PI2);
            ctx.fill();
        }
        _drawXray() {
            const ctx = this.ctx;
            // X-ray tube
            ctx.strokeStyle = '#4682B4';
            ctx.lineWidth = 5;
            ctx.beginPath();
            ctx.moveTo(this.cx - 40, this.cy);
            ctx.lineTo(this.cx + 40, this.cy);
            ctx.stroke();
            // Rays
            for(let i=0;i<7;i++){
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(this.cx, this.cy);
                ctx.lineTo(this.cx + 60 + i*6, this.cy - 30 + i*8);
                ctx.stroke();
            }
        }
        _drawOilDrop() {
            const ctx = this.ctx;
            // Chamber
            ctx.strokeStyle = '#B8860B';
            ctx.lineWidth = 3;
            ctx.strokeRect(this.cx - 30, this.cy - 60, 60, 120);
            // Oil drop
            const dropY = this.cy - 40 + Math.sin(this.t) * 30;
            ctx.fillStyle = '#B8860B';
            ctx.beginPath();
            ctx.arc(this.cx, dropY, 14, 0, PI2);
            ctx.fill();
        }
        _drawZeeman() {
            const ctx = this.ctx;
            // Magnetic field lines
            ctx.strokeStyle = '#4682B4';
            ctx.lineWidth = 2;
            for(let i=-2;i<=2;i++){
                ctx.beginPath();
                ctx.moveTo(this.cx - 40, this.cy + i*12);
                ctx.lineTo(this.cx + 40, this.cy + i*12);
                ctx.stroke();
            }
            // Split lines
            ctx.strokeStyle = '#FF0';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.moveTo(this.cx - 10, this.cy);
            ctx.lineTo(this.cx + 10, this.cy);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(this.cx - 10, this.cy - 16);
            ctx.lineTo(this.cx + 10, this.cy - 16);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(this.cx - 10, this.cy + 16);
            ctx.lineTo(this.cx + 10, this.cy + 16);
            ctx.stroke();
        }
        _drawElectronDiffraction() {
            const ctx = this.ctx;
            // Crystal
            ctx.fillStyle = '#00CED1';
            ctx.fillRect(this.cx - 40, this.cy + 20, 80, 12);
            // Diffraction pattern
            for(let i=0;i<7;i++){
                ctx.fillStyle = `rgba(0,206,209,${0.2 + 0.1*i})`;
                ctx.beginPath();
                ctx.arc(this.cx, this.cy - 30 - i*12, 18 + i*7, 0, PI2);
                ctx.fill();
            }
        }
        _drawNeutron() {
            const ctx = this.ctx;
            // Beryllium target
            ctx.fillStyle = '#A9A9A9';
            ctx.fillRect(this.cx - 30, this.cy + 10, 60, 18);
            // Alpha particle
            ctx.fillStyle = '#FF6666';
            ctx.beginPath();
            ctx.arc(this.cx - 40 + Math.abs(Math.sin(this.t))*80, this.cy + 20, 10, 0, PI2);
            ctx.fill();
            // Neutron
            ctx.fillStyle = '#fff';
            ctx.beginPath();
            ctx.arc(this.cx + 40 - Math.abs(Math.sin(this.t))*80, this.cy - 20, 10, 0, PI2);
            ctx.fill();
        }
        _drawCosmicRay() {
            const ctx = this.ctx;
            // Balloon
            ctx.fillStyle = '#4682B4';
            ctx.beginPath();
            ctx.ellipse(this.cx, this.cy - 40, 30, 40, 0, 0, PI2);
            ctx.fill();
            // Ray
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(this.cx, this.cy - 80);
            ctx.lineTo(this.cx, this.cy + 40);
            ctx.stroke();
        }
        _drawExpansion() {
            const ctx = this.ctx;
            // Galaxies
            for(let i=0;i<7;i++){
                const r = 30 + i*18 + Math.sin(this.t+i)*8;
                const a = this.t*0.2 + i;
                ctx.fillStyle = '#4682B4';
                ctx.beginPath();
                ctx.arc(this.cx + Math.cos(a)*r, this.cy + Math.sin(a)*r, 8, 0, PI2);
                ctx.fill();
            }
            // Arrows
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            for(let i=0;i<7;i++){
                const r = 30 + i*18 + Math.sin(this.t+i)*8;
                const a = this.t*0.2 + i;
                ctx.beginPath();
                ctx.moveTo(this.cx, this.cy);
                ctx.lineTo(this.cx + Math.cos(a)*r, this.cy + Math.sin(a)*r);
                ctx.stroke();
            }
        }
    constructor(canvas, exp) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.exp = exp;
        this.t = Math.random() * 100;
        this.sparks = [];
        this.particles = [];
        this.running = false;
        this.frameId = null;

        this._resize();
        this._initParticles();

        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting && !this.running) this.start();
                else if (!e.isIntersecting && this.running) this.stop();
            });
        }, { threshold: 0.1 });
        obs.observe(canvas);
    }

    _resize() {
        const r = this.canvas.parentElement.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = r.width * dpr;
        this.canvas.height = 220 * dpr;
        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        this.w = r.width;
        this.h = 220;
        this.cx = this.w / 2;
        this.cy = this.h / 2;
    }

    _initParticles() {
        this.particles = [];
        const n = 12 + Math.floor(Math.random() * 8);
        for (let i = 0; i < n; i++) {
            this.particles.push({
                x: Math.random() * this.w,
                y: Math.random() * this.h,
                vx: (Math.random() - 0.5) * 2,
                vy: (Math.random() - 0.5) * 2,
                r: 1 + Math.random() * 2,
                phase: Math.random() * PI2,
                speed: 0.5 + Math.random() * 1.5
            });
        }
    }

    _bg() {
        const ctx = this.ctx;
        const g = ctx.createRadialGradient(this.cx, this.cy, 0, this.cx, this.cy, this.w * 0.6);
        g.addColorStop(0, 'rgba(10,4,20,1)');
        g.addColorStop(1, '#020208');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, this.w, this.h);
    }

    _spark(x, y, color, count) {
        for (let i = 0; i < (count || 2); i++) {
            this.sparks.push({
                x, y,
                vx: (Math.random() - 0.5) * 6,
                vy: (Math.random() - 0.5) * 6,
                life: 1, decay: 0.03 + Math.random() * 0.04,
                color: color || this.exp.color,
                size: 1 + Math.random() * 2
            });
        }
    }

    _drawSparks() {
        const ctx = this.ctx;
        this.sparks = this.sparks.filter(s => {
            s.x += s.vx; s.y += s.vy;
            s.vx *= 0.95; s.vy *= 0.95;
            s.life -= s.decay;
            if (s.life <= 0) return false;
            ctx.globalAlpha = s.life;
            ctx.fillStyle = s.color;
            ctx.shadowColor = s.color;
            ctx.shadowBlur = 5;
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.size * s.life, 0, PI2);
            ctx.fill();
            return true;
        });
        ctx.globalAlpha = 1;
        ctx.shadowBlur = 0;
    }

    _drawGlow(x, y, r, color, alpha) {
        const ctx = this.ctx;
        const g = ctx.createRadialGradient(x, y, 0, x, y, r);
        g.addColorStop(0, color);
        g.addColorStop(0.4, color + '60');
        g.addColorStop(1, color + '00');
        ctx.globalAlpha = alpha || 0.5;
        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.arc(x, y, r, 0, PI2);
        ctx.fill();
        ctx.globalAlpha = 1;
    }

    // ── Specific visualizations per experiment type ──

    _drawTesla() {
        const ctx = this.ctx;
        // Primary coil
        this._drawGlow(this.cx - 40, this.cy, 30, '#FF3366', 0.4);
        // Secondary coil
        this._drawGlow(this.cx + 40, this.cy - 20, 25, '#FF6699', 0.3);
        // Top load
        this._drawGlow(this.cx + 40, this.cy - 60, 15, '#FFD700', 0.6);

        // Lightning arcs from top
        const arcCount = 3 + Math.floor(Math.sin(this.t * 3) * 2);
        for (let a = 0; a < arcCount; a++) {
            ctx.beginPath();
            let lx = this.cx + 40, ly = this.cy - 60;
            ctx.moveTo(lx, ly);
            const angle = (a / arcCount) * Math.PI - Math.PI / 2 + Math.sin(this.t * 5 + a) * 0.3;
            const len = 40 + 30 * Math.sin(this.t * 4 + a * 2);
            const segs = 8;
            for (let s = 1; s <= segs; s++) {
                const frac = s / segs;
                lx += Math.cos(angle) * len / segs + (Math.random() - 0.5) * 12;
                ly += Math.sin(angle) * len / segs + (Math.random() - 0.5) * 12;
                ctx.lineTo(lx, ly);
            }
            ctx.strokeStyle = `rgba(255,200,255,${0.5 + 0.3 * Math.sin(this.t * 10)})`;
            ctx.lineWidth = 1 + Math.random();
            ctx.shadowColor = '#FF3366';
            ctx.shadowBlur = 10;
            ctx.stroke();
            if (Math.random() < 0.15) this._spark(lx, ly, '#FF3366', 3);
        }
        ctx.shadowBlur = 0;
    }

    _drawCasimir() {
        const ctx = this.ctx;
        const plateW = 8, plateH = 100;
        const gap = 30 + 5 * Math.sin(this.t * 2);
        const lx = this.cx - gap / 2; const rx = this.cx + gap / 2;

        // Plates
        ctx.fillStyle = '#00FFCC';
        ctx.shadowColor = '#00FFCC';
        ctx.shadowBlur = 10;
        ctx.fillRect(lx - plateW, this.cy - plateH / 2, plateW, plateH);
        ctx.fillRect(rx, this.cy - plateH / 2, plateW, plateH);
        ctx.shadowBlur = 0;

        // Vacuum fluctuations between plates
        for (let i = 0; i < 15; i++) {
            const fx = lx + Math.random() * gap;
            const fy = this.cy - plateH / 2 + Math.random() * plateH;
            const osc = Math.sin(this.t * 8 + i * 0.7) * 0.4 + 0.4;
            ctx.fillStyle = `rgba(0,255,204,${osc * 0.5})`;
            ctx.beginPath();
            ctx.arc(fx, fy, 1.5, 0, PI2);
            ctx.fill();
        }

        // Force arrows
        const arrowAlpha = 0.4 + 0.3 * Math.sin(this.t * 3);
        ctx.strokeStyle = `rgba(0,255,204,${arrowAlpha})`;
        ctx.lineWidth = 2;
        for (let i = 0; i < 3; i++) {
            const ay = this.cy - 25 + i * 25;
            ctx.beginPath(); ctx.moveTo(lx + 8, ay); ctx.lineTo(lx + 2, ay); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(rx - 8, ay); ctx.lineTo(rx - 2, ay); ctx.stroke();
        }
    }

    _drawInterference() {
        const ctx = this.ctx;
        // Two EM sources
        const s1x = this.cx - 80, s2x = this.cx + 80;
        this._drawGlow(s1x, this.cy, 20, '#FF00FF', 0.4);
        this._drawGlow(s2x, this.cy, 20, '#00FFFF', 0.4);

        // Interference rings from each
        for (let r = 0; r < 6; r++) {
            const rad = (r * 25 + this.t * 40) % 160;
            const alpha = Math.max(0, 0.3 - rad / 500);
            ctx.beginPath(); 
            ctx.arc(s1x, this.cy, rad, 0, PI2);
            ctx.strokeStyle = `rgba(255,0,255,${alpha})`;
            ctx.lineWidth = 1.2;
            ctx.stroke();
            ctx.beginPath(); 
            ctx.arc(s2x, this.cy, rad, 0, PI2);
            ctx.strokeStyle = `rgba(0,255,255,${alpha})`;
            ctx.lineWidth = 1.2;
            ctx.stroke();
        }

        // Center flash and random spark
        const flashI = Math.abs(Math.sin(this.t * 6));
        this._drawGlow(this.cx, this.cy, 30 * flashI, '#FFFFFF', flashI * 0.5);
        if (Math.random() < 0.2) this._spark(this.cx + (Math.random() - 0.5) * 40, this.cy + (Math.random() - 0.5) * 40, '#FF88FF', 2);
    }

    _drawMotorPulse() {
        const ctx = this.ctx;
        // Spinning rotor
        ctx.save();
        ctx.translate(this.cx, this.cy);
        ctx.rotate(this.t * 2);
        for (let i = 0; i < 6; i++) {
            const a = (i / 6) * PI2;
            ctx.fillStyle = i % 2 === 0 ? '#00CCFF' : '#0066AA';
            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.arc(0, 0, 40, a, a + PI2 / 6);
            ctx.fill();
        }
        ctx.restore();

        // Coil glow
        this._drawGlow(this.cx, this.cy, 50, '#00CCFF', 0.2);

        // Back-EMF spike
        const spike = Math.abs(Math.sin(this.t * 8));
        if (spike > 0.9) {
            this._drawGlow(this.cx, this.cy - 50, 15 * spike, '#FFFFFF', spike);
            this._spark(this.cx, this.cy - 50, '#00FFFF', 4);
        }

        // Battery
        ctx.fillStyle = '#00FF88';
        ctx.fillRect(this.cx + 80, this.cy - 15, 20, 30);
        ctx.fillStyle = '#020208';
        ctx.fillRect(this.cx + 84, this.cy - 11, 12, 22);
        ctx.fillStyle = '#00FF88';
        const charge = (Math.sin(this.t * 0.5) + 1) / 2;
        ctx.fillRect(this.cx + 84, this.cy - 11 + 22 * (1 - charge), 12, 22 * charge);
    }

    _drawResonantStep() {
        const ctx = this.ctx;
        // Step-up visualization
        const stages = 4;
        for (let i = 0; i < stages; i++) {
            const sx = 50 + i * (this.w - 100) / stages;
            const h = 15 + i * 20;
            const pulse = Math.sin(this.t * 4 + i * 1.5);
            ctx.fillStyle = `rgba(255,136,0,${0.3 + Math.abs(pulse) * 0.4})`;
            ctx.fillRect(sx - 10, this.cy - h / 2, 20, h);
            this._drawGlow(sx, this.cy, 15 + i * 5, '#FF8800', 0.2 + Math.abs(pulse) * 0.2);
        }

        // Energy flow between stages
        ctx.strokeStyle = 'rgba(255,136,0,0.3)';
        ctx.lineWidth = 1;
        ctx.setLineDash([4, 4]);
        ctx.beginPath();
        ctx.moveTo(50, this.cy);
        ctx.lineTo(this.w - 50, this.cy);
        ctx.stroke();
        ctx.setLineDash([]);

        // Final spark
        if (Math.sin(this.t * 3) > 0.8) {
            this._spark(this.w - 60, this.cy, '#FFAA00', 5);
        }
    }

    _drawSEG() {
        const ctx = this.ctx;
        // Central ring
        ctx.strokeStyle = '#FF66AA';
        ctx.lineWidth = 3;
        ctx.shadowColor = '#FF66AA';
        ctx.shadowBlur = 8;
        ctx.beginPath();
        ctx.arc(this.cx, this.cy, 50, 0, PI2);
        ctx.stroke();

        // Second ring
        ctx.strokeStyle = 'rgba(255,102,170,0.5)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(this.cx, this.cy, 70, 0, PI2);
        ctx.stroke();
        ctx.shadowBlur = 0;

        // Rollers orbiting
        const rollerCount = this.exp.physics.rollerCount || 12;
        for (let i = 0; i < rollerCount; i++) {
            const angle = (i / rollerCount) * PI2 + this.t * 1.5;
            const layer = i < rollerCount / 2 ? 50 : 70;
            const rx = this.cx + layer * Math.cos(angle);
            const ry = this.cy + layer * Math.sin(angle);
            this._drawGlow(rx, ry, 6, '#FF66AA', 0.6);
            ctx.fillStyle = '#FFD4E8';
            ctx.beginPath();
            ctx.arc(rx, ry, 3, 0, PI2);
            ctx.fill();

            // Trail sparks
            if (Math.random() < 0.08) this._spark(rx, ry, '#FF66AA', 1);
        }

        // Center glow pulse
        const pulse = 0.3 + 0.2 * Math.sin(this.t * 3);
        this._drawGlow(this.cx, this.cy, 30, '#FF66AA', pulse);
    }

    _drawVTA() {
        const ctx = this.ctx;
        // Conditioned magnets (two blocks)
        const magW = 30, magH = 60;
        ctx.fillStyle = '#CC66FF';
        ctx.shadowColor = '#CC66FF';
        ctx.shadowBlur = 10;
        ctx.fillRect(this.cx - 50, this.cy - magH / 2, magW, magH);
        ctx.fillRect(this.cx + 20, this.cy - magH / 2, magW, magH);
        ctx.shadowBlur = 0;

        // Oscillating field between magnets
        for (let y = -magH / 2; y < magH / 2; y += 6) {
            const osc = Math.sin(this.t * 8 + y * 0.1) * 15;
            ctx.strokeStyle = `rgba(204,102,255,${0.3 + 0.2 * Math.sin(this.t * 4 + y * 0.1)})`;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(this.cx - 20, this.cy + y);
            ctx.quadraticCurveTo(this.cx, this.cy + y + osc, this.cx + 20, this.cy + y);
            ctx.stroke();
        }

        // Output glow (cold electricity claim)
        const outPulse = (Math.sin(this.t * 5) + 1) / 2;
        this._drawGlow(this.cx + 90, this.cy, 20 * outPulse, '#00CCFF', outPulse * 0.5);
        ctx.fillStyle = '#e8e8f0';
        ctx.font = '600 9px "SF Mono", monospace';
        ctx.textAlign = 'center';
        ctx.fillText('500W OUT', this.cx + 90, this.cy + 30);
        ctx.fillStyle = 'rgba(255,255,255,0.3)';
        ctx.fillText('~0W IN', this.cx - 90, this.cy + 30);
    }

    _drawMEG() {
        const ctx = this.ctx;
        // Permanent magnet core
        ctx.fillStyle = '#FF4444';
        ctx.fillRect(this.cx - 5, this.cy - 40, 10, 80);

        // Two flux paths
        const pathAlpha = 0.5 + 0.3 * Math.sin(this.t * 4);
        ctx.strokeStyle = `rgba(255,153,51,${pathAlpha})`;
        ctx.lineWidth = 2;
        // Left path
        ctx.beginPath();
        ctx.arc(this.cx - 40, this.cy, 35, -Math.PI / 2, Math.PI / 2);
        ctx.stroke();
        // Right path
        ctx.beginPath();
        ctx.arc(this.cx + 40, this.cy, 35, Math.PI / 2, -Math.PI / 2);
        ctx.stroke();

        // Switching — flux alternates
        const switchSide = Math.sin(this.t * 4) > 0;
        const activeX = switchSide ? this.cx - 40 : this.cx + 40;
        this._drawGlow(activeX, this.cy, 25, '#FF9933', 0.4);
        if (Math.abs(Math.sin(this.t * 4)) > 0.95) {
            this._spark(this.cx, this.cy, '#FFD700', 3);
        }

        // Coils on paths
        ctx.fillStyle = '#FFB800';
        ctx.fillRect(this.cx - 60, this.cy - 8, 12, 16);
        ctx.fillRect(this.cx + 48, this.cy - 8, 12, 16);
    }

    _drawLENR() {
        const ctx = this.ctx;
        // Electrolytic cell
        ctx.strokeStyle = 'rgba(100,150,255,0.3)';
        ctx.lineWidth = 2;
        ctx.strokeRect(this.cx - 40, this.cy - 50, 80, 100);

        // D₂O / water
        ctx.fillStyle = 'rgba(0,100,255,0.15)';
        ctx.fillRect(this.cx - 39, this.cy - 20, 78, 69);

        // Cathode (Pd or Ni)
        const cathColor = this.exp.physics.cathode === 'Pd' ? '#B8B8D0' : '#88AA88';
        ctx.fillStyle = cathColor;
        ctx.fillRect(this.cx - 5, this.cy - 45, 10, 90);
        this._drawGlow(this.cx, this.cy, 15, '#FF4444', 0.3 + 0.2 * Math.sin(this.t * 3));

        // Bubbles
        for (let i = 0; i < 6; i++) {
            const bx = this.cx - 30 + Math.random() * 60;
            const baseY = this.cy + 40;
            const by = baseY - ((this.t * 40 + i * 30) % 80);
            const br = 1 + Math.random() * 2;
            ctx.fillStyle = `rgba(200,220,255,${0.3 + Math.random() * 0.3})`;
            ctx.beginPath();
            ctx.arc(bx, by, br, 0, PI2);
            ctx.fill();
        }

        // Excess heat glow
        const heat = this.exp.physics.excessHeat / 100;
        if (Math.sin(this.t * 2) > 0.3) {
            this._drawGlow(this.cx, this.cy, 50 * heat, '#FF4444', heat * 0.3);
            if (Math.random() < 0.05) this._spark(this.cx + (Math.random() - 0.5) * 50, this.cy + (Math.random() - 0.5) * 50, '#FF6644', 2);
        }
    }

    _drawWFC() {
        const ctx = this.ctx;
        // Concentric tubes
        ctx.strokeStyle = '#00BBFF';
        ctx.lineWidth = 2;
        ctx.shadowColor = '#00BBFF';
        ctx.shadowBlur = 6;
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 35, 0, PI2); ctx.stroke();
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 25, 0, PI2); ctx.stroke();
        ctx.shadowBlur = 0;

        // Pulsing voltage
        const pulse = Math.abs(Math.sin(this.t * 12));
        if (pulse > 0.7) {
            ctx.strokeStyle = `rgba(0,187,255,${pulse})`;
            ctx.lineWidth = 1;
            for (let a = 0; a < 8; a++) {
                const angle = (a / 8) * PI2 + this.t;
                ctx.beginPath();
                ctx.moveTo(this.cx + 25 * Math.cos(angle), this.cy + 25 * Math.sin(angle));
                ctx.lineTo(this.cx + 35 * Math.cos(angle), this.cy + 35 * Math.sin(angle));
                ctx.stroke();
            }
        }

        // Gas bubbles rising
        for (let i = 0; i < 8; i++) {
            const bx = this.cx + (Math.random() - 0.5) * 20;
            const by = this.cy - ((this.t * 50 + i * 20) % 80);
            ctx.fillStyle = `rgba(200,240,255,${0.4 + Math.random() * 0.3})`;
            ctx.beginPath();
            ctx.arc(bx, by, 1.5 + Math.random(), 0, PI2);
            ctx.fill();
        }

        // H₂ + O₂ label
        ctx.fillStyle = '#00BBFF';
        ctx.font = '600 10px "SF Mono", monospace';
        ctx.textAlign = 'center';
        ctx.fillText('H₂ + O₂ ↑', this.cx, this.cy - 70);
    }

    _drawAcoustic() {
        const ctx = this.ctx;
        // Tuning forks
        for (let f = 0; f < 3; f++) {
            const fx = this.cx - 60 + f * 60;
            const freq = (f + 1) * 1; // harmonic
            ctx.strokeStyle = '#FFB800';
            ctx.lineWidth = 3;
            const osc = Math.sin(this.t * (6 + f * 6)) * 4;
            ctx.beginPath();
            ctx.moveTo(fx - osc, this.cy - 40);
            ctx.lineTo(fx - osc + osc * 0.5, this.cy + 10);
            ctx.moveTo(fx + osc, this.cy - 40);
            ctx.lineTo(fx + osc - osc * 0.5, this.cy + 10);
            ctx.moveTo(fx, this.cy + 10);
            ctx.lineTo(fx, this.cy + 40);
            ctx.stroke();

            // Sound waves
            for (let r = 0; r < 3; r++) {
                const rad = (r * 15 + this.t * 30 * freq) % 60;
                ctx.beginPath();
                ctx.arc(fx, this.cy - 10, rad, 0, PI2);
                ctx.strokeStyle = `rgba(255,184,0,${Math.max(0, 0.3 - rad / 200)})`;
                ctx.lineWidth = 1;
                ctx.stroke();
            }
        }

        // Constructive interference center burst
        const beat = Math.abs(Math.sin(this.t * 3) * Math.sin(this.t * 6) * Math.sin(this.t * 9));
        if (beat > 0.8) {
            this._drawGlow(this.cx, this.cy, 30, '#FFD700', beat * 0.4);
            this._spark(this.cx, this.cy, '#FFB800', 2);
        }
    }

    _drawVortexFlow() {
        const ctx = this.ctx;
        // Spiral arms
        const arms = this.exp.physics.spiralArms || 8;
        for (let a = 0; a < arms; a++) {
            const baseAngle = (a / arms) * PI2;
            ctx.beginPath();
            for (let r = 80; r > 5; r -= 2) {
                const angle = baseAngle + (80 - r) * 0.08 + this.t * 1.5;
                const x = this.cx + r * Math.cos(angle);
                const y = this.cy + r * Math.sin(angle);
                if (r === 80) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            }
            const alpha = 0.15 + 0.1 * Math.sin(this.t * 2 + a);
            ctx.strokeStyle = `rgba(0,255,170,${alpha})`;
            ctx.lineWidth = 1.5;
            ctx.stroke();
        }

        // Center implosion glow
        const pulse = 0.3 + 0.2 * Math.sin(this.t * 4);
        this._drawGlow(this.cx, this.cy, 20, '#00FFAA', pulse);
        this._drawGlow(this.cx, this.cy, 8, '#FFFFFF', pulse * 0.5);

        // Inward-moving particles
        for (const p of this.particles) {
            const dx = this.cx - p.x, dy = this.cy - p.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist > 5) {
                p.x += dx / dist * p.speed;
                p.y += dy / dist * p.speed;
                // Add spiral
                p.x += -dy / dist * p.speed * 0.5;
                p.y += dx / dist * p.speed * 0.5;
            } else {
                p.x = this.cx + (Math.random() - 0.5) * this.w * 0.8;
                p.y = this.cy + (Math.random() - 0.5) * this.h * 0.8;
                this._spark(this.cx, this.cy, '#00FFAA', 1);
            }
            ctx.fillStyle = `rgba(0,255,170,${0.5})`;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, PI2);
            ctx.fill();
        }
    }

    _drawCSE() {
        const ctx = this.ctx;
        // Cavity grid
        const cols = 8, rows = 5;
        const cellW = 20, cellH = 15;
        const ox = this.cx - (cols * cellW) / 2;
        const oy = this.cy - (rows * cellH) / 2;

        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const cx2 = ox + c * cellW + cellW / 2;
                const cy2 = oy + r * cellH + cellH / 2;
                const phase = Math.sin(this.t * 3 + c * 0.5 + r * 0.7);
                ctx.strokeStyle = `rgba(136,221,255,${0.2 + phase * 0.15})`;
                ctx.lineWidth = 0.8;
                ctx.strokeRect(cx2 - cellW / 2 + 2, cy2 - cellH / 2 + 2, cellW - 4, cellH - 4);
            }
        }

        // Upward force arrows
        const lift = (Math.sin(this.t * 2) + 1) / 2;
        ctx.strokeStyle = `rgba(136,221,255,${lift * 0.4})`;
        ctx.lineWidth = 1.5;
        for (let i = 0; i < 5; i++) {
            const ax = this.cx - 40 + i * 20;
            const ay = oy - 10 - lift * 15;
            ctx.beginPath();
            ctx.moveTo(ax, ay + 20);
            ctx.lineTo(ax, ay);
            ctx.lineTo(ax - 4, ay + 6);
            ctx.moveTo(ax, ay);
            ctx.lineTo(ax + 4, ay + 6);
            ctx.stroke();
        }

        // Casimir glow between cavities  
        if (Math.random() < 0.05) {
            const sx = ox + Math.random() * cols * cellW;
            const sy = oy + Math.random() * rows * cellH;
            this._spark(sx, sy, '#88DDFF', 1);
        }
    }

    _drawGravityShield() {
        const ctx = this.ctx;
        // Spinning disk
        ctx.save();
        ctx.translate(this.cx, this.cy);
        ctx.rotate(this.t * 8);
        ctx.strokeStyle = '#b388ff';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.ellipse(0, 0, 50, 15, 0, 0, PI2);
        ctx.stroke();
        ctx.shadowColor = '#b388ff';
        ctx.shadowBlur = 12;
        ctx.stroke();
        ctx.shadowBlur = 0;
        ctx.restore();

        // Coils below
        ctx.strokeStyle = 'rgba(255,100,100,0.4)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.ellipse(this.cx, this.cy + 35, 40, 8, 0, 0, PI2);
        ctx.stroke();

        // Weight reduction zone above
        const shield = 0.3 + 0.15 * Math.sin(this.t * 3);
        ctx.fillStyle = `rgba(179,136,255,${shield * 0.1})`;
        ctx.fillRect(this.cx - 60, 10, 120, this.cy - 30);

        // Upward arrows (reduced gravity)
        ctx.strokeStyle = `rgba(179,136,255,${shield})`;
        ctx.lineWidth = 1;
        for (let i = 0; i < 4; i++) {
            const ax = this.cx - 30 + i * 20;
            const ay = this.cy - 50 - Math.sin(this.t * 2 + i) * 10;
            ctx.beginPath();
            ctx.moveTo(ax, ay + 15); ctx.lineTo(ax, ay);
            ctx.lineTo(ax - 3, ay + 5); ctx.moveTo(ax, ay); ctx.lineTo(ax + 3, ay + 5);
            ctx.stroke();
        }
    }

    _drawLifter() {
        const ctx = this.ctx;
        // Triangular lifter frame
        ctx.strokeStyle = '#DDBB00';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(this.cx, this.cy - 40);
        ctx.lineTo(this.cx - 50, this.cy + 30);
        ctx.lineTo(this.cx + 50, this.cy + 30);
        ctx.closePath();
        ctx.stroke();

        // HV corona wire (top)
        ctx.strokeStyle = '#FFEE00';
        ctx.lineWidth = 0.5;
        ctx.beginPath();
        ctx.moveTo(this.cx - 30, this.cy - 30);
        ctx.lineTo(this.cx + 30, this.cy - 30);
        ctx.stroke();

        // Ion wind downward
        for (let i = 0; i < 10; i++) {
            const ix = this.cx - 25 + Math.random() * 50;
            const iy = this.cy - 25 + ((this.t * 60 + i * 15) % 60);
            ctx.fillStyle = `rgba(221,187,0,${0.3 - (iy - this.cy + 25) / 200})`;
            ctx.beginPath();
            ctx.arc(ix, iy, 1, 0, PI2);
            ctx.fill();
        }

        // Upward motion
        const lift = Math.sin(this.t * 1.5) * 5;
        this._drawGlow(this.cx, this.cy - 40 + lift, 15, '#DDBB00', 0.3);
    }

    _drawMagneticMotor() {
        const ctx = this.ctx;
        // Rotor
        ctx.save();
        ctx.translate(this.cx, this.cy);
        ctx.rotate(this.t * 3);
        const magnets = this.exp.physics.magnets || 16;
        for (let i = 0; i < magnets; i++) {
            const angle = (i / magnets) * PI2;
            const mx = 35 * Math.cos(angle);
            const my = 35 * Math.sin(angle);
            ctx.fillStyle = i % 2 === 0 ? '#FF4466' : '#4466FF';
            ctx.beginPath();
            ctx.arc(mx, my, 4, 0, PI2);
            ctx.fill();
        }
        ctx.restore();

        // Stator ring
        ctx.strokeStyle = 'rgba(255,136,204,0.3)';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.arc(this.cx, this.cy, 50, 0, PI2);
        ctx.stroke();

        // Repulsion lines
        const repAngle = this.t * 3;
        ctx.strokeStyle = 'rgba(255,136,204,0.2)';
        ctx.lineWidth = 1;
        for (let i = 0; i < 6; i++) {
            const a = repAngle + (i / 6) * PI2;
            ctx.beginPath();
            ctx.moveTo(this.cx + 38 * Math.cos(a), this.cy + 38 * Math.sin(a));
            ctx.lineTo(this.cx + 48 * Math.cos(a), this.cy + 48 * Math.sin(a));
            ctx.stroke();
        }
    }

    _drawSchumann() {
        const ctx = this.ctx;
        // Earth circle
        ctx.fillStyle = '#1a3366';
        ctx.beginPath();
        ctx.arc(this.cx, this.cy + 20, 40, 0, PI2);
        ctx.fill();
        ctx.strokeStyle = '#00DDFF';
        ctx.lineWidth = 1;
        ctx.stroke();

        // Ionosphere ring
        ctx.strokeStyle = `rgba(0,221,255,${0.3 + 0.15 * Math.sin(this.t * 2)})`;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.arc(this.cx, this.cy + 20, 70, 0, PI2);
        ctx.stroke();

        // Standing waves
        for (let h = 0; h < 4; h++) {
            const amp = 8 * Math.sin(this.t * (3 + h * 2));
            ctx.beginPath();
            for (let a = 0; a < PI2; a += 0.05) {
                const wave = amp * Math.sin(a * (h + 1));
                const r = 55 + wave;
                const x = this.cx + r * Math.cos(a);
                const y = this.cy + 20 + r * Math.sin(a);
                if (a === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            }
            ctx.closePath();
            ctx.strokeStyle = `rgba(0,221,255,${0.15 - h * 0.03})`;
            ctx.lineWidth = 1;
            ctx.stroke();
        }

        // 7.83 Hz label
        ctx.fillStyle = '#00DDFF';
        ctx.font = '600 10px "SF Mono", monospace';
        ctx.textAlign = 'center';
        ctx.fillText('7.83 Hz', this.cx, this.cy - 55);
    }

    _drawInduction() {
        const ctx = this.ctx;
        // Coil
        for (let i = 0; i < 8; i++) {
            const y = this.cy - 30 + i * 8;
            ctx.strokeStyle = '#FFD700';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.ellipse(this.cx, y, 25, 6, 0, 0, PI2);
            ctx.stroke();
        }

        // Moving magnet
        const magY = this.cy + 50 * Math.sin(this.t * 3);
        ctx.fillStyle = '#FF4444';
        ctx.fillRect(this.cx - 8, magY - 15, 16, 15);
        ctx.fillStyle = '#4444FF';
        ctx.fillRect(this.cx - 8, magY, 16, 15);
        ctx.fillStyle = '#fff';
        ctx.font = '700 9px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('N', this.cx, magY - 4);
        ctx.fillText('S', this.cx, magY + 11);

        // EMF glow
        const emf = Math.abs(Math.cos(this.t * 3)) * 3;
        this._drawGlow(this.cx + 60, this.cy, 10 + emf * 3, '#FFD700', emf * 0.15);
    }

    _drawOscillator() {
        const ctx = this.ctx;
        // Dead battery
        ctx.fillStyle = '#444';
        ctx.fillRect(this.cx - 70, this.cy - 8, 20, 16);
        ctx.fillStyle = '#888';
        ctx.font = '600 7px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('0.8V', this.cx - 60, this.cy + 25);

        // Coil
        ctx.strokeStyle = '#88FF00';
        ctx.lineWidth = 2;
        for (let i = 0; i < 5; i++) {
            ctx.beginPath();
            ctx.ellipse(this.cx, this.cy - 10 + i * 8, 15, 4, 0, 0, PI2);
            ctx.stroke();
        }

        // LED glowing
        const pulse = (Math.sin(this.t * 15) + 1) / 2;
        this._drawGlow(this.cx + 60, this.cy, 15, '#88FF00', 0.3 + pulse * 0.4);
        ctx.fillStyle = '#88FF00';
        ctx.beginPath();
        ctx.arc(this.cx + 60, this.cy, 5, 0, PI2);
        ctx.fill();
    }

    _drawScalar() {
        const ctx = this.ctx;
        // TX coil
        this._drawGlow(this.cx - 60, this.cy, 25, '#00CCFF', 0.3);
        ctx.strokeStyle = '#00CCFF';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(this.cx - 60, this.cy, 20, 0, PI2);
        ctx.stroke();

        // RX coil
        this._drawGlow(this.cx + 60, this.cy, 25, '#00CCFF', 0.3);
        ctx.strokeStyle = '#00CCFF';
        ctx.beginPath();
        ctx.arc(this.cx + 60, this.cy, 20, 0, PI2);
        ctx.stroke();

        // Scalar wave between
        ctx.beginPath();
        for (let x = -50; x < 50; x += 2) {
            const wave = Math.sin(x * 0.15 + this.t * 8) * 15;
            if (x === -50) ctx.moveTo(this.cx + x, this.cy + wave);
            else ctx.lineTo(this.cx + x, this.cy + wave);
        }
        ctx.strokeStyle = `rgba(51,204,255,${0.4 + 0.2 * Math.sin(this.t * 3)})`;
        ctx.lineWidth = 1.5;
        ctx.stroke();
    }

    _drawVortex() {
        const ctx = this.ctx;
        // 3-6-9 pattern
        const radius = 50;
        ctx.strokeStyle = '#AA66FF';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(this.cx, this.cy, radius, 0, PI2);
        ctx.stroke();

        // Pattern numbers
        const nums = [1,2,4,8,7,5];
        const special = [3,6,9];
        for (let i = 0; i < 9; i++) {
            const angle = (i / 9) * PI2 - Math.PI / 2 + this.t * 0.5;
            const x = this.cx + radius * Math.cos(angle);
            const y = this.cy + radius * Math.sin(angle);
            const isSpecial = special.includes(i + 1);
            this._drawGlow(x, y, isSpecial ? 10 : 6, isSpecial ? '#FFD700' : '#AA66FF', 0.4);
            ctx.fillStyle = isSpecial ? '#FFD700' : '#e8e8f0';
            ctx.font = '700 10px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(String(i + 1), x, y);
        }

        // Connecting lines for doubling
        ctx.strokeStyle = 'rgba(170,102,255,0.2)';
        ctx.lineWidth = 1;
        for (let i = 0; i < nums.length; i++) {
            const a1 = ((nums[i] - 1) / 9) * PI2 - Math.PI / 2 + this.t * 0.5;
            const a2 = ((nums[(i + 1) % nums.length] - 1) / 9) * PI2 - Math.PI / 2 + this.t * 0.5;
            ctx.beginPath();
            ctx.moveTo(this.cx + radius * Math.cos(a1), this.cy + radius * Math.sin(a1));
            ctx.lineTo(this.cx + radius * Math.cos(a2), this.cy + radius * Math.sin(a2));
            ctx.stroke();
        }
    }

    // ═══ BATCH 1: Tesla variants, Plasma, Fusion ═══

    _drawTower() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Wardenclyffe tower structure
        const bw = 30, bh = 50;
        ctx.strokeStyle = color + '60'; ctx.lineWidth = 2;
        ctx.strokeRect(this.cx - bw/2, this.cy + 10, bw, bh);
        // Cross beams
        for (let i = 0; i < 4; i++) {
            const y = this.cy + 15 + i * 12;
            ctx.beginPath(); ctx.moveTo(this.cx - bw/2, y); ctx.lineTo(this.cx + bw/2, y); ctx.stroke();
        }
        // Dome on top
        ctx.beginPath(); ctx.arc(this.cx, this.cy + 5, 25, Math.PI, 0); ctx.strokeStyle = color + '80'; ctx.stroke();
        // Energy discharge from dome
        for (let i = 0; i < 6; i++) {
            const a = Math.PI + (i / 5) * Math.PI + Math.sin(this.t * 3 + i) * 0.2;
            const len = 20 + Math.random() * 25;
            ctx.beginPath(); ctx.moveTo(this.cx + 25 * Math.cos(a), this.cy + 5 + 25 * Math.sin(a));
            ctx.lineTo(this.cx + (25 + len) * Math.cos(a), this.cy + 5 + (25 + len) * Math.sin(a));
            ctx.strokeStyle = color; ctx.globalAlpha = 0.3 + Math.random() * 0.4; ctx.lineWidth = 1; ctx.stroke();
        }
        ctx.globalAlpha = 1;
        this._drawGlow(this.cx, this.cy + 5, 20, color, 0.2 + 0.15 * Math.sin(this.t * 4));
        if (Math.random() < 0.15) this._spark(this.cx + (Math.random()-0.5)*50, this.cy - 20, color, 4);
    }

    _drawTurbine() {
        const ctx = this.ctx;
        const color = this.exp.color;
        const disks = this.exp.physics.disks || 12;
        // Stacked spinning disks
        for (let i = 0; i < Math.min(disks, 8); i++) {
            const y = this.cy - 30 + i * 8;
            const angle = this.t * (8 + i * 0.5);
            ctx.beginPath(); ctx.ellipse(this.cx, y, 40, 4, 0, 0, PI2);
            ctx.strokeStyle = color + '40'; ctx.lineWidth = 1; ctx.stroke();
            // Rotation marker
            const mx = this.cx + 38 * Math.cos(angle);
            const my = y + 3 * Math.sin(angle);
            ctx.fillStyle = color; ctx.globalAlpha = 0.7;
            ctx.beginPath(); ctx.arc(mx, my, 2, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Central shaft
        ctx.strokeStyle = color + '60'; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy - 35); ctx.lineTo(this.cx, this.cy + 35); ctx.stroke();
        this._drawGlow(this.cx, this.cy, 15, color, 0.15);
    }

    _drawRadiant() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Central collector
        this._drawGlow(this.cx, this.cy, 20, color, 0.3);
        // Antenna rods radiating outward
        const tubes = this.exp.physics.tubes || 12;
        for (let i = 0; i < tubes; i++) {
            const a = (i / tubes) * PI2 + this.t * 0.3;
            const len = 35 + 15 * Math.sin(this.t * 4 + i * 0.7);
            ctx.beginPath(); ctx.moveTo(this.cx, this.cy);
            ctx.lineTo(this.cx + len * Math.cos(a), this.cy + len * Math.sin(a));
            ctx.strokeStyle = color + '50'; ctx.lineWidth = 1.5; ctx.stroke();
            // Energy pulse traveling along rod
            const pd = (this.t * 40 + i * 5) % len;
            const px = this.cx + pd * Math.cos(a), py = this.cy + pd * Math.sin(a);
            ctx.fillStyle = color; ctx.globalAlpha = 0.6;
            ctx.beginPath(); ctx.arc(px, py, 2, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        if (Math.random() < 0.1) this._spark(this.cx, this.cy, color, 3);
    }

    _drawElectrostatic() {
        const ctx = this.ctx;
        const color = this.exp.color;
        const sectors = this.exp.physics.sectors || 36;
        // Two counter-rotating disks
        for (let d = -1; d <= 1; d += 2) {
            const rot = this.t * 3 * d;
            for (let i = 0; i < Math.min(sectors, 18); i++) {
                const a = (i / 18) * PI2 + rot;
                const r1 = 20, r2 = 45;
                ctx.beginPath();
                ctx.moveTo(this.cx + r1 * Math.cos(a), this.cy + d * 5 + r1 * Math.sin(a));
                ctx.lineTo(this.cx + r2 * Math.cos(a), this.cy + d * 5 + r2 * Math.sin(a));
                ctx.strokeStyle = d > 0 ? color + '30' : '#FF6B9A30'; ctx.lineWidth = 2; ctx.stroke();
            }
        }
        // Spark gap between plates
        if (Math.sin(this.t * 8) > 0.7) {
            ctx.beginPath(); ctx.moveTo(this.cx - 5, this.cy - 5); ctx.lineTo(this.cx + 5, this.cy + 5);
            ctx.strokeStyle = '#FFFFFF'; ctx.lineWidth = 2; ctx.stroke();
            this._spark(this.cx, this.cy, '#FFFFFF', 3);
        }
    }

    _drawToroidal() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Torus shape
        const R = 35, r = 12;
        for (let a = 0; a < PI2; a += 0.15) {
            const x = this.cx + R * Math.cos(a);
            const y = this.cy + R * Math.sin(a) * 0.4;
            const osc = Math.sin(a * 3 + this.t * 5) * 0.3 + 0.5;
            ctx.fillStyle = color; ctx.globalAlpha = osc * 0.4;
            ctx.beginPath(); ctx.arc(x, y, r * 0.3, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Energy flowing through torus
        for (let i = 0; i < 4; i++) {
            const a = (i / 4) * PI2 + this.t * 3;
            const x = this.cx + R * Math.cos(a);
            const y = this.cy + R * Math.sin(a) * 0.4;
            ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.8;
            ctx.beginPath(); ctx.arc(x, y, 3, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        this._drawGlow(this.cx, this.cy, 15, color, 0.15);
    }

    _drawHomopolar() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Spinning disk (Faraday disk)
        const rpm = this.exp.physics.rpm || 3000;
        const rot = this.t * (rpm / 500);
        ctx.strokeStyle = color + '50'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 45, 0, PI2); ctx.stroke();
        // Radial lines on disk
        for (let i = 0; i < 8; i++) {
            const a = (i / 8) * PI2 + rot;
            ctx.beginPath();
            ctx.moveTo(this.cx + 10 * Math.cos(a), this.cy + 10 * Math.sin(a));
            ctx.lineTo(this.cx + 45 * Math.cos(a), this.cy + 45 * Math.sin(a));
            ctx.strokeStyle = color + '25'; ctx.stroke();
        }
        // Central magnet
        this._drawGlow(this.cx, this.cy, 10, '#FF6B9A', 0.3);
        // Brush contacts
        ctx.fillStyle = '#AAAAAA'; ctx.fillRect(this.cx + 42, this.cy - 3, 10, 6);
        ctx.fillRect(this.cx - 5, this.cy - 3, 10, 6);
        // Current flow
        ctx.strokeStyle = color; ctx.lineWidth = 1.5; ctx.globalAlpha = 0.5;
        const ca = rot % PI2;
        ctx.beginPath();
        ctx.moveTo(this.cx + 47, this.cy);
        ctx.lineTo(this.cx + 45 * Math.cos(ca), this.cy + 45 * Math.sin(ca));
        ctx.lineTo(this.cx, this.cy); ctx.stroke();
        ctx.globalAlpha = 1;
    }

    _drawPlasmaDischarge() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Two electrodes
        ctx.fillStyle = '#888'; ctx.fillRect(this.cx - 50, this.cy - 3, 15, 6);
        ctx.fillRect(this.cx + 35, this.cy - 3, 15, 6);
        // Plasma glow between electrodes
        this._drawGlow(this.cx, this.cy, 25, color, 0.25 + 0.15 * Math.sin(this.t * 6));
        // Discharge streamers
        for (let i = 0; i < 5; i++) {
            ctx.beginPath(); ctx.moveTo(this.cx - 35, this.cy);
            let px = this.cx - 35;
            for (let s = 0; s < 8; s++) {
                px += 9;
                const py = this.cy + (Math.random() - 0.5) * 20 * Math.sin(this.t * 10 + i);
                ctx.lineTo(px, py);
            }
            ctx.strokeStyle = color; ctx.globalAlpha = 0.2 + Math.random() * 0.3;
            ctx.lineWidth = 1 + Math.random(); ctx.stroke();
        }
        ctx.globalAlpha = 1;
        if (Math.random() < 0.18) this._spark(this.cx, this.cy, color, 4);
    }

    _drawFusor() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Outer sphere (vacuum chamber)
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 50, 0, PI2);
        ctx.strokeStyle = color + '30'; ctx.lineWidth = 2; ctx.stroke();
        // Inner grid
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 18, 0, PI2);
        ctx.strokeStyle = color + '60'; ctx.lineWidth = 1.5; ctx.stroke();
        // Grid wires
        for (let i = 0; i < 4; i++) {
            const a = (i / 4) * Math.PI;
            ctx.beginPath();
            ctx.moveTo(this.cx + 18 * Math.cos(a), this.cy + 18 * Math.sin(a));
            ctx.lineTo(this.cx + 18 * Math.cos(a + Math.PI), this.cy + 18 * Math.sin(a + Math.PI));
            ctx.stroke();
        }
        // Plasma glow at center (star mode)
        this._drawGlow(this.cx, this.cy, 12, '#FFFFFF', 0.4 + 0.2 * Math.sin(this.t * 5));
        this._drawGlow(this.cx, this.cy, 8, color, 0.5);
        // Ion paths converging
        for (let i = 0; i < 8; i++) {
            const a = (i / 8) * PI2 + this.t * 0.5;
            const r = 48 - ((this.t * 30 + i * 6) % 30);
            if (r > 18) {
                const px = this.cx + r * Math.cos(a), py = this.cy + r * Math.sin(a);
                ctx.fillStyle = color; ctx.globalAlpha = 0.5;
                ctx.beginPath(); ctx.arc(px, py, 1.5, 0, PI2); ctx.fill();
            }
        }
        ctx.globalAlpha = 1;
    }

    _drawTokamak() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Torus cross-section (donut)
        const R = 40, r = 18;
        // Draw torus outline
        ctx.beginPath(); ctx.ellipse(this.cx, this.cy, R + r, (R + r) * 0.35, 0, 0, PI2);
        ctx.strokeStyle = color + '25'; ctx.lineWidth = 1; ctx.stroke();
        ctx.beginPath(); ctx.ellipse(this.cx, this.cy, R - r, Math.max(1, (R - r) * 0.35), 0, 0, PI2);
        ctx.strokeStyle = color + '25'; ctx.stroke();
        // Hot plasma ring
        for (let a = 0; a < PI2; a += 0.08) {
            const x = this.cx + R * Math.cos(a);
            const y = this.cy + R * Math.sin(a) * 0.35;
            const flicker = 0.2 + 0.3 * Math.sin(a * 5 + this.t * 8);
            ctx.fillStyle = color; ctx.globalAlpha = flicker;
            ctx.beginPath(); ctx.arc(x, y, r * 0.25 + Math.random() * 2, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Magnetic field coils
        for (let i = 0; i < 8; i++) {
            const a = (i / 8) * PI2;
            const x = this.cx + (R + r + 5) * Math.cos(a);
            const y = this.cy + (R + r + 5) * Math.sin(a) * 0.35;
            ctx.strokeStyle = '#4488FF40'; ctx.lineWidth = 3;
            ctx.beginPath(); ctx.arc(x, y, 4, 0, PI2); ctx.stroke();
        }
        this._drawGlow(this.cx, this.cy, 15, color, 0.15);
    }

    // ═══ BATCH 2: Orgone, MWO, Warp, EmDrive, Bessler, HHO, Superconductor, Cloudbuster ═══

    _drawOrgone() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Layered box (alternating organic/metallic)
        const layers = this.exp.physics.layers || 6;
        for (let i = 0; i < layers; i++) {
            const s = 50 - i * 6;
            ctx.strokeStyle = i % 2 === 0 ? color + '30' : '#88888830';
            ctx.lineWidth = 3; ctx.strokeRect(this.cx - s, this.cy - s * 0.6, s * 2, s * 1.2);
        }
        // Pulsing energy inside
        const pulseR = 15 + 8 * Math.sin(this.t * 2);
        this._drawGlow(this.cx, this.cy, pulseR, color, 0.25);
        // Orgone energy waves expanding
        for (let w = 0; w < 3; w++) {
            const wr = (w * 30 + this.t * 20) % 90;
            ctx.beginPath(); ctx.arc(this.cx, this.cy, wr, 0, PI2);
            ctx.strokeStyle = color; ctx.globalAlpha = Math.max(0, 0.3 - wr / 300);
            ctx.lineWidth = 1; ctx.stroke();
        }
        ctx.globalAlpha = 1;
    }

    _drawCloudbuster() {
        const ctx = this.ctx;
        const color = this.exp.color;
        const tubes = this.exp.physics.tubes || 6;
        // Tubes pointing upward
        for (let i = 0; i < tubes; i++) {
            const x = this.cx - 25 + i * (50 / (tubes - 1));
            ctx.strokeStyle = '#888'; ctx.lineWidth = 3;
            ctx.beginPath(); ctx.moveTo(x, this.cy + 35); ctx.lineTo(x, this.cy - 40); ctx.stroke();
            // Energy flowing up tubes
            const ey = this.cy + 30 - ((this.t * 40 + i * 10) % 70);
            ctx.fillStyle = color; ctx.globalAlpha = 0.6;
            ctx.beginPath(); ctx.arc(x, ey, 2, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Base (water bucket)
        ctx.fillStyle = '#334'; ctx.fillRect(this.cx - 30, this.cy + 35, 60, 15);
        // Sky energy
        for (let i = 0; i < 10; i++) {
            const sx = this.cx + (Math.random() - 0.5) * 80;
            const sy = this.cy - 45 - Math.random() * 15;
            ctx.fillStyle = color; ctx.globalAlpha = 0.15 + Math.random() * 0.2;
            ctx.beginPath(); ctx.arc(sx, sy, 1 + Math.random(), 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    _drawMWO() {
        const ctx = this.ctx;
        const color = this.exp.color;
        const rings = this.exp.physics.rings || 12;
        // Concentric split rings (Lakhovsky antenna)
        for (let i = 0; i < Math.min(rings, 10); i++) {
            const r = 10 + i * 5;
            const gap = (i * 0.7 + this.t * 2) % PI2;
            ctx.beginPath(); ctx.arc(this.cx, this.cy, r, gap + 0.15, gap + PI2 - 0.15);
            const hue = (i / 10) * 360;
            ctx.strokeStyle = `hsla(${hue}, 80%, 60%, 0.4)`; ctx.lineWidth = 1.5; ctx.stroke();
        }
        // Energy radiation between rings
        for (let i = 0; i < 6; i++) {
            const a = (i / 6) * PI2 + this.t * 1.5;
            const r = 15 + Math.random() * 35;
            ctx.fillStyle = color; ctx.globalAlpha = 0.3 + Math.random() * 0.3;
            ctx.beginPath(); ctx.arc(this.cx + r * Math.cos(a), this.cy + r * Math.sin(a), 1.5, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        this._drawGlow(this.cx, this.cy, 8, '#FFFFFF', 0.3);
    }

    _drawRife() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Beam ray tube
        ctx.strokeStyle = '#888'; ctx.lineWidth = 2;
        ctx.strokeRect(this.cx - 35, this.cy - 8, 70, 16);
        // Plasma inside tube
        for (let x = -30; x <= 30; x += 3) {
            const intensity = (Math.sin(x * 0.3 + this.t * 15) + 1) / 2;
            ctx.fillStyle = color; ctx.globalAlpha = intensity * 0.5;
            ctx.fillRect(this.cx + x - 1, this.cy - 5, 3, 10);
        }
        ctx.globalAlpha = 1;
        // Frequency emission
        for (let r = 0; r < 4; r++) {
            const rad = 10 + (r * 20 + this.t * 30) % 80;
            ctx.beginPath(); ctx.arc(this.cx, this.cy, rad, -0.4, 0.4);
            ctx.strokeStyle = color; ctx.globalAlpha = Math.max(0, 0.3 - rad / 300);
            ctx.lineWidth = 1.5; ctx.stroke();
            ctx.beginPath(); ctx.arc(this.cx, this.cy, rad, Math.PI - 0.4, Math.PI + 0.4);
            ctx.stroke();
        }
        ctx.globalAlpha = 1;
    }

    _drawWarp() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Alcubierre warp bubble
        ctx.beginPath(); ctx.ellipse(this.cx, this.cy, 50, 25, 0, 0, PI2);
        ctx.strokeStyle = color + '40'; ctx.lineWidth = 1.5; ctx.stroke();
        // Space compression at front, expansion at back
        for (let i = 0; i < 5; i++) {
            // Compressed lines (front)
            const fx = this.cx + 35 + i * 3;
            ctx.beginPath(); ctx.moveTo(fx, this.cy - 20); ctx.lineTo(fx, this.cy + 20);
            ctx.strokeStyle = '#FF6B9A30'; ctx.lineWidth = 1; ctx.stroke();
            // Expanded lines (back)
            const bx = this.cx - 35 - i * 8;
            ctx.beginPath(); ctx.moveTo(bx, this.cy - 20); ctx.lineTo(bx, this.cy + 20);
            ctx.strokeStyle = '#4488FF30'; ctx.stroke();
        }
        // Craft in center
        ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.8;
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 4, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
        // Starfield streaking past
        for (let i = 0; i < 12; i++) {
            const sx = this.cx - 70 + ((this.t * 60 + i * 12) % 140);
            const sy = this.cy + (i - 6) * 8;
            const len = 3 + Math.random() * 5;
            ctx.strokeStyle = '#FFFFFF30'; ctx.lineWidth = 0.5;
            ctx.beginPath(); ctx.moveTo(sx, sy); ctx.lineTo(sx - len, sy); ctx.stroke();
        }
    }

    _drawEmdrive() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Truncated cone (frustum)
        ctx.beginPath();
        ctx.moveTo(this.cx - 15, this.cy - 30); ctx.lineTo(this.cx + 15, this.cy - 30);
        ctx.lineTo(this.cx + 30, this.cy + 30); ctx.lineTo(this.cx - 30, this.cy + 30);
        ctx.closePath();
        ctx.strokeStyle = color + '50'; ctx.lineWidth = 2; ctx.stroke();
        // RF waves bouncing inside
        for (let i = 0; i < 4; i++) {
            const y = this.cy - 25 + i * 15;
            const w = 15 + (y - (this.cy - 30)) * 0.5;
            ctx.beginPath();
            for (let x = -w; x <= w; x += 2) {
                const wy = y + Math.sin(x * 0.5 + this.t * 10 + i * 2) * 4;
                if (x === -w) ctx.moveTo(this.cx + x, wy); else ctx.lineTo(this.cx + x, wy);
            }
            ctx.strokeStyle = color + '35'; ctx.lineWidth = 1; ctx.stroke();
        }
        // Thrust indicator (tiny arrow at top)
        const thrustPulse = Math.sin(this.t * 4) * 3;
        ctx.strokeStyle = '#4488FF'; ctx.lineWidth = 1.5; ctx.globalAlpha = 0.5;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy - 35); ctx.lineTo(this.cx, this.cy - 45 - thrustPulse);
        ctx.moveTo(this.cx - 4, this.cy - 41 - thrustPulse); ctx.lineTo(this.cx, this.cy - 45 - thrustPulse);
        ctx.lineTo(this.cx + 4, this.cy - 41 - thrustPulse); ctx.stroke();
        ctx.globalAlpha = 1;
    }

    _drawBessler() {
        const ctx = this.ctx;
        const color = this.exp.color;
        const masses = this.exp.physics.masses || 8;
        // Spinning wheel
        const rot = this.t * 1.5;
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 45, 0, PI2);
        ctx.strokeStyle = color + '40'; ctx.lineWidth = 2; ctx.stroke();
        // Spokes
        for (let i = 0; i < masses; i++) {
            const a = (i / masses) * PI2 + rot;
            ctx.beginPath(); ctx.moveTo(this.cx, this.cy);
            ctx.lineTo(this.cx + 45 * Math.cos(a), this.cy + 45 * Math.sin(a));
            ctx.strokeStyle = color + '25'; ctx.lineWidth = 1; ctx.stroke();
            // Weight at end
            const wx = this.cx + 40 * Math.cos(a), wy = this.cy + 40 * Math.sin(a);
            ctx.fillStyle = color; ctx.globalAlpha = 0.6;
            ctx.beginPath(); ctx.arc(wx, wy, 4, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Axle
        ctx.fillStyle = '#888'; ctx.beginPath(); ctx.arc(this.cx, this.cy, 5, 0, PI2); ctx.fill();
        // Stand
        ctx.strokeStyle = '#666'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy + 45); ctx.lineTo(this.cx, this.cy + 60); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(this.cx - 20, this.cy + 60); ctx.lineTo(this.cx + 20, this.cy + 60); ctx.stroke();
    }

    _drawHHO() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Electrolysis cell
        ctx.strokeStyle = '#666'; ctx.lineWidth = 1.5;
        ctx.strokeRect(this.cx - 25, this.cy - 20, 50, 45);
        // Water level
        ctx.fillStyle = '#224466'; ctx.globalAlpha = 0.3;
        ctx.fillRect(this.cx - 24, this.cy - 5, 48, 30);
        ctx.globalAlpha = 1;
        // Electrodes
        ctx.fillStyle = '#AAA'; ctx.fillRect(this.cx - 15, this.cy - 18, 3, 40);
        ctx.fillStyle = '#CC8833'; ctx.fillRect(this.cx + 12, this.cy - 18, 3, 40);
        // Bubbles rising
        for (let i = 0; i < 8; i++) {
            const bx = this.cx - 13 + (i > 3 ? 27 : 0) + Math.random() * 4;
            const by = this.cy + 20 - ((this.t * 25 + i * 7) % 30);
            ctx.strokeStyle = color + '60'; ctx.lineWidth = 0.8;
            ctx.beginPath(); ctx.arc(bx, by, 1.5 + Math.random(), 0, PI2); ctx.stroke();
        }
        // Gas collection at top
        this._drawGlow(this.cx, this.cy - 25, 8, color, 0.2 + 0.1 * Math.sin(this.t * 3));
    }

    _drawSuperconductor() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Superconducting disk
        ctx.beginPath(); ctx.ellipse(this.cx, this.cy + 10, 30, 8, 0, 0, PI2);
        ctx.fillStyle = '#223344'; ctx.globalAlpha = 0.5; ctx.fill();
        ctx.strokeStyle = color + '60'; ctx.lineWidth = 1.5; ctx.globalAlpha = 1; ctx.stroke();
        // Levitating magnet above
        const levY = this.cy - 15 + Math.sin(this.t * 2) * 3;
        ctx.fillStyle = '#FF4444'; ctx.fillRect(this.cx - 12, levY - 5, 12, 10);
        ctx.fillStyle = '#4444FF'; ctx.fillRect(this.cx, levY - 5, 12, 10);
        ctx.strokeStyle = '#888'; ctx.lineWidth = 0.5;
        ctx.strokeRect(this.cx - 12, levY - 5, 24, 10);
        // Meissner effect field lines
        for (let i = 0; i < 5; i++) {
            const fx = this.cx - 20 + i * 10;
            ctx.beginPath();
            ctx.moveTo(fx, levY + 15);
            ctx.quadraticCurveTo(fx + (i - 2) * 5, this.cy + 5, fx + (i - 2) * 12, this.cy + 25);
            ctx.strokeStyle = color + '25'; ctx.lineWidth = 1; ctx.stroke();
        }
        // Cold fog
        for (let i = 0; i < 6; i++) {
            const fx = this.cx - 25 + Math.random() * 50;
            const fy = this.cy + 15 + Math.random() * 10;
            ctx.fillStyle = '#AADDFF'; ctx.globalAlpha = 0.08 + Math.random() * 0.08;
            ctx.beginPath(); ctx.arc(fx, fy, 5 + Math.random() * 5, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    // ═══ BATCH 3: Quantum, Tunneling, Piezo, Stirling, Sono, Z-Pinch, Polywell, DPF ═══

    _drawTunneling() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Potential barrier
        ctx.fillStyle = '#FF6B9A15'; ctx.fillRect(this.cx - 8, this.cy - 40, 16, 80);
        ctx.strokeStyle = '#FF6B9A40'; ctx.lineWidth = 1;
        ctx.strokeRect(this.cx - 8, this.cy - 40, 16, 80);
        // Wave function approaching from left
        ctx.beginPath();
        for (let x = -65; x <= 65; x += 1) {
            const absX = x + this.cx;
            let amp;
            if (x < -8) amp = Math.sin(x * 0.3 + this.t * 5) * 18;
            else if (x <= 8) amp = Math.sin(x * 0.3 + this.t * 5) * 18 * Math.exp(-Math.abs(x) * 0.1);
            else amp = Math.sin(x * 0.3 + this.t * 5) * 18 * Math.exp(-8 * 0.1) * 0.4;
            if (x === -65) ctx.moveTo(absX, this.cy + amp); else ctx.lineTo(absX, this.cy + amp);
        }
        ctx.strokeStyle = color; ctx.globalAlpha = 0.6; ctx.lineWidth = 1.5; ctx.stroke();
        ctx.globalAlpha = 1;
        // Tunneled particle dot
        const tp = ((this.t * 20) % 130) - 65;
        ctx.fillStyle = color; ctx.globalAlpha = tp > 8 ? 0.3 : 0.7;
        ctx.beginPath(); ctx.arc(this.cx + tp, this.cy, 3, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
    }

    _drawPiezo() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Crystal block
        const squeeze = Math.sin(this.t * 8) * 5;
        const w = 30 + squeeze, h = 40 - squeeze;
        ctx.fillStyle = '#334455'; ctx.globalAlpha = 0.3;
        ctx.fillRect(this.cx - w / 2, this.cy - h / 2, w, h);
        ctx.globalAlpha = 1; ctx.strokeStyle = color + '60'; ctx.lineWidth = 1.5;
        ctx.strokeRect(this.cx - w / 2, this.cy - h / 2, w, h);
        // Pressure arrows
        ctx.strokeStyle = '#FF6B9A50'; ctx.lineWidth = 2;
        const arrowY = 5 + squeeze;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy - h / 2 - 15); ctx.lineTo(this.cx, this.cy - h / 2 - arrowY); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy + h / 2 + 15); ctx.lineTo(this.cx, this.cy + h / 2 + arrowY); ctx.stroke();
        // Voltage output
        const voltage = Math.abs(Math.sin(this.t * 8)) * 0.5;
        ctx.fillStyle = color; ctx.globalAlpha = voltage;
        this._drawGlow(this.cx, this.cy, 12, color, voltage * 0.3);
        ctx.globalAlpha = 1;
        // Electrodes
        ctx.fillStyle = '#AAA';
        ctx.fillRect(this.cx - w / 2 - 5, this.cy - 3, 5, 6);
        ctx.fillRect(this.cx + w / 2, this.cy - 3, 5, 6);
    }

    _drawStirling() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Cylinder
        ctx.strokeStyle = '#888'; ctx.lineWidth = 1.5;
        ctx.strokeRect(this.cx - 15, this.cy - 35, 30, 70);
        // Piston
        const pistonY = this.cy + Math.sin(this.t * 4) * 20;
        ctx.fillStyle = '#AAA'; ctx.globalAlpha = 0.5;
        ctx.fillRect(this.cx - 13, pistonY - 3, 26, 6);
        ctx.globalAlpha = 1;
        // Piston rod
        ctx.strokeStyle = '#888'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(this.cx, pistonY); ctx.lineTo(this.cx, this.cy + 40); ctx.stroke();
        // Hot side (bottom) - red glow
        this._drawGlow(this.cx, this.cy + 35, 15, '#FF4444', 0.2 + 0.1 * Math.sin(this.t * 2));
        // Cold side (top) - blue glow
        this._drawGlow(this.cx, this.cy - 35, 15, '#4488FF', 0.15);
        // Flywheel
        const fwAngle = this.t * 4;
        ctx.beginPath(); ctx.arc(this.cx + 30, this.cy + 40, 12, 0, PI2);
        ctx.strokeStyle = color + '50'; ctx.lineWidth = 1.5; ctx.stroke();
        ctx.fillStyle = color; ctx.beginPath();
        ctx.arc(this.cx + 30 + 10 * Math.cos(fwAngle), this.cy + 40 + 10 * Math.sin(fwAngle), 2, 0, PI2); ctx.fill();
    }

    _drawSono() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Water container
        ctx.strokeStyle = '#446688'; ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 45, 0, PI2); ctx.stroke();
        // Sound waves converging
        for (let r = 0; r < 4; r++) {
            const rad = 45 - ((this.t * 30 + r * 12) % 40);
            if (rad > 5) {
                ctx.beginPath(); ctx.arc(this.cx, this.cy, rad, 0, PI2);
                ctx.strokeStyle = color + '20'; ctx.lineWidth = 1; ctx.stroke();
            }
        }
        // Central bubble
        const bR = 4 + 2 * Math.sin(this.t * 12);
        ctx.beginPath(); ctx.arc(this.cx, this.cy, bR, 0, PI2);
        ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.4; ctx.fill(); ctx.globalAlpha = 1;
        // Flash on collapse
        if (Math.sin(this.t * 12) < -0.9) {
            this._drawGlow(this.cx, this.cy, 20, '#FFFFFF', 0.6);
            this._drawGlow(this.cx, this.cy, 10, color, 0.4);
        }
    }

    _drawZPinch() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Vertical plasma column
        ctx.strokeStyle = color + '30'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy - 50); ctx.lineTo(this.cx, this.cy + 50); ctx.stroke();
        // Pinch point in center
        const pinchR = 3 + 2 * Math.sin(this.t * 8);
        for (let y = -45; y <= 45; y += 3) {
            const dist = Math.abs(y);
            const r = dist < 15 ? pinchR + (dist / 15) * 12 : 15;
            ctx.fillStyle = color; ctx.globalAlpha = 0.15 + (1 - dist / 45) * 0.2;
            ctx.beginPath(); ctx.arc(this.cx, this.cy + y, r * 0.3, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Magnetic field rings
        for (let i = 0; i < 4; i++) {
            const ry = this.cy - 20 + i * 13;
            ctx.beginPath(); ctx.ellipse(this.cx, ry, 20, 5, 0, 0, PI2);
            ctx.strokeStyle = '#4488FF25'; ctx.lineWidth = 1; ctx.stroke();
        }
        // Pinch glow
        this._drawGlow(this.cx, this.cy, 8, '#FFFFFF', 0.3 + 0.2 * Math.sin(this.t * 8));
    }

    _drawPolywell() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Cube of magnetic coils (projected)
        const s = 35;
        const corners = [[-1,-1],[-1,1],[1,1],[1,-1]];
        // Front face
        ctx.strokeStyle = '#4488FF30'; ctx.lineWidth = 2;
        ctx.beginPath();
        for (let i = 0; i <= 4; i++) {
            const c = corners[i % 4];
            const x = this.cx + c[0] * s * 0.5, y = this.cy + c[1] * s * 0.5;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        }
        ctx.stroke();
        // Back face (offset)
        ctx.beginPath();
        for (let i = 0; i <= 4; i++) {
            const c = corners[i % 4];
            const x = this.cx + c[0] * s * 0.35 + 8, y = this.cy + c[1] * s * 0.35 - 8;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        }
        ctx.stroke();
        // Coil indicators on faces
        for (let i = 0; i < 6; i++) {
            const a = (i / 6) * PI2 + this.t * 2;
            const r = s * 0.3;
            ctx.beginPath(); ctx.arc(this.cx + r * Math.cos(a) * 0.5, this.cy + r * Math.sin(a) * 0.5, 4, 0, PI2);
            ctx.strokeStyle = '#4488FF50'; ctx.lineWidth = 1.5; ctx.stroke();
        }
        // Central plasma
        this._drawGlow(this.cx, this.cy, 10, color, 0.3 + 0.15 * Math.sin(this.t * 6));
        this._drawGlow(this.cx, this.cy, 5, '#FFFFFF', 0.4);
    }

    _drawDPF() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Anode/cathode
        ctx.fillStyle = '#888';
        ctx.fillRect(this.cx - 3, this.cy - 45, 6, 30); // inner electrode
        ctx.strokeStyle = '#666'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.arc(this.cx, this.cy - 15, 20, 0, PI2); ctx.stroke(); // outer electrode
        // Plasma sheath
        const sheath = (this.t * 2) % PI2;
        for (let a = 0; a < PI2; a += 0.2) {
            const r = 18 - ((a + sheath) % PI2) * 2;
            if (r > 0) {
                ctx.fillStyle = color; ctx.globalAlpha = 0.3;
                ctx.beginPath(); ctx.arc(this.cx + r * Math.cos(a), this.cy - 15 + r * Math.sin(a), 1.5, 0, PI2); ctx.fill();
            }
        }
        ctx.globalAlpha = 1;
        // Plasmoid at tip
        this._drawGlow(this.cx, this.cy - 45, 8, '#FFFFFF', 0.3 + 0.2 * Math.sin(this.t * 10));
        this._drawGlow(this.cx, this.cy - 45, 5, color, 0.4);
        if (Math.random() < 0.12) this._spark(this.cx, this.cy - 45, color, 3);
    }

    _drawHydrino() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Reaction cell (SunCell)
        ctx.strokeStyle = '#888'; ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 35, 0, PI2); ctx.stroke();
        // Electrodes
        ctx.fillStyle = '#AAA';
        ctx.fillRect(this.cx - 35, this.cy - 3, 10, 6);
        ctx.fillRect(this.cx + 25, this.cy - 3, 10, 6);
        // Brilliant plasma
        this._drawGlow(this.cx, this.cy, 20, '#FFFFFF', 0.5 + 0.2 * Math.sin(this.t * 6));
        this._drawGlow(this.cx, this.cy, 12, color, 0.4);
        // Fractional hydrogen orbit shrinking
        for (let n = 0; n < 3; n++) {
            const r = 25 - n * 7;
            const a = this.t * (3 + n * 2);
            ctx.beginPath(); ctx.arc(this.cx, this.cy, r, 0, PI2);
            ctx.strokeStyle = color + '20'; ctx.lineWidth = 0.5; ctx.stroke();
            ctx.fillStyle = color; ctx.globalAlpha = 0.6;
            ctx.beginPath(); ctx.arc(this.cx + r * Math.cos(a), this.cy + r * Math.sin(a), 2, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    // ═══ BATCH 4: Atmospheric, Schappeller, Heim, Witricity, Hawking, Schwinger, Quantum-Bio, SAFIRE ═══

    _drawAtmospheric() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Tall antenna/rod
        ctx.strokeStyle = '#888'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy + 40); ctx.lineTo(this.cx, this.cy - 45); ctx.stroke();
        // Collector at top
        ctx.strokeStyle = color + '60'; ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.moveTo(this.cx - 20, this.cy - 40); ctx.lineTo(this.cx, this.cy - 48);
        ctx.lineTo(this.cx + 20, this.cy - 40); ctx.stroke();
        // Atmospheric charge particles descending
        for (let i = 0; i < 8; i++) {
            const px = this.cx + (Math.random() - 0.5) * 60;
            const py = this.cy - 50 + ((this.t * 15 + i * 12) % 50);
            ctx.fillStyle = color; ctx.globalAlpha = 0.2 + Math.random() * 0.2;
            ctx.beginPath(); ctx.arc(px, py, 1, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Current flowing down rod
        const pulseY = this.cy - 45 + ((this.t * 30) % 85);
        ctx.fillStyle = color; ctx.globalAlpha = 0.7;
        ctx.beginPath(); ctx.arc(this.cx, pulseY, 2.5, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
        // Ground plate
        ctx.fillStyle = '#445'; ctx.fillRect(this.cx - 25, this.cy + 40, 50, 5);
        if (Math.random() < 0.08) this._spark(this.cx, this.cy - 45, color, 3);
    }

    _drawSchappeller() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Glowing magnetism sphere
        const pulseR = 30 + 8 * Math.sin(this.t * 2);
        // Outer sphere
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 40, 0, PI2);
        ctx.strokeStyle = color + '30'; ctx.lineWidth = 1.5; ctx.stroke();
        // Inner glowing sphere
        this._drawGlow(this.cx, this.cy, pulseR, color, 0.3);
        this._drawGlow(this.cx, this.cy, pulseR * 0.5, '#FFFFFF', 0.25);
        // Rotating magnetic field lines
        for (let i = 0; i < 6; i++) {
            const a = (i / 6) * PI2 + this.t * 1.5;
            ctx.beginPath();
            ctx.moveTo(this.cx, this.cy - 40);
            ctx.quadraticCurveTo(
                this.cx + 50 * Math.cos(a), this.cy,
                this.cx, this.cy + 40
            );
            ctx.strokeStyle = color + '15'; ctx.lineWidth = 1; ctx.stroke();
        }
        // Glowing magnetism particles
        for (let i = 0; i < 5; i++) {
            const pa = (i / 5) * PI2 + this.t * 3;
            const pr = pulseR * 0.7;
            ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.4;
            ctx.beginPath(); ctx.arc(this.cx + pr * Math.cos(pa), this.cy + pr * Math.sin(pa), 1.5, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    _drawHeim() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // 8D geometry visualization (projected hypercube hints)
        const rot = this.t * 0.8;
        // Inner and outer cubes
        for (let layer = 0; layer < 3; layer++) {
            const s = 15 + layer * 15;
            const offset = layer * 3;
            const r = rot + layer * 0.3;
            ctx.beginPath();
            for (let i = 0; i <= 4; i++) {
                const a = (i / 4) * PI2 + r;
                const x = this.cx + s * Math.cos(a) + offset;
                const y = this.cy + s * Math.sin(a) - offset;
                if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            }
            ctx.strokeStyle = color; ctx.globalAlpha = 0.15 + layer * 0.08; ctx.lineWidth = 1; ctx.stroke();
        }
        ctx.globalAlpha = 1;
        // Coupling lines between layers
        for (let i = 0; i < 4; i++) {
            const a = (i / 4) * PI2 + rot;
            const x1 = this.cx + 15 * Math.cos(a), y1 = this.cy + 15 * Math.sin(a);
            const x2 = this.cx + 45 * Math.cos(a + 0.3) + 6, y2 = this.cy + 45 * Math.sin(a + 0.3) - 6;
            ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2);
            ctx.strokeStyle = color + '15'; ctx.lineWidth = 0.5; ctx.stroke();
        }
        // Central gravitational coupling
        this._drawGlow(this.cx, this.cy, 8, color, 0.2 + 0.1 * Math.sin(this.t * 3));
    }

    _drawWitricity() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Transmitter coil (left)
        ctx.beginPath(); ctx.arc(this.cx - 30, this.cy, 18, 0, PI2);
        ctx.strokeStyle = color + '50'; ctx.lineWidth = 2; ctx.stroke();
        // Receiver coil (right)
        ctx.beginPath(); ctx.arc(this.cx + 30, this.cy, 15, 0, PI2);
        ctx.strokeStyle = '#22CC8850'; ctx.lineWidth = 2; ctx.stroke();
        // Wireless energy transfer waves
        for (let w = 0; w < 5; w++) {
            const wx = this.cx - 12 + ((this.t * 25 + w * 12) % 48);
            const amp = 12 * Math.sin(this.t * 6 + w);
            ctx.beginPath();
            ctx.moveTo(wx, this.cy - amp * 0.5);
            ctx.quadraticCurveTo(wx + 5, this.cy + amp * 0.5, wx + 10, this.cy - amp * 0.3);
            ctx.strokeStyle = color; ctx.globalAlpha = 0.3; ctx.lineWidth = 1.5; ctx.stroke();
        }
        ctx.globalAlpha = 1;
        // LED indicator on receiver
        const brightness = (Math.sin(this.t * 4) + 1) / 2;
        this._drawGlow(this.cx + 30, this.cy, 6, '#22CC88', brightness * 0.4);
        // Power indicator on transmitter
        this._drawGlow(this.cx - 30, this.cy, 8, color, 0.25);
    }

    _drawHawking() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Black hole
        ctx.fillStyle = '#000000'; ctx.beginPath(); ctx.arc(this.cx, this.cy, 15, 0, PI2); ctx.fill();
        // Event horizon glow
        this._drawGlow(this.cx, this.cy, 18, color, 0.2);
        // Accretion disk
        ctx.beginPath(); ctx.ellipse(this.cx, this.cy, 45, 10, this.t * 0.3, 0, PI2);
        ctx.strokeStyle = color + '30'; ctx.lineWidth = 3; ctx.stroke();
        // Hawking radiation (particle-antiparticle pairs)
        for (let i = 0; i < 4; i++) {
            const a = (i / 4) * PI2 + this.t * 2;
            const r = 16 + ((this.t * 10 + i * 5) % 35);
            const px = this.cx + r * Math.cos(a), py = this.cy + r * Math.sin(a);
            // Escaping particle
            ctx.fillStyle = color; ctx.globalAlpha = Math.max(0, 0.5 - r / 80);
            ctx.beginPath(); ctx.arc(px, py, 1.5, 0, PI2); ctx.fill();
            // Falling antiparticle
            const ar = Math.max(0, 16 - ((this.t * 10 + i * 5) % 16));
            const ax = this.cx + ar * Math.cos(a + Math.PI), ay = this.cy + ar * Math.sin(a + Math.PI);
            ctx.fillStyle = '#FF6B9A'; ctx.globalAlpha = 0.3;
            ctx.beginPath(); ctx.arc(ax, ay, 1.5, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    _drawSchwinger() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Strong electric field
        for (let i = 0; i < 8; i++) {
            const x = this.cx - 35 + i * 10;
            ctx.beginPath(); ctx.moveTo(x, this.cy - 45); ctx.lineTo(x, this.cy + 45);
            ctx.strokeStyle = '#FF6B9A10'; ctx.lineWidth = 1; ctx.stroke();
        }
        // Pair production events
        for (let i = 0; i < 3; i++) {
            const py = this.cy + Math.sin(this.t * 3 + i * 2) * 30;
            const spread = ((this.t * 2 + i) % 3) * 12;
            // Electron
            ctx.fillStyle = color; ctx.globalAlpha = 0.6;
            ctx.beginPath(); ctx.arc(this.cx - spread, py, 3, 0, PI2); ctx.fill();
            // Positron
            ctx.fillStyle = '#FF6B9A';
            ctx.beginPath(); ctx.arc(this.cx + spread, py, 3, 0, PI2); ctx.fill();
            // Creation flash
            if (spread < 3) {
                this._drawGlow(this.cx, py, 8, '#FFFFFF', 0.4);
            }
        }
        ctx.globalAlpha = 1;
    }

    _drawQuantumBio() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Leaf / chloroplast shape
        ctx.beginPath();
        ctx.moveTo(this.cx - 40, this.cy);
        ctx.quadraticCurveTo(this.cx, this.cy - 30, this.cx + 40, this.cy);
        ctx.quadraticCurveTo(this.cx, this.cy + 30, this.cx - 40, this.cy);
        ctx.fillStyle = '#22884420'; ctx.fill();
        ctx.strokeStyle = '#22884460'; ctx.lineWidth = 1; ctx.stroke();
        // Quantum coherence pathways
        for (let i = 0; i < 5; i++) {
            const sx = this.cx - 30 + i * 12;
            const sy = this.cy + (i % 2 - 0.5) * 15;
            const ex = sx + 12, ey = this.cy + ((i + 1) % 2 - 0.5) * 15;
            // Coherent energy hop
            const progress = (Math.sin(this.t * 4 + i * 1.2) + 1) / 2;
            const px = sx + (ex - sx) * progress, ppy = sy + (ey - sy) * progress;
            ctx.fillStyle = color; ctx.globalAlpha = 0.7;
            ctx.beginPath(); ctx.arc(px, ppy, 2.5, 0, PI2); ctx.fill();
            // Path line
            ctx.beginPath(); ctx.moveTo(sx, sy); ctx.lineTo(ex, ey);
            ctx.strokeStyle = color + '25'; ctx.lineWidth = 0.8; ctx.stroke();
        }
        ctx.globalAlpha = 1;
        // Photon arriving
        const photonX = this.cx - 50 + ((this.t * 20) % 40);
        ctx.fillStyle = '#FFFF44'; ctx.globalAlpha = 0.5;
        ctx.beginPath(); ctx.arc(photonX, this.cy - 20, 2, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
    }

    _drawSAFIRE() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Central anode sphere
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 12, 0, PI2);
        ctx.fillStyle = '#AA6633'; ctx.globalAlpha = 0.5; ctx.fill(); ctx.globalAlpha = 1;
        ctx.strokeStyle = color + '60'; ctx.lineWidth = 1; ctx.stroke();
        // Plasma layers
        for (let r = 0; r < 4; r++) {
            const rad = 16 + r * 8;
            ctx.beginPath(); ctx.arc(this.cx, this.cy, rad, 0, PI2);
            ctx.strokeStyle = color; ctx.globalAlpha = 0.12 - r * 0.02; ctx.lineWidth = 3; ctx.stroke();
        }
        ctx.globalAlpha = 1;
        // Electric discharge tufts
        for (let i = 0; i < 8; i++) {
            const a = (i / 8) * PI2 + this.t * 0.5;
            const len = 15 + Math.random() * 20;
            ctx.beginPath(); ctx.moveTo(this.cx + 12 * Math.cos(a), this.cy + 12 * Math.sin(a));
            let lx = this.cx + 12 * Math.cos(a), ly = this.cy + 12 * Math.sin(a);
            for (let s = 0; s < 4; s++) {
                lx += (len / 4) * Math.cos(a + (Math.random() - 0.5) * 0.8);
                ly += (len / 4) * Math.sin(a + (Math.random() - 0.5) * 0.8);
                ctx.lineTo(lx, ly);
            }
            ctx.strokeStyle = color; ctx.globalAlpha = 0.2 + Math.random() * 0.2;
            ctx.lineWidth = 1; ctx.stroke();
        }
        ctx.globalAlpha = 1;
        // Outer cathode cage
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 48, 0, PI2);
        ctx.strokeStyle = '#66666640'; ctx.lineWidth = 1; ctx.stroke();
    }

    // ═══ BATCH 5: Classical EM, Optics, Nuclear ═══

    _drawLightning() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Storm cloud at top
        ctx.fillStyle = '#334'; ctx.beginPath();
        ctx.ellipse(this.cx, this.cy - 40, 40, 12, 0, 0, PI2); ctx.fill();
        // Lightning bolt — jagged line from cloud to ground
        if (Math.sin(this.t * 3) > 0.3) {
            ctx.beginPath(); ctx.moveTo(this.cx, this.cy - 30);
            let lx = this.cx, ly = this.cy - 30;
            for (let s = 0; s < 6; s++) {
                lx += (Math.random() - 0.5) * 20;
                ly += 12;
                ctx.lineTo(lx, ly);
            }
            ctx.strokeStyle = '#FFFFFF'; ctx.lineWidth = 2 + Math.random(); ctx.globalAlpha = 0.8; ctx.stroke();
            this._drawGlow(lx, ly, 8, color, 0.5);
            ctx.globalAlpha = 1;
        }
        // Kite
        ctx.strokeStyle = color + '60'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(this.cx + 25, this.cy - 15); ctx.lineTo(this.cx + 35, this.cy - 5);
        ctx.lineTo(this.cx + 25, this.cy + 5); ctx.lineTo(this.cx + 15, this.cy - 5); ctx.closePath(); ctx.stroke();
        // Key
        ctx.fillStyle = '#DDAA44'; ctx.fillRect(this.cx + 23, this.cy + 20, 4, 8);
    }

    _drawDoubleSlit() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Barrier with two slits
        ctx.fillStyle = '#555'; ctx.fillRect(this.cx - 2, this.cy - 50, 4, 35);
        ctx.fillRect(this.cx - 2, this.cy - 8, 4, 16);
        ctx.fillRect(this.cx - 2, this.cy + 15, 4, 35);
        // Incoming wave
        for (let w = 0; w < 4; w++) {
            const wx = this.cx - 10 - ((this.t * 20 + w * 15) % 60);
            ctx.beginPath(); ctx.moveTo(wx, this.cy - 45); ctx.lineTo(wx, this.cy + 45);
            ctx.strokeStyle = color + '15'; ctx.lineWidth = 1; ctx.stroke();
        }
        // Interference pattern on screen (right side)
        const screenX = this.cx + 50;
        for (let y = -45; y <= 45; y += 1) {
            const d = 12;
            const phase1 = Math.sqrt((50) ** 2 + (y + d) ** 2);
            const phase2 = Math.sqrt((50) ** 2 + (y - d) ** 2);
            const intensity = Math.cos((phase1 - phase2) * 0.8 + this.t * 3) ** 2;
            ctx.fillStyle = color; ctx.globalAlpha = intensity * 0.6;
            ctx.fillRect(screenX, this.cy + y, 3, 1);
        }
        ctx.globalAlpha = 1;
    }

    _drawLaser() {
        const ctx = this.ctx;
        const color = '#FF2222';
        // Laser cavity (rod)
        ctx.strokeStyle = '#888'; ctx.lineWidth = 1.5;
        ctx.strokeRect(this.cx - 35, this.cy - 6, 50, 12);
        // Crystal medium glow
        ctx.fillStyle = color; ctx.globalAlpha = 0.15;
        ctx.fillRect(this.cx - 34, this.cy - 5, 48, 10);
        ctx.globalAlpha = 1;
        // Mirrors at ends
        ctx.fillStyle = '#AAA'; ctx.fillRect(this.cx - 37, this.cy - 8, 3, 16);
        ctx.fillStyle = '#AAA99'; ctx.fillRect(this.cx + 15, this.cy - 8, 3, 16);
        // Beam output
        const beamFlicker = 0.7 + 0.3 * Math.sin(this.t * 20);
        ctx.strokeStyle = color; ctx.globalAlpha = beamFlicker; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(this.cx + 18, this.cy); ctx.lineTo(this.cx + 70, this.cy); ctx.stroke();
        // Beam glow
        ctx.globalAlpha = beamFlicker * 0.3; ctx.lineWidth = 6; ctx.stroke();
        ctx.globalAlpha = 1;
        // Photons bouncing inside
        for (let i = 0; i < 3; i++) {
            const px = this.cx - 33 + ((this.t * 40 + i * 20) % 46);
            ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.6;
            ctx.beginPath(); ctx.arc(px, this.cy, 1.5, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    _drawScattering() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Gold foil (thin vertical line)
        ctx.fillStyle = '#DDAA44'; ctx.globalAlpha = 0.3;
        ctx.fillRect(this.cx - 1, this.cy - 40, 2, 80);
        ctx.globalAlpha = 1;
        // Alpha particles approaching from left
        for (let i = 0; i < 8; i++) {
            const progress = (this.t * 15 + i * 15) % 120;
            const startX = this.cx - 60, startY = this.cy - 25 + i * 7;
            if (progress < 60) {
                // Approaching
                const px = startX + progress;
                ctx.fillStyle = color; ctx.globalAlpha = 0.6;
                ctx.beginPath(); ctx.arc(px, startY, 2, 0, PI2); ctx.fill();
            } else {
                // Scattered - various angles
                const scattered = progress - 60;
                const angle = (i < 6) ? (i - 3) * 0.05 : (i < 7 ? 0.8 : -1.2 + Math.PI);
                const px = this.cx + scattered * Math.cos(angle);
                const py = startY + scattered * Math.sin(angle);
                ctx.fillStyle = (Math.abs(angle) > 0.5) ? '#FF6666' : color;
                ctx.globalAlpha = Math.max(0, 0.6 - scattered / 80);
                ctx.beginPath(); ctx.arc(px, py, 2, 0, PI2); ctx.fill();
            }
        }
        ctx.globalAlpha = 1;
        // Nucleus dot
        this._drawGlow(this.cx, this.cy, 4, '#DDAA44', 0.4);
    }

    _drawFission() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Uranium nucleus
        const phase = (this.t * 2) % (Math.PI * 2);
        const wobble = Math.sin(phase) * 3;
        if (phase < Math.PI) {
            // Pre-fission: big nucleus wobbling
            ctx.beginPath(); ctx.ellipse(this.cx, this.cy, 15 + wobble, 15 - wobble, 0, 0, PI2);
            ctx.fillStyle = color; ctx.globalAlpha = 0.3; ctx.fill(); ctx.globalAlpha = 1;
            ctx.strokeStyle = color + '60'; ctx.lineWidth = 1.5; ctx.stroke();
        } else {
            // Post-fission: two fragments flying apart
            const sep = (phase - Math.PI) * 15;
            ctx.beginPath(); ctx.arc(this.cx - sep, this.cy, 8, 0, PI2);
            ctx.fillStyle = '#FF6644'; ctx.globalAlpha = 0.4; ctx.fill();
            ctx.beginPath(); ctx.arc(this.cx + sep * 0.7, this.cy - 3, 6, 0, PI2);
            ctx.fillStyle = '#44AAFF'; ctx.globalAlpha = 0.4; ctx.fill();
            ctx.globalAlpha = 1;
            // Neutrons
            for (let n = 0; n < 3; n++) {
                const na = (n / 3) * PI2 + phase * 2;
                const nr = sep * 0.8;
                ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.5;
                ctx.beginPath(); ctx.arc(this.cx + nr * Math.cos(na), this.cy + nr * Math.sin(na), 1.5, 0, PI2); ctx.fill();
            }
            // Energy flash
            if (sep < 5) this._drawGlow(this.cx, this.cy, 20, '#FFFFFF', 0.4);
        }
        ctx.globalAlpha = 1;
    }

    _drawCyclotron() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Two D-shaped electrodes
        const rot = this.t * 0.3;
        ctx.strokeStyle = color + '40'; ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 45, rot, rot + Math.PI); ctx.stroke();
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 45, rot + Math.PI, rot + PI2); ctx.stroke();
        // Gap between Dees
        ctx.strokeStyle = '#FFB80040'; ctx.lineWidth = 2;
        const gx1 = 45 * Math.cos(rot), gy1 = 45 * Math.sin(rot);
        ctx.beginPath(); ctx.moveTo(this.cx + gx1, this.cy + gy1); ctx.lineTo(this.cx - gx1, this.cy - gy1); ctx.stroke();
        // Spiraling particle
        const spiralT = (this.t * 3) % 10;
        const spiralR = spiralT * 4.5;
        const spiralA = spiralT * 8;
        if (spiralR < 45) {
            const px = this.cx + spiralR * Math.cos(spiralA);
            const py = this.cy + spiralR * Math.sin(spiralA);
            ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.8;
            ctx.beginPath(); ctx.arc(px, py, 2.5, 0, PI2); ctx.fill();
            // Trail
            for (let t = 0; t < 8; t++) {
                const tr = spiralR - t * 0.5;
                const ta = spiralA - t * 0.8;
                if (tr > 0) {
                    ctx.fillStyle = color; ctx.globalAlpha = 0.3 - t * 0.03;
                    ctx.beginPath(); ctx.arc(this.cx + tr * Math.cos(ta), this.cy + tr * Math.sin(ta), 1, 0, PI2); ctx.fill();
                }
            }
        }
        ctx.globalAlpha = 1;
        // Magnetic field indicator
        ctx.fillStyle = '#4488FF'; ctx.globalAlpha = 0.3; ctx.font = '10px monospace'; ctx.textAlign = 'center';
        ctx.fillText('B ⊗', this.cx, this.cy + 3);
        ctx.globalAlpha = 1;
    }

    _drawCollider() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Ring
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 45, 0, PI2);
        ctx.strokeStyle = color + '25'; ctx.lineWidth = 6; ctx.stroke();
        // Two counter-rotating particles
        const a1 = this.t * 5, a2 = -this.t * 5;
        ctx.fillStyle = '#FF4488'; ctx.globalAlpha = 0.8;
        ctx.beginPath(); ctx.arc(this.cx + 45 * Math.cos(a1), this.cy + 45 * Math.sin(a1), 3, 0, PI2); ctx.fill();
        ctx.fillStyle = '#4488FF';
        ctx.beginPath(); ctx.arc(this.cx + 45 * Math.cos(a2), this.cy + 45 * Math.sin(a2), 3, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
        // Collision event at intersection
        const dist = Math.abs(Math.sin((a1 - a2) / 2));
        if (dist < 0.15) {
            // Collision! Shower of particles
            this._drawGlow(this.cx + 45, this.cy, 15, '#FFFFFF', 0.6);
            for (let p = 0; p < 8; p++) {
                const pa = (p / 8) * PI2 + this.t * 10;
                const pr = Math.random() * 20;
                ctx.fillStyle = p % 2 ? '#FF4488' : '#4488FF'; ctx.globalAlpha = 0.4;
                ctx.beginPath(); ctx.arc(this.cx + 45 + pr * Math.cos(pa), this.cy + pr * Math.sin(pa), 1.5, 0, PI2); ctx.fill();
            }
            ctx.globalAlpha = 1;
        }
        // Detector segments
        for (let d = 0; d < 4; d++) {
            const da = (d / 4) * PI2;
            ctx.fillStyle = color + '15';
            ctx.beginPath(); ctx.arc(this.cx + 45 * Math.cos(da), this.cy + 45 * Math.sin(da), 6, 0, PI2); ctx.fill();
        }
    }

    // ═══ BATCH 6: Renewable, Battery, Relativity, Quantum Computing, Bio ═══

    _drawSolarCell() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Solar panel grid
        ctx.fillStyle = '#223355'; ctx.globalAlpha = 0.4;
        ctx.fillRect(this.cx - 35, this.cy - 20, 70, 40);
        ctx.globalAlpha = 1;
        // Grid lines
        ctx.strokeStyle = '#88BBFF30'; ctx.lineWidth = 0.5;
        for (let x = -30; x <= 30; x += 10) {
            ctx.beginPath(); ctx.moveTo(this.cx + x, this.cy - 20); ctx.lineTo(this.cx + x, this.cy + 20); ctx.stroke();
        }
        for (let y = -15; y <= 15; y += 10) {
            ctx.beginPath(); ctx.moveTo(this.cx - 35, this.cy + y); ctx.lineTo(this.cx + 35, this.cy + y); ctx.stroke();
        }
        // Sunlight photons arriving
        for (let i = 0; i < 6; i++) {
            const px = this.cx - 25 + i * 10;
            const py = this.cy - 25 - ((this.t * 20 + i * 8) % 25);
            ctx.strokeStyle = '#FFEE44'; ctx.lineWidth = 1; ctx.globalAlpha = 0.5;
            ctx.beginPath(); ctx.moveTo(px, py); ctx.lineTo(px + 2, py + 5); ctx.stroke();
        }
        ctx.globalAlpha = 1;
        // Electron ejected
        const ey = this.cy + 20 + ((this.t * 15) % 20);
        ctx.fillStyle = color; ctx.globalAlpha = 0.6;
        ctx.beginPath(); ctx.arc(this.cx, ey, 2, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
        // Output wires
        ctx.strokeStyle = '#FF2222'; ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.moveTo(this.cx - 35, this.cy + 20); ctx.lineTo(this.cx - 35, this.cy + 35); ctx.stroke();
        ctx.strokeStyle = '#2222FF';
        ctx.beginPath(); ctx.moveTo(this.cx + 35, this.cy + 20); ctx.lineTo(this.cx + 35, this.cy + 35); ctx.stroke();
    }

    _drawWindTurbine() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Tower
        ctx.strokeStyle = '#888'; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy); ctx.lineTo(this.cx, this.cy + 45); ctx.stroke();
        // Nacelle
        ctx.fillStyle = '#666'; ctx.fillRect(this.cx - 5, this.cy - 3, 10, 6);
        // Blades rotating
        const rot = this.t * 3;
        for (let b = 0; b < 3; b++) {
            const a = (b / 3) * PI2 + rot;
            ctx.beginPath(); ctx.moveTo(this.cx, this.cy);
            ctx.lineTo(this.cx + 38 * Math.cos(a), this.cy + 38 * Math.sin(a));
            ctx.strokeStyle = '#CCCCCC'; ctx.lineWidth = 2; ctx.stroke();
        }
        // Hub
        ctx.fillStyle = '#AAA'; ctx.beginPath(); ctx.arc(this.cx, this.cy, 3, 0, PI2); ctx.fill();
        // Wind lines
        for (let w = 0; w < 4; w++) {
            const wy = this.cy - 30 + w * 15;
            const wx = this.cx - 55 + ((this.t * 25 + w * 15) % 30);
            ctx.strokeStyle = color + '20'; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(wx, wy); ctx.lineTo(wx + 12, wy); ctx.stroke();
        }
        // Ground
        ctx.strokeStyle = '#44662240'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(this.cx - 40, this.cy + 45); ctx.lineTo(this.cx + 40, this.cy + 45); ctx.stroke();
    }

    _drawInterferometer() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Beam splitter at center
        ctx.fillStyle = '#AAAAFF40'; ctx.save();
        ctx.translate(this.cx, this.cy); ctx.rotate(Math.PI / 4);
        ctx.fillRect(-12, -1, 24, 2); ctx.restore();
        // Two arms
        const armLen = 40;
        // Horizontal arm
        ctx.strokeStyle = color + '30'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy); ctx.lineTo(this.cx + armLen, this.cy); ctx.stroke();
        // Vertical arm
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy); ctx.lineTo(this.cx, this.cy - armLen); ctx.stroke();
        // Mirrors at ends
        ctx.fillStyle = '#AAAAAA'; ctx.fillRect(this.cx + armLen - 1, this.cy - 6, 3, 12);
        ctx.fillRect(this.cx - 6, this.cy - armLen - 1, 12, 3);
        // Laser beams traveling along arms
        const pulseH = (this.t * 30) % (armLen * 2);
        const pulseV = (this.t * 30 + armLen) % (armLen * 2);
        const hx = pulseH < armLen ? this.cx + pulseH : this.cx + armLen * 2 - pulseH;
        const vy = pulseV < armLen ? this.cy - pulseV : this.cy - armLen * 2 + pulseV;
        ctx.fillStyle = color; ctx.globalAlpha = 0.7;
        ctx.beginPath(); ctx.arc(hx, this.cy, 2, 0, PI2); ctx.fill();
        ctx.beginPath(); ctx.arc(this.cx, vy, 2, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
        // Gravitational wave distortion
        const stretch = Math.sin(this.t * 2) * 3;
        ctx.strokeStyle = '#AA66FF30'; ctx.lineWidth = 0.5;
        ctx.setLineDash([2, 4]);
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy); ctx.lineTo(this.cx + armLen + stretch, this.cy); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(this.cx, this.cy); ctx.lineTo(this.cx, this.cy - armLen - stretch); ctx.stroke();
        ctx.setLineDash([]);
        // Detector
        ctx.fillStyle = '#22FF66'; ctx.globalAlpha = 0.3 + 0.2 * Math.sin(this.t * 4);
        ctx.fillRect(this.cx - 15, this.cy + 5, 10, 8);
        ctx.globalAlpha = 1;
    }

    _drawEntanglement() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Two entangled particles
        const sep = 40;
        const spin1 = Math.sin(this.t * 4);
        const spin2 = -spin1; // always anti-correlated
        // Particle A
        ctx.fillStyle = '#FF4488'; ctx.globalAlpha = 0.6;
        ctx.beginPath(); ctx.arc(this.cx - sep, this.cy, 8, 0, PI2); ctx.fill();
        // Spin arrow A
        ctx.strokeStyle = '#FFFFFF'; ctx.lineWidth = 1.5; ctx.globalAlpha = 0.8;
        ctx.beginPath(); ctx.moveTo(this.cx - sep, this.cy + 12 * spin1);
        ctx.lineTo(this.cx - sep, this.cy - 12 * spin1); ctx.stroke();
        // Particle B
        ctx.fillStyle = '#4488FF'; ctx.globalAlpha = 0.6;
        ctx.beginPath(); ctx.arc(this.cx + sep, this.cy, 8, 0, PI2); ctx.fill();
        // Spin arrow B
        ctx.strokeStyle = '#FFFFFF'; ctx.globalAlpha = 0.8;
        ctx.beginPath(); ctx.moveTo(this.cx + sep, this.cy + 12 * spin2);
        ctx.lineTo(this.cx + sep, this.cy - 12 * spin2); ctx.stroke();
        ctx.globalAlpha = 1;
        // Entanglement connection (wavy line)
        ctx.beginPath();
        for (let x = -sep + 10; x <= sep - 10; x += 2) {
            const y = Math.sin(x * 0.3 + this.t * 6) * 5;
            if (x === -sep + 10) ctx.moveTo(this.cx + x, this.cy + y);
            else ctx.lineTo(this.cx + x, this.cy + y);
        }
        ctx.strokeStyle = color + '30'; ctx.lineWidth = 1; ctx.stroke();
        // Labels
        ctx.fillStyle = '#FFFFFF'; ctx.globalAlpha = 0.5; ctx.font = '9px monospace'; ctx.textAlign = 'center';
        ctx.fillText('|ψ⟩', this.cx, this.cy - 20);
        ctx.globalAlpha = 1;
    }

    _drawActionPotential() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Neuron cell body
        ctx.beginPath(); ctx.arc(this.cx - 35, this.cy, 10, 0, PI2);
        ctx.fillStyle = color; ctx.globalAlpha = 0.2; ctx.fill(); ctx.globalAlpha = 1;
        ctx.strokeStyle = color + '50'; ctx.lineWidth = 1; ctx.stroke();
        // Axon
        ctx.strokeStyle = color + '40'; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(this.cx - 25, this.cy); ctx.lineTo(this.cx + 40, this.cy); ctx.stroke();
        // Myelin sheaths
        for (let m = 0; m < 4; m++) {
            const mx = this.cx - 18 + m * 15;
            ctx.fillStyle = '#665544'; ctx.globalAlpha = 0.2;
            ctx.fillRect(mx, this.cy - 5, 10, 10);
        }
        ctx.globalAlpha = 1;
        // Action potential pulse traveling along axon
        const pulse = ((this.t * 30) % 80) - 10;
        const pulseX = this.cx - 30 + pulse;
        // Voltage spike at pulse location
        ctx.beginPath();
        for (let x = -35; x <= 45; x += 1) {
            const dx = x - (pulse - 25);
            const v = -70 + 100 * Math.exp(-dx * dx / 30);
            const vy = this.cy - v * 0.3 - 10;
            if (x === -35) ctx.moveTo(this.cx + x, vy); else ctx.lineTo(this.cx + x, vy);
        }
        ctx.strokeStyle = color; ctx.globalAlpha = 0.6; ctx.lineWidth = 1; ctx.stroke();
        ctx.globalAlpha = 1;
        // Na+/K+ ions at pulse
        if (pulseX > this.cx - 30 && pulseX < this.cx + 40) {
            ctx.fillStyle = '#FF6644'; ctx.globalAlpha = 0.5; ctx.font = '7px monospace';
            ctx.fillText('Na⁺', pulseX, this.cy - 10);
            ctx.fillStyle = '#4466FF';
            ctx.fillText('K⁺', pulseX, this.cy + 15);
            ctx.globalAlpha = 1;
        }
        // Synapse at end
        ctx.fillStyle = color; ctx.globalAlpha = 0.15;
        ctx.beginPath(); ctx.arc(this.cx + 45, this.cy, 6, 0, PI2); ctx.fill();
        ctx.globalAlpha = 1;
    }

    _drawBatteryStack() {
        const ctx = this.ctx;
        const color = this.exp.color;
        const cells = Math.min(this.exp.physics.cells || 8, 10);
        // Stacked cells
        for (let i = 0; i < cells; i++) {
            const y = this.cy - (cells * 3) + i * 6;
            // Zinc (gray) layer
            ctx.fillStyle = '#888'; ctx.globalAlpha = 0.4;
            ctx.fillRect(this.cx - 18, y, 36, 3);
            // Copper (brown) layer
            ctx.fillStyle = '#CC8833';
            ctx.fillRect(this.cx - 18, y + 3, 36, 2);
        }
        ctx.globalAlpha = 1;
        // Voltage glow at terminals
        this._drawGlow(this.cx, this.cy - (cells * 3), 6, color, 0.3 + 0.15 * Math.sin(this.t * 3));
        // Current flow
        ctx.strokeStyle = color + '50'; ctx.lineWidth = 1;
        const flowY = this.cy - (cells * 3) - ((this.t * 15) % (cells * 6));
        ctx.beginPath(); ctx.moveTo(this.cx + 20, this.cy + (cells * 3));
        ctx.lineTo(this.cx + 25, this.cy + (cells * 3));
        ctx.lineTo(this.cx + 25, this.cy - (cells * 3));
        ctx.stroke();
    }

    _drawEEG() {
        const ctx = this.ctx;
        const color = this.exp.color;
        // Head outline
        ctx.beginPath(); ctx.arc(this.cx, this.cy, 30, 0, PI2);
        ctx.strokeStyle = '#666'; ctx.lineWidth = 1.5; ctx.stroke();
        // Electrode dots
        const electrodes = [[0,-28],[-20,-15],[20,-15],[-25,5],[25,5],[-15,20],[15,20]];
        for (const [ex, ey] of electrodes) {
            ctx.fillStyle = color; ctx.globalAlpha = 0.4;
            ctx.beginPath(); ctx.arc(this.cx + ex, this.cy + ey, 2.5, 0, PI2); ctx.fill();
        }
        ctx.globalAlpha = 1;
        // Brain wave traces
        const bands = [{freq: 10, amp: 8, color: '#44FF88'}, {freq: 20, amp: 5, color: '#4488FF'}, {freq: 5, amp: 10, color: '#FF8844'}, {freq: 2, amp: 12, color: '#FF4488'}];
        for (let b = 0; b < bands.length; b++) {
            const band = bands[b];
            ctx.beginPath();
            for (let x = -55; x <= -5; x += 1) {
                const y = this.cy - 35 + b * 20 + band.amp * Math.sin(x * band.freq * 0.02 + this.t * band.freq * 0.5);
                if (x === -55) ctx.moveTo(this.cx + x, y); else ctx.lineTo(this.cx + x, y);
            }
            ctx.strokeStyle = band.color; ctx.globalAlpha = 0.4; ctx.lineWidth = 1; ctx.stroke();
        }
        ctx.globalAlpha = 1;
    }

    _drawGeneric() {

    // Adaptive generic renderer based on tags
    const color = this.exp.color || '#00D4FF';
    const tags = this.exp.tags;
    const hasGravity = tags.includes('gravity');
    const hasPlasma = tags.includes('plasma');
    const hasNuclear = tags.includes('nuclear');
    const hasMagnetic = tags.includes('magnetic');
    const hasThermal = tags.includes('thermal');
    const hasQuantum = tags.includes('quantum');
    const hasWireless = tags.includes('wireless');
    const hasPropulsion = tags.includes('propulsion');
    const hasChemical = tags.includes('chemical');
    const hasBio = tags.includes('biological');
    const hasResonance = tags.includes('resonance');

    // Central device glow
    const pulseR = 25 + 10 * Math.sin(this.t * 3);
    this._drawGlow(this.cx, this.cy, pulseR, color, 0.3 + 0.15 * Math.sin(this.t * 2.5));

        if (hasPlasma || hasNuclear) {
            // Hot plasma core
            this._drawGlow(this.cx, this.cy, 15, '#FFFFFF', 0.3 + 0.2 * Math.sin(this.t * 5));
            for (let i = 0; i < 8; i++) {
                const angle = (i / 8) * PI2 + this.t * 4;
                const r = 20 + Math.random() * 15;
                const px = this.cx + r * Math.cos(angle);
                const py = this.cy + r * Math.sin(angle);
                ctx.fillStyle = color;
                ctx.globalAlpha = 0.3 + Math.random() * 0.4;
                ctx.beginPath(); ctx.arc(px, py, 2 + Math.random() * 2, 0, PI2); ctx.fill();
            }
            ctx.globalAlpha = 1;
            if (Math.random() < 0.12) this._spark(this.cx + (Math.random()-0.5)*40, this.cy + (Math.random()-0.5)*40, color, 3);
        }

        if (hasGravity || hasPropulsion) {
            // Field lines curving upward
            ctx.strokeStyle = color + '40';
            ctx.lineWidth = 1;
            for (let i = 0; i < 5; i++) {
                const bx = this.cx - 50 + i * 25;
                ctx.beginPath();
                ctx.moveTo(bx, this.cy + 40);
                ctx.quadraticCurveTo(bx + Math.sin(this.t * 2 + i) * 15, this.cy, bx, this.cy - 40 - Math.sin(this.t * 1.5) * 10);
                ctx.stroke();
            }
        }

        if (hasMagnetic) {
            // Magnetic field lines
            for (let side = -1; side <= 1; side += 2) {
                for (let r = 0; r < 3; r++) {
                    const rad = 30 + r * 15;
                    ctx.beginPath();
                    ctx.ellipse(this.cx + side * 20, this.cy, rad * 0.4, rad, 0, -Math.PI/2, Math.PI/2, side < 0);
                    ctx.strokeStyle = color + '20';
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }
            }
        }

        if (hasQuantum) {
            // Quantum probability clouds
            for (let i = 0; i < 20; i++) {
                const angle = Math.random() * PI2;
                const r = Math.random() * 60;
                const qx = this.cx + r * Math.cos(angle + this.t * 0.5);
                const qy = this.cy + r * Math.sin(angle + this.t * 0.3);
                const osc = (Math.sin(this.t * 6 + i) + 1) / 2;
                ctx.fillStyle = color;
                ctx.globalAlpha = osc * 0.25;
                ctx.beginPath(); ctx.arc(qx, qy, 1.5, 0, PI2); ctx.fill();
            }
            ctx.globalAlpha = 1;
        }

        if (hasWireless) {
            // Radiating rings
            for (let r = 0; r < 4; r++) {
                const rad = (r * 25 + this.t * 35) % 100;
                ctx.beginPath(); ctx.arc(this.cx, this.cy, rad, 0, PI2);
                ctx.strokeStyle = color; ctx.globalAlpha = Math.max(0, 0.3 - rad/350);
                ctx.lineWidth = 1.5; ctx.stroke();
            }
            ctx.globalAlpha = 1;
        }

        if (hasThermal) {
            // Heat waves rising
            for (let i = 0; i < 4; i++) {
                const hx = this.cx - 30 + i * 20;
                ctx.beginPath();
                for (let y = 0; y < 40; y += 3) {
                    const wx = hx + Math.sin(this.t * 4 + y * 0.3 + i) * 6;
                    if (y === 0) ctx.moveTo(wx, this.cy + 30 - y);
                    else ctx.lineTo(wx, this.cy + 30 - y);
                }
                ctx.strokeStyle = color + '30'; ctx.lineWidth = 1.5; ctx.stroke();
            }
        }

        if (hasChemical) {
            // Bubbles
            for (let i = 0; i < 5; i++) {
                const bx = this.cx - 25 + Math.random() * 50;
                const by = this.cy + 30 - ((this.t * 30 + i * 15) % 60);
                ctx.strokeStyle = color + '50'; ctx.lineWidth = 0.8;
                ctx.beginPath(); ctx.arc(bx, by, 2 + Math.random() * 2, 0, PI2); ctx.stroke();
            }
        }

        if (hasBio) {
            // Organic pulsing tendrils
            for (let i = 0; i < 6; i++) {
                const a = (i / 6) * PI2 + this.t * 0.3;
                ctx.beginPath();
                const len = 30 + 15 * Math.sin(this.t * 2 + i * 1.1);
                ctx.moveTo(this.cx, this.cy);
                const cpx = this.cx + len * 0.6 * Math.cos(a + 0.3);
                const cpy = this.cy + len * 0.6 * Math.sin(a + 0.3);
                ctx.quadraticCurveTo(cpx, cpy, this.cx + len * Math.cos(a), this.cy + len * Math.sin(a));
                ctx.strokeStyle = color + '35'; ctx.lineWidth = 2; ctx.stroke();
            }
        }

        if (hasResonance) {
            // Standing wave
            ctx.beginPath();
            for (let x = -70; x <= 70; x += 2) {
                const y = Math.sin(x * 0.12 + this.t * 5) * 15 * Math.sin(x * 0.04);
                if (x === -70) ctx.moveTo(this.cx + x, this.cy + y);
                else ctx.lineTo(this.cx + x, this.cy + y);
            }
            ctx.strokeStyle = color + '40'; ctx.lineWidth = 1.5; ctx.stroke();
        }

        // Orbiting energy dots
        for (let i = 0; i < 6; i++) {
            const angle = (i / 6) * PI2 + this.t * 2;
            const r = 50 + 10 * Math.sin(this.t * 3 + i);
            const x = this.cx + r * Math.cos(angle);
            const y = this.cy + r * Math.sin(angle);
            ctx.fillStyle = color;
            ctx.globalAlpha = 0.6;
            ctx.beginPath();
            ctx.arc(x, y, 2.5, 0, PI2);
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        if (Math.random() < 0.06) this._spark(this.cx + (Math.random()-0.5)*60, this.cy + (Math.random()-0.5)*60, color, 2);
    }

    _draw() {
        this._bg();

        const phys = this.exp.physics;
        switch (phys.type) {
            case 'pauli-exclusion': this._drawPauliExclusion(); break;
            case 'dirac-antimatter': this._drawDiracAntimatter(); break;
            case 'quark-model': this._drawQuarkModel(); break;
            case 'gravity-amplifier': this._drawGravityAmplifier(); break;
            case 'inclined-plane': this._drawInclinedPlane(); break;
            case 'prism': this._drawPrism(); break;
            case 'torsion-balance': this._drawTorsionBalance(); break;
            case 'paddle-wheel': this._drawPaddleWheel(); break;
            case 'pendulum': this._drawPendulum(); break;
            case 'demon': this._drawDemon(); break;
            case 'xray': this._drawXray(); break;
            case 'oil-drop': this._drawOilDrop(); break;
            case 'zeeman': this._drawZeeman(); break;
            case 'electron-diffraction': this._drawElectronDiffraction(); break;
            case 'neutron': this._drawNeutron(); break;
            case 'cosmic-ray': this._drawCosmicRay(); break;
            case 'expansion': this._drawExpansion(); break;
            case 'tesla': this._drawTesla(); break;
            case 'casimir': this._drawCasimir(); break;
            case 'interference': this._drawInterference(); break;
            case 'motor-pulse': this._drawMotorPulse(); break;
            case 'resonant-step': this._drawResonantStep(); break;
            case 'seg': this._drawSEG(); break;
            case 'vta': this._drawVTA(); break;
            case 'meg': this._drawMEG(); break;
            case 'lenr': this._drawLENR(); break;
            case 'wfc': this._drawWFC(); break;
            case 'acoustic': this._drawAcoustic(); break;
            case 'vortex-flow': this._drawVortexFlow(); break;
            case 'cse': this._drawCSE(); break;
            case 'gravity-shield': this._drawGravityShield(); break;
            case 'lifter': this._drawLifter(); break;
            case 'magnetic-motor': this._drawMagneticMotor(); break;
            case 'schumann': this._drawSchumann(); break;
            case 'induction': this._drawInduction(); break;
            case 'oscillator': this._drawOscillator(); break;
            case 'scalar': this._drawScalar(); break;
            case 'vortex': this._drawVortex(); break;
            // Batch 1 — Tesla variants, Plasma, Fusion
            case 'tower': case 'magnifying-tx': case 'corona': case 'cold-elec': this._drawTower(); break;
            case 'turbine': case 'clem': this._drawTurbine(); break;
            case 'radiant': case 'kapanadze': this._drawRadiant(); break;
            case 'electrostatic': this._drawElectrostatic(); break;
            case 'toroidal': this._drawToroidal(); break;
            case 'homopolar': this._drawHomopolar(); break;
            case 'plasma-discharge': case 'pagd': this._drawPlasmaDischarge(); break;
            case 'fusor': this._drawFusor(); break;
            case 'tokamak': this._drawTokamak(); break;
            // Batch 2 — Orgone, MWO, Warp, EmDrive, Bessler, HHO
            case 'orgone': this._drawOrgone(); break;
            case 'cloudbuster': this._drawCloudbuster(); break;
            case 'mwo': this._drawMWO(); break;
            case 'rife': this._drawRife(); break;
            case 'warp': this._drawWarp(); break;
            case 'emdrive': case 'mach-thruster': case 'quantum-thruster': this._drawEmdrive(); break;
            case 'bessler': this._drawBessler(); break;
            case 'hho': case 'joe-cell': this._drawHHO(); break;
            case 'superconductor': this._drawSuperconductor(); break;
            // Batch 3 — Quantum, Mechanical, Fusion variants
            case 'tunneling': case 'ab-effect': case 'lamb-shift': this._drawTunneling(); break;
            case 'piezo': this._drawPiezo(); break;
            case 'stirling': case 'teg': case 'thermoacoustic': this._drawStirling(); break;
            case 'sono': case 'sono-fusion': case 'muon': this._drawSono(); break;
            case 'z-pinch': this._drawZPinch(); break;
            case 'polywell': this._drawPolywell(); break;
            case 'dpf': this._drawDPF(); break;
            case 'hydrino': this._drawHydrino(); break;
            // Batch 4 — Exotic, Quantum, Bio
            case 'atmospheric': case 'earth-battery': this._drawAtmospheric(); break;
            case 'schappeller': this._drawSchappeller(); break;
            case 'heim': this._drawHeim(); break;
            case 'witricity': this._drawWitricity(); break;
            case 'hawking': case 'unruh': this._drawHawking(); break;
            case 'schwinger': case 'dynamic-casimir': case 'sed': this._drawSchwinger(); break;
            case 'quantum-bio': this._drawQuantumBio(); break;
            case 'safire': this._drawSAFIRE(); break;
            // Batch 5 — Classical EM, Optics, Nuclear/Particle
            case 'lightning': this._drawLightning(); break;
            case 'double-slit': this._drawDoubleSlit(); break;
            case 'laser': this._drawLaser(); break;
            case 'scattering': this._drawScattering(); break;
            case 'fission': this._drawFission(); break;
            case 'cyclotron': this._drawCyclotron(); break;
            case 'collider': case 'bubble-chamber': this._drawCollider(); break;
            // Batch 6 — Renewable, Battery, Relativity, Quantum, Bio
            case 'solar-cell': this._drawSolarCell(); break;
            case 'wind': this._drawWindTurbine(); break;
            case 'interferometer': this._drawInterferometer(); break;
            case 'entanglement': case 'qubit': case 'teleportation': case 'bell-test': this._drawEntanglement(); break;
            case 'action-potential': case 'bioelectric': this._drawActionPotential(); break;
            case 'battery-stack': case 'capacitor': this._drawBatteryStack(); break;
            case 'eeg': case 'bioluminescence': this._drawEEG(); break;
            // Routed to existing or generic
            case 'ema-motor': case 'newman': case 'adams': this._drawMotorPulse(); break;
            case 'hex-magnet': case 'ecklin': this._drawMagneticMotor(); break;
            case 'earth-field': this._drawInduction(); break;
            case 'geet': this._drawVortexFlow(); break;
            case 'ac-gravity': this._drawGravityShield(); break;
            case 'pemf': this._drawMEG(); break;
            case 'crystal': this._drawOscillator(); break;
            case 'cathode-ray': case 'filament': this._drawPlasmaDischarge(); break;
            case 'em-wave': case 'hertz': case 'radio-tx': this._drawRadiant(); break;
            case 'coulomb': this._drawElectrostatic(); break;
            case 'oersted': this._drawInduction(); break;
            case 'radioactive': case 'pair-production': this._drawFission(); break;
            case 'reactor': this._drawTokamak(); break;
            case 'cloud-chamber': this._drawScattering(); break;
            case 'photoelectric': this._drawSolarCell(); break;
            case 'fiber': case 'hologram': this._drawLaser(); break;
            case 'hydro': case 'tidal': this._drawTurbine(); break;
            case 'geothermal': case 'csp': this._drawStirling(); break;
            case 'li-ion': case 'fuel-cell': case 'supercap': case 'flow-battery': this._drawBatteryStack(); break;
            case 'cavendish': case 'lensing': this._drawGravityShield(); break;
            case 'gps-orbit': case 'gyroscope': this._drawCyclotron(); break;
            case 'eel-discharge': this._drawPlasmaDischarge(); break;
            default: this._drawGeneric();
        }

        this._drawSparks();
    }

    _loop() {
        if (!this.running) return;
        this.t += 0.025;
        this._draw();
        this.frameId = requestAnimationFrame(() => this._loop());
    }

    start() { this.running = true; this._loop(); }
    stop() { this.running = false; if (this.frameId) cancelAnimationFrame(this.frameId); }
}


// ══════════════════════════════════════════════
//  BUILD UI
// ══════════════════════════════════════════════

const grid = document.getElementById('experimentGrid');
const animators = [];
let currentFilter = 'all';
let currentSearch = '';

function buildCards(filter, searchQuery) {
    grid.innerHTML = '';
    animators.forEach(a => a.stop());
    animators.length = 0;

    currentFilter = filter || currentFilter;
    currentSearch = (searchQuery !== undefined ? searchQuery : currentSearch).toLowerCase().trim();

    let filtered = currentFilter === 'all'
        ? EXPERIMENTS
        : EXPERIMENTS.filter(e => {
            if (currentFilter === 'verified' || currentFilter === 'unverified' || currentFilter === 'disputed' || currentFilter === 'theoretical') return e.status === currentFilter;
            return e.tags.includes(currentFilter);
        });

    // Apply search filter
    if (currentSearch) {
        filtered = filtered.filter(e =>
            e.name.toLowerCase().includes(currentSearch) ||
            e.researcher.toLowerCase().includes(currentSearch) ||
            e.description.toLowerCase().includes(currentSearch) ||
            e.formula.toLowerCase().includes(currentSearch) ||
            e.tags.some(t => t.toLowerCase().includes(currentSearch)) ||
            e.id.toLowerCase().includes(currentSearch)
        );
    }

    for (const exp of filtered) {
        const card = document.createElement('div');
        card.className = 'ex-card';
        card.dataset.status = exp.status;

        const statusClass = ({
            verified: 'ex-status-verified',
            unverified: 'ex-status-unverified',
            disputed: 'ex-status-disputed',
            theoretical: 'ex-status-theoretical'
        })[exp.status] || 'ex-status-unverified';

        const tagHTML = exp.tags.map(t => {
            const tclass = ({
                zpe: 'ex-tag-zpe', em: 'ex-tag-em', nuclear: 'ex-tag-nuclear',
                acoustic: 'ex-tag-acoustic', gravity: 'ex-tag-gravity',
                thermal: 'ex-tag-thermal', electrolysis: 'ex-tag-electrolysis', magnetic: 'ex-tag-magnetic',
                plasma: 'ex-tag-plasma', mechanical: 'ex-tag-mechanical', biological: 'ex-tag-biological',
                chemical: 'ex-tag-chemical', quantum: 'ex-tag-quantum', propulsion: 'ex-tag-propulsion',
                wireless: 'ex-tag-wireless', resonance: 'ex-tag-resonance',
                optics: 'ex-tag-optics', particle: 'ex-tag-particle', battery: 'ex-tag-battery',
                renewable: 'ex-tag-renewable', historical: 'ex-tag-historical', bioelectric: 'ex-tag-bioelectric',
                relativity: 'ex-tag-relativity', computing: 'ex-tag-computing'
            })[t] || 'ex-tag-em';
            return '<span class="ex-tag ' + tclass + '">' + t + '</span>';
        }).join('');

        card.innerHTML =
            '<div class="ex-card-status ' + statusClass + '">' + exp.status + '</div>' +
            '<canvas class="ex-card-canvas" id="canvas-' + exp.id + '"></canvas>' +
            '<div class="ex-card-body">' +
            '<div class="ex-card-title"><i class="fas ' + exp.icon + ' ex-icon" style="color:' + exp.color + '"></i> ' + exp.name + '</div>' +
            '<div class="ex-card-researcher">' + exp.researcher + '</div>' +
            '<div class="ex-card-desc">' + exp.description + '</div>' +
            '<div class="ex-card-formula">' + exp.formula + '</div>' +
            '<div class="ex-card-tags">' + tagHTML + '</div>' +
            '</div>';

        grid.appendChild(card);

        // Init animator after DOM insertion
        requestAnimationFrame(() => {
            const canvasEl = document.getElementById('canvas-' + exp.id);
            if (canvasEl) {
                const anim = new ExperimentAnimator(canvasEl, exp);
                animators.push(anim);
            }
        });
    }

    document.getElementById('expCount').textContent = filtered.length;
    const badge = document.getElementById('expCountBadge');
    if (badge) badge.textContent = filtered.length;
}

// ── Filters ──
document.querySelectorAll('.ex-filter').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.ex-filter').forEach(b => b.classList.remove('ex-filter-active'));
        btn.classList.add('ex-filter-active');
        buildCards(btn.dataset.filter);
    });
});

// ── Search ──
const searchInput = document.getElementById('expSearch');
let searchTimer = null;
if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => buildCards(undefined, searchInput.value), 200);
    });
}

// ── Init ──
buildCards('all');

// Modal logic
function showExperimentModal(exp) {
    let modal = document.getElementById('ex-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'ex-modal';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.background = 'rgba(0,0,0,0.85)';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.zIndex = '9999';
        modal.innerHTML = '<div id="ex-modal-content" style="background:var(--ex-surface,#181c22);color:var(--ex-text,#fff);padding:2rem;max-width:600px;width:90vw;border-radius:18px;box-shadow:0 8px 32px rgba(0,212,255,.18);position:relative;overflow-y:auto;max-height:90vh;"></div>';
        document.body.appendChild(modal);
        modal.addEventListener('click', e => {
            if (e.target === modal) modal.style.display = 'none';
        });
    } else {
        modal.style.display = 'flex';
    }
    const content = modal.querySelector('#ex-modal-content');
    content.innerHTML = `
        <button id="ex-modal-close" style="position:absolute;top:1rem;right:1rem;font-size:1.5rem;background:none;border:none;color:var(--ex-accent,#0df);cursor:pointer;"><i class='fa fa-times'></i></button>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
            <i class="fas ${exp.icon} ex-icon" style="font-size:2.2rem;color:${exp.color}"></i>
            <div>
                <div style="font-size:1.3rem;font-weight:700;">${exp.name}</div>
                <div style="font-size:.9rem;color:var(--ex-muted,#aaa);">${exp.researcher}</div>
            </div>
        </div>
        <div style="margin-bottom:1rem;font-size:1.05rem;line-height:1.6;">${exp.description}</div>
        <div style="margin-bottom:1rem;"><b>Formula:</b> <span style="font-family:'SF Mono','Fira Code',monospace;">${exp.formula}</span></div>
        <div style="margin-bottom:1rem;"><b>Status:</b> <span>${exp.status}</span></div>
        <div style="margin-bottom:1rem;"><b>Tags:</b> ${exp.tags.map(t => `<span class='ex-tag' style='margin-right:.3em;'>${t}</span>`).join('')}</div>
        <div style="margin-bottom:1rem;"><b>ID:</b> <span>${exp.id}</span></div>
        <div style="margin-bottom:1rem;"><b>References:</b> <span>${exp.references ? exp.references.join(', ') : ''}</span></div>
    `;
    content.querySelector('#ex-modal-close').onclick = () => { modal.style.display = 'none'; };
}

})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
