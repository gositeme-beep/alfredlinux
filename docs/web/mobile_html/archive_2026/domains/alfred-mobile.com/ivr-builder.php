<?php
$page_title       = 'AI-Powered IVR Builder — Visual Call Flow Designer | GoSiteMe';
$page_description = 'Design intelligent IVR call flows with drag-and-drop simplicity. Alfred handles AI intent detection — no more "Press 1 for Sales." Build, test, and deploy in minutes.';
$page_canonical   = 'https://gositeme.com/ivr-builder.php';
$page_og_title    = 'AI-Powered IVR Builder — Design Call Flows Visually';
$page_og_description = 'Drag-and-drop IVR builder with AI intent detection. No coding required. Build intelligent call flows in minutes.';
$page_twitter_description = 'AI-powered IVR builder with drag-and-drop flow design. No more "Press 1 for Sales."';
include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>

<style>
/* ===== IVR BUILDER — SCOPED STYLES ===== */

.ivr-page {
    --ivr-bg: #0a0a14;
    --ivr-surface: #12121e;
    --ivr-surface-2: #1a1a2e;
    --ivr-surface-3: #222240;
    --ivr-border: rgba(255,255,255,.06);
    --ivr-border-hover: rgba(255,255,255,.12);
    --ivr-text: #e4e4ec;
    --ivr-text-muted: #8892b0;
    --ivr-text-dim: #5a6380;
    --ivr-accent: #6c5ce7;
    --ivr-accent-light: #a29bfe;
    --ivr-blue: #0984e3;
    --ivr-green: #00b894;
    --ivr-cyan: #00cec9;
    --ivr-orange: #e17055;
    --ivr-red: #d63031;
    --ivr-yellow: #fdcb6e;
    --ivr-pink: #fd79a8;
    --ivr-radius: 14px;
    --ivr-radius-sm: 10px;
    --ivr-radius-lg: 18px;
    --ivr-shadow: 0 4px 24px rgba(0,0,0,.3);
    --ivr-transition: all .25s cubic-bezier(.4,0,.2,1);
}

/* Hero */
.ivr-hero {
    padding: 120px 0 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.ivr-hero::before {
    content: '';
    position: absolute;
    top: -300px;
    left: 50%;
    transform: translateX(-50%);
    width: 900px;
    height: 900px;
    background: radial-gradient(circle, rgba(108,92,231,.2) 0%, rgba(9,132,227,.08) 40%, transparent 70%);
    pointer-events: none;
    animation: ivrHeroGlow 6s ease-in-out infinite;
}
@keyframes ivrHeroGlow {
    0%,100% { opacity:.4; transform:translateX(-50%) scale(1); }
    50% { opacity:.7; transform:translateX(-50%) scale(1.06); }
}
.ivr-hero .badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 100px;
    background: linear-gradient(135deg, rgba(108,92,231,.2), rgba(9,132,227,.15));
    border: 1px solid rgba(108,92,231,.3);
    color: var(--ivr-accent-light);
    font-size: .85rem;
    font-weight: 600;
    margin-bottom: 24px;
    letter-spacing: .5px;
}
.ivr-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 20px;
    position: relative;
}
.ivr-hero h1 .hl {
    background: linear-gradient(135deg, var(--ivr-accent-light), var(--ivr-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ivr-hero p {
    font-size: 1.15rem;
    color: var(--ivr-text-muted);
    max-width: 640px;
    margin: 0 auto;
    line-height: 1.7;
}

/* Container */
.ivr-container {
    max-width: 1340px;
    margin: 0 auto;
    padding: 0 24px;
}
.ivr-section {
    padding: 60px 0;
}
.ivr-section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 12px;
    text-align: center;
}
.ivr-section-sub {
    color: var(--ivr-text-muted);
    text-align: center;
    max-width: 600px;
    margin: 0 auto 40px;
    font-size: 1rem;
}

/* ===== BUILDER AREA ===== */
.ivr-builder {
    display: flex;
    gap: 0;
    border: 1px solid var(--ivr-border);
    border-radius: var(--ivr-radius-lg);
    overflow: hidden;
    background: var(--ivr-surface);
    box-shadow: var(--ivr-shadow);
    height: 800px;
    position: relative;
}

/* Sidebar */
.ivr-sidebar {
    width: 240px;
    min-width: 240px;
    background: rgba(18,18,30,.95);
    border-right: 1px solid var(--ivr-border);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}
.ivr-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid var(--ivr-border);
    font-weight: 700;
    font-size: .85rem;
    color: var(--ivr-text-muted);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ivr-node-list {
    padding: 8px;
    flex: 1;
}
.ivr-node-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: var(--ivr-radius-sm);
    cursor: grab;
    transition: var(--ivr-transition);
    margin-bottom: 4px;
    font-size: .85rem;
    font-weight: 500;
    color: var(--ivr-text);
    border: 1px solid transparent;
    user-select: none;
}
.ivr-node-item:hover {
    background: rgba(108,92,231,.1);
    border-color: rgba(108,92,231,.2);
}
.ivr-node-item:active {
    cursor: grabbing;
}
.ivr-node-item i {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: .9rem;
    flex-shrink: 0;
}
.ivr-node-item[data-type="greeting"] i { background: rgba(0,184,148,.15); color: var(--ivr-green); }
.ivr-node-item[data-type="intent"] i { background: rgba(108,92,231,.15); color: var(--ivr-accent-light); }
.ivr-node-item[data-type="agent"] i { background: rgba(9,132,227,.15); color: var(--ivr-blue); }
.ivr-node-item[data-type="transfer"] i { background: rgba(0,206,201,.15); color: var(--ivr-cyan); }
.ivr-node-item[data-type="voicemail"] i { background: rgba(225,112,85,.15); color: var(--ivr-orange); }
.ivr-node-item[data-type="payment"] i { background: rgba(253,203,110,.15); color: var(--ivr-yellow); }
.ivr-node-item[data-type="sms"] i { background: rgba(162,155,254,.15); color: var(--ivr-accent-light); }
.ivr-node-item[data-type="webhook"] i { background: rgba(9,132,227,.15); color: var(--ivr-blue); }
.ivr-node-item[data-type="schedule"] i { background: rgba(253,121,168,.15); color: var(--ivr-pink); }
.ivr-node-item[data-type="survey"] i { background: rgba(253,203,110,.15); color: var(--ivr-yellow); }
.ivr-node-item[data-type="condition"] i { background: rgba(214,48,49,.15); color: var(--ivr-red); }

/* Canvas */
.ivr-canvas-wrap {
    flex: 1;
    position: relative;
    overflow: auto;
    background:
        radial-gradient(circle at 1px 1px, rgba(255,255,255,.03) 1px, transparent 0);
    background-size: 30px 30px;
}
.ivr-canvas {
    position: relative;
    width: 3000px;
    height: 2000px;
    min-width: 100%;
    min-height: 100%;
}
.ivr-canvas svg.ivr-connections {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}
.ivr-canvas svg.ivr-connections path {
    fill: none;
    stroke: var(--ivr-accent);
    stroke-width: 2;
    stroke-dasharray: 6 4;
    opacity: .6;
}

/* Canvas Nodes */
.ivr-canvas-node {
    position: absolute;
    min-width: 180px;
    background: var(--ivr-surface-2);
    border: 1px solid var(--ivr-border);
    border-radius: var(--ivr-radius);
    padding: 14px 16px;
    cursor: move;
    z-index: 10;
    transition: box-shadow .2s, border-color .2s;
    user-select: none;
}
.ivr-canvas-node:hover {
    border-color: var(--ivr-accent);
    box-shadow: 0 0 20px rgba(108,92,231,.15);
}
.ivr-canvas-node.selected {
    border-color: var(--ivr-accent-light);
    box-shadow: 0 0 30px rgba(108,92,231,.25);
}
.ivr-canvas-node .node-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.ivr-canvas-node .node-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    flex-shrink: 0;
}
.ivr-canvas-node .node-title {
    font-weight: 600;
    font-size: .82rem;
    white-space: nowrap;
}
.ivr-canvas-node .node-desc {
    font-size: .72rem;
    color: var(--ivr-text-muted);
    line-height: 1.4;
}
.ivr-canvas-node .node-port {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--ivr-accent);
    border: 2px solid var(--ivr-surface-2);
    position: absolute;
    cursor: crosshair;
    z-index: 20;
}
.ivr-canvas-node .node-port.port-out {
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
}
.ivr-canvas-node .node-port.port-in {
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
}
.ivr-canvas-node .node-delete {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--ivr-red);
    color: #fff;
    border: none;
    cursor: pointer;
    font-size: .6rem;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 30;
}
.ivr-canvas-node:hover .node-delete {
    display: flex;
}

/* Toolbar */
.ivr-toolbar {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 8px;
    z-index: 50;
}
.ivr-toolbar button {
    padding: 8px 16px;
    border-radius: var(--ivr-radius-sm);
    border: 1px solid var(--ivr-border);
    background: var(--ivr-surface-2);
    color: var(--ivr-text);
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--ivr-transition);
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: inherit;
}
.ivr-toolbar button:hover {
    border-color: var(--ivr-accent);
    background: rgba(108,92,231,.1);
}
.ivr-toolbar .btn-save {
    background: var(--ivr-accent);
    border-color: var(--ivr-accent);
    color: #fff;
}
.ivr-toolbar .btn-save:hover {
    background: #7d6df0;
}

/* Config Panel */
.ivr-config-panel {
    position: absolute;
    top: 0;
    right: -360px;
    width: 360px;
    height: 100%;
    background: rgba(18,18,30,.98);
    border-left: 1px solid var(--ivr-border);
    z-index: 100;
    transition: right .3s ease;
    overflow-y: auto;
    padding: 20px;
    backdrop-filter: blur(12px);
}
.ivr-config-panel.open {
    right: 0;
}
.ivr-config-panel h3 {
    font-size: 1rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.ivr-config-panel .close-config {
    background: none;
    border: none;
    color: var(--ivr-text-muted);
    cursor: pointer;
    font-size: 1rem;
    padding: 4px;
}
.ivr-config-panel .form-group {
    margin-bottom: 16px;
}
.ivr-config-panel label {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    color: var(--ivr-text-muted);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.ivr-config-panel input,
.ivr-config-panel select,
.ivr-config-panel textarea {
    width: 100%;
    padding: 10px 12px;
    border-radius: var(--ivr-radius-sm);
    border: 1px solid var(--ivr-border);
    background: var(--ivr-surface-2);
    color: var(--ivr-text);
    font-size: .85rem;
    font-family: inherit;
    transition: border-color .2s;
}
.ivr-config-panel input:focus,
.ivr-config-panel select:focus,
.ivr-config-panel textarea:focus {
    outline: none;
    border-color: var(--ivr-accent);
}
.ivr-config-panel textarea {
    resize: vertical;
    min-height: 80px;
}
.ivr-config-panel .btn-apply {
    width: 100%;
    padding: 10px;
    border-radius: var(--ivr-radius-sm);
    background: var(--ivr-accent);
    color: #fff;
    border: none;
    font-weight: 600;
    font-size: .85rem;
    cursor: pointer;
    margin-top: 12px;
    font-family: inherit;
}

/* ===== TEMPLATES ===== */
.ivr-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.ivr-template-card {
    background: var(--ivr-surface);
    border: 1px solid var(--ivr-border);
    border-radius: var(--ivr-radius);
    padding: 24px;
    cursor: pointer;
    transition: var(--ivr-transition);
}
.ivr-template-card:hover {
    border-color: var(--ivr-accent);
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(108,92,231,.1);
}
.ivr-template-card .tpl-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 16px;
}
.ivr-template-card h3 {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.ivr-template-card p {
    font-size: .85rem;
    color: var(--ivr-text-muted);
    line-height: 1.5;
    margin-bottom: 16px;
}
.ivr-template-card .tpl-flow {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ivr-template-card .tpl-flow-step {
    font-size: .7rem;
    padding: 4px 10px;
    border-radius: 20px;
    background: rgba(108,92,231,.1);
    color: var(--ivr-accent-light);
    font-weight: 600;
    white-space: nowrap;
}
.ivr-template-card .tpl-flow-arrow {
    color: var(--ivr-text-dim);
    font-size: .7rem;
}
.ivr-template-card .tpl-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 16px;
    font-size: .82rem;
    font-weight: 600;
    color: var(--ivr-accent-light);
}

/* ===== ANALYTICS ===== */
.ivr-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.ivr-stat-card {
    background: var(--ivr-surface);
    border: 1px solid var(--ivr-border);
    border-radius: var(--ivr-radius);
    padding: 24px;
}
.ivr-stat-card .stat-label {
    font-size: .78rem;
    color: var(--ivr-text-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
    margin-bottom: 8px;
}
.ivr-stat-card .stat-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 4px;
}
.ivr-stat-card .stat-change {
    font-size: .8rem;
    font-weight: 600;
}
.ivr-stat-card .stat-change.up { color: var(--ivr-green); }
.ivr-stat-card .stat-change.down { color: var(--ivr-red); }

.ivr-chart-card {
    background: var(--ivr-surface);
    border: 1px solid var(--ivr-border);
    border-radius: var(--ivr-radius);
    padding: 24px;
}
.ivr-chart-card h3 {
    font-size: .95rem;
    font-weight: 700;
    margin-bottom: 20px;
}
.ivr-chart-bar-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ivr-chart-bar {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ivr-chart-bar .bar-label {
    font-size: .78rem;
    color: var(--ivr-text-muted);
    min-width: 100px;
    text-align: right;
}
.ivr-chart-bar .bar-track {
    flex: 1;
    height: 8px;
    background: rgba(255,255,255,.05);
    border-radius: 4px;
    overflow: hidden;
}
.ivr-chart-bar .bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 1s ease;
}
.ivr-chart-bar .bar-value {
    font-size: .78rem;
    font-weight: 600;
    min-width: 50px;
}

/* CTA */
.ivr-cta {
    text-align: center;
    padding: 80px 24px;
    position: relative;
}
.ivr-cta::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(108,92,231,.1), transparent 70%);
    pointer-events: none;
}
.ivr-cta h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 16px;
    position: relative;
}
.ivr-cta p {
    color: var(--ivr-text-muted);
    max-width: 500px;
    margin: 0 auto 28px;
    position: relative;
}
.ivr-cta-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
}

/* Buttons */
.btn-ivr {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: var(--ivr-radius-sm);
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    transition: var(--ivr-transition);
    text-decoration: none;
    border: none;
    font-family: inherit;
}
.btn-ivr-primary {
    background: var(--ivr-accent);
    color: #fff;
}
.btn-ivr-primary:hover {
    background: #7d6df0;
    transform: translateY(-1px);
    color: #fff;
}
.btn-ivr-outline {
    background: transparent;
    border: 1px solid var(--ivr-border);
    color: var(--ivr-text);
}
.btn-ivr-outline:hover {
    border-color: var(--ivr-accent);
    color: var(--ivr-accent-light);
}

/* Responsive */
@media (max-width: 900px) {
    .ivr-builder {
        flex-direction: column;
        height: auto;
    }
    .ivr-sidebar {
        width: 100%;
        min-width: 100%;
        flex-direction: row;
        overflow-x: auto;
        border-right: none;
        border-bottom: 1px solid var(--ivr-border);
    }
    .ivr-sidebar-header {
        display: none;
    }
    .ivr-node-list {
        display: flex;
        gap: 4px;
        padding: 8px;
    }
    .ivr-node-item {
        white-space: nowrap;
        flex-shrink: 0;
    }
    .ivr-canvas-wrap {
        height: 500px;
    }
    .ivr-config-panel {
        width: 100%;
        right: -100%;
    }
}
@media (max-width: 600px) {
    .ivr-hero { padding: 100px 0 40px; }
    .ivr-hero h1 { font-size: 1.8rem; }
    .ivr-templates-grid { grid-template-columns: 1fr; }
    .ivr-analytics-grid { grid-template-columns: 1fr; }
}
</style>

<div class="ivr-page">

    <!-- Hero -->
    <section class="ivr-hero">
        <div class="ivr-container">
            <div class="badge" data-aos="fade-down"><i class="fa-solid fa-diagram-project"></i> Visual Flow Builder</div>
            <h1 data-aos="fade-up">AI-Powered <span class="hl">IVR Builder</span></h1>
            <p data-aos="fade-up" data-aos-delay="100">Design intelligent call flows without coding. Alfred handles intent detection — no more "Press 1 for Sales."</p>
        </div>
    </section>

    <!-- Builder -->
    <section class="ivr-section">
        <div class="ivr-container">
            <div class="ivr-builder" data-aos="fade-up">
                <!-- Sidebar Nodes -->
                <div class="ivr-sidebar">
                    <div class="ivr-sidebar-header"><i class="fa-solid fa-cubes"></i> Nodes</div>
                    <div class="ivr-node-list">
                        <div class="ivr-node-item" draggable="true" data-type="greeting"><i class="fa-solid fa-comment-dots"></i> Greeting</div>
                        <div class="ivr-node-item" draggable="true" data-type="intent"><i class="fa-solid fa-brain"></i> AI Intent Detection</div>
                        <div class="ivr-node-item" draggable="true" data-type="agent"><i class="fa-solid fa-robot"></i> Agent Router</div>
                        <div class="ivr-node-item" draggable="true" data-type="transfer"><i class="fa-solid fa-phone-arrow-right"></i> Transfer</div>
                        <div class="ivr-node-item" draggable="true" data-type="voicemail"><i class="fa-solid fa-voicemail"></i> Voicemail</div>
                        <div class="ivr-node-item" draggable="true" data-type="payment"><i class="fa-solid fa-credit-card"></i> Payment</div>
                        <div class="ivr-node-item" draggable="true" data-type="sms"><i class="fa-solid fa-message"></i> SMS Send</div>
                        <div class="ivr-node-item" draggable="true" data-type="webhook"><i class="fa-solid fa-globe"></i> Webhook</div>
                        <div class="ivr-node-item" draggable="true" data-type="schedule"><i class="fa-solid fa-calendar"></i> Schedule</div>
                        <div class="ivr-node-item" draggable="true" data-type="survey"><i class="fa-solid fa-star"></i> Survey</div>
                        <div class="ivr-node-item" draggable="true" data-type="condition"><i class="fa-solid fa-code-branch"></i> Condition</div>
                    </div>
                </div>

                <!-- Canvas -->
                <div class="ivr-canvas-wrap" id="ivrCanvasWrap">
                    <div class="ivr-canvas" id="ivrCanvas">
                        <svg class="ivr-connections" id="ivrSVG"></svg>
                    </div>
                    <!-- Toolbar -->
                    <div class="ivr-toolbar">
                        <button onclick="IVR.clearCanvas()" title="Clear all"><i class="fa-solid fa-trash"></i> Clear</button>
                        <button onclick="IVR.loadFlow()" title="Load saved flow"><i class="fa-solid fa-folder-open"></i> Load</button>
                        <button class="btn-save" onclick="IVR.saveFlow()" title="Save flow"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                    </div>
                    <!-- Config Panel -->
                    <div class="ivr-config-panel" id="ivrConfigPanel">
                        <h3>
                            <span id="configTitle">Configure Node</span>
                            <button class="close-config" onclick="IVR.closeConfig()"><i class="fa-solid fa-xmark"></i></button>
                        </h3>
                        <div id="configBody">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Templates -->
    <section class="ivr-section">
        <div class="ivr-container">
            <h2 class="ivr-section-title" data-aos="fade-up">Pre-Built Templates</h2>
            <p class="ivr-section-sub" data-aos="fade-up" data-aos-delay="50">Start with a proven flow and customize it to your needs.</p>

            <div class="ivr-templates-grid">
                <!-- Template 1: Customer Support -->
                <div class="ivr-template-card" data-aos="fade-up" data-aos-delay="0" onclick="IVR.loadTemplate('support')">
                    <div class="tpl-icon" style="background:rgba(0,184,148,.15);color:var(--ivr-green)"><i class="fa-solid fa-headset"></i></div>
                    <h3>Customer Support</h3>
                    <p>Full support flow with AI intent detection routing to Support, Sales, or Billing agents with escalation paths.</p>
                    <div class="tpl-flow">
                        <span class="tpl-flow-step">Greeting</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">Intent</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">Agent</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">Escalate</span>
                    </div>
                    <div class="tpl-btn"><i class="fa-solid fa-play"></i> Load Template</div>
                </div>

                <!-- Template 2: Appointment Booking -->
                <div class="ivr-template-card" data-aos="fade-up" data-aos-delay="100" onclick="IVR.loadTemplate('appointment')">
                    <div class="tpl-icon" style="background:rgba(9,132,227,.15);color:var(--ivr-blue)"><i class="fa-solid fa-calendar-check"></i></div>
                    <h3>Appointment Booking</h3>
                    <p>Automated appointment scheduling with AI agent, calendar integration, and SMS confirmation.</p>
                    <div class="tpl-flow">
                        <span class="tpl-flow-step">Greeting</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">Agent</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">Schedule</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">SMS</span>
                    </div>
                    <div class="tpl-btn"><i class="fa-solid fa-play"></i> Load Template</div>
                </div>

                <!-- Template 3: Simple Voicemail -->
                <div class="ivr-template-card" data-aos="fade-up" data-aos-delay="200" onclick="IVR.loadTemplate('voicemail')">
                    <div class="tpl-icon" style="background:rgba(225,112,85,.15);color:var(--ivr-orange)"><i class="fa-solid fa-voicemail"></i></div>
                    <h3>Simple Voicemail</h3>
                    <p>Business hours check with AI agent during open hours and voicemail capture after hours.</p>
                    <div class="tpl-flow">
                        <span class="tpl-flow-step">Greeting</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">Condition</span>
                        <span class="tpl-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                        <span class="tpl-flow-step">Agent / Voicemail</span>
                    </div>
                    <div class="tpl-btn"><i class="fa-solid fa-play"></i> Load Template</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Analytics -->
    <section class="ivr-section">
        <div class="ivr-container">
            <h2 class="ivr-section-title" data-aos="fade-up">IVR Analytics</h2>
            <p class="ivr-section-sub" data-aos="fade-up" data-aos-delay="50">Monitor call flow performance with real-time insights.</p>

            <div class="ivr-analytics-grid" data-aos="fade-up" data-aos-delay="100">
                <div class="ivr-stat-card">
                    <div class="stat-label">Total Calls Today</div>
                    <div class="stat-value" style="color:var(--ivr-accent-light)">2,847</div>
                    <div class="stat-change up"><i class="fa-solid fa-arrow-up"></i> 12.3% vs yesterday</div>
                </div>
                <div class="ivr-stat-card">
                    <div class="stat-label">Avg Handle Time</div>
                    <div class="stat-value" style="color:var(--ivr-green)">2:34</div>
                    <div class="stat-change up"><i class="fa-solid fa-arrow-down"></i> 18% faster</div>
                </div>
                <div class="ivr-stat-card">
                    <div class="stat-label">AI Resolution Rate</div>
                    <div class="stat-value" style="color:var(--ivr-cyan)">87%</div>
                    <div class="stat-change up"><i class="fa-solid fa-arrow-up"></i> 5.2% improvement</div>
                </div>
                <div class="ivr-stat-card">
                    <div class="stat-label">Caller Satisfaction</div>
                    <div class="stat-value" style="color:var(--ivr-yellow)">4.6<span style="font-size:1rem;color:var(--ivr-text-muted)">/5</span></div>
                    <div class="stat-change up"><i class="fa-solid fa-arrow-up"></i> 0.3 points</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px" data-aos="fade-up" data-aos-delay="150">
                <div class="ivr-chart-card">
                    <h3><i class="fa-solid fa-chart-bar" style="color:var(--ivr-accent-light);margin-right:8px"></i> Call Volume (Last 7 Days)</h3>
                    <div class="ivr-chart-bar-group">
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Mon</span>
                            <div class="bar-track"><div class="bar-fill" style="width:72%;background:var(--ivr-accent)"></div></div>
                            <span class="bar-value">2,134</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Tue</span>
                            <div class="bar-track"><div class="bar-fill" style="width:85%;background:var(--ivr-accent)"></div></div>
                            <span class="bar-value">2,521</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Wed</span>
                            <div class="bar-track"><div class="bar-fill" style="width:100%;background:var(--ivr-accent)"></div></div>
                            <span class="bar-value">2,963</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Thu</span>
                            <div class="bar-track"><div class="bar-fill" style="width:78%;background:var(--ivr-accent)"></div></div>
                            <span class="bar-value">2,312</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Fri</span>
                            <div class="bar-track"><div class="bar-fill" style="width:91%;background:var(--ivr-accent)"></div></div>
                            <span class="bar-value">2,698</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Sat</span>
                            <div class="bar-track"><div class="bar-fill" style="width:45%;background:var(--ivr-accent)"></div></div>
                            <span class="bar-value">1,334</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Sun</span>
                            <div class="bar-track"><div class="bar-fill" style="width:38%;background:var(--ivr-accent)"></div></div>
                            <span class="bar-value">1,126</span>
                        </div>
                    </div>
                </div>
                <div class="ivr-chart-card">
                    <h3><i class="fa-solid fa-chart-pie" style="color:var(--ivr-green);margin-right:8px"></i> Drop-off Analysis</h3>
                    <div class="ivr-chart-bar-group">
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Greeting</span>
                            <div class="bar-track"><div class="bar-fill" style="width:5%;background:var(--ivr-green)"></div></div>
                            <span class="bar-value" style="color:var(--ivr-green)">5%</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Intent</span>
                            <div class="bar-track"><div class="bar-fill" style="width:8%;background:var(--ivr-cyan)"></div></div>
                            <span class="bar-value" style="color:var(--ivr-cyan)">8%</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Queue Wait</span>
                            <div class="bar-track"><div class="bar-fill" style="width:22%;background:var(--ivr-orange)"></div></div>
                            <span class="bar-value" style="color:var(--ivr-orange)">22%</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Agent Handling</span>
                            <div class="bar-track"><div class="bar-fill" style="width:12%;background:var(--ivr-yellow)"></div></div>
                            <span class="bar-value" style="color:var(--ivr-yellow)">12%</span>
                        </div>
                        <div class="ivr-chart-bar">
                            <span class="bar-label">Resolved</span>
                            <div class="bar-track"><div class="bar-fill" style="width:87%;background:var(--ivr-accent-light)"></div></div>
                            <span class="bar-value" style="color:var(--ivr-accent-light)">87%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="ivr-cta">
        <h2 data-aos="fade-up">Ready to Build Your IVR?</h2>
        <p data-aos="fade-up" data-aos-delay="50">Get started for free. No credit card required.</p>
        <div class="ivr-cta-buttons" data-aos="fade-up" data-aos-delay="100">
            <a href="/pricing.php" class="btn-ivr btn-ivr-primary"><i class="fa-solid fa-rocket"></i> Start Free Trial</a>
            <a href="/voice.php" class="btn-ivr btn-ivr-outline"><i class="fa-solid fa-phone"></i> Try Voice Demo</a>
        </div>
    </section>

</div>

<script src="/assets/js/ivr-builder-engine.js"></script>


<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
