<?php
$page_title       = 'AI Outbound Calling Campaigns — Automated Call Campaigns | GoSiteMe';
$page_description = 'Let Alfred make calls for you. Upload contacts, configure your AI agent, set calling schedules, and launch outbound campaigns with full CRTC/TCPA compliance.';
$page_canonical   = 'https://gositeme.com/call-campaigns.php';
$page_og_title    = 'AI Outbound Calling Campaigns — Let Alfred Call for You';
$page_og_description = 'Upload contacts, configure your AI agent, and launch outbound calling campaigns. CRTC/TCPA compliant.';
$page_twitter_description = 'AI outbound calling campaigns with full compliance. Upload, configure, launch.';
include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>

<style>
/* ===== CALL CAMPAIGNS — SCOPED STYLES ===== */
.cc-page {
    --cc-bg: #0a0a14;
    --cc-surface: #12121e;
    --cc-surface-2: #1a1a2e;
    --cc-surface-3: #222240;
    --cc-border: rgba(255,255,255,.06);
    --cc-border-hover: rgba(255,255,255,.12);
    --cc-text: #e4e4ec;
    --cc-text-muted: #8892b0;
    --cc-text-dim: #5a6380;
    --cc-accent: #6c5ce7;
    --cc-accent-light: #a29bfe;
    --cc-blue: #0984e3;
    --cc-green: #00b894;
    --cc-cyan: #00cec9;
    --cc-orange: #e17055;
    --cc-red: #d63031;
    --cc-yellow: #fdcb6e;
    --cc-pink: #fd79a8;
    --cc-radius: 14px;
    --cc-radius-sm: 10px;
    --cc-radius-lg: 18px;
    --cc-shadow: 0 4px 24px rgba(0,0,0,.3);
    --cc-transition: all .25s cubic-bezier(.4,0,.2,1);
}

/* Hero */
.cc-hero {
    padding: 120px 0 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.cc-hero::before {
    content: '';
    position: absolute;
    top: -300px;
    left: 50%;
    transform: translateX(-50%);
    width: 900px;
    height: 900px;
    background: radial-gradient(circle, rgba(9,132,227,.2) 0%, rgba(108,92,231,.1) 40%, transparent 70%);
    pointer-events: none;
    animation: ccGlow 6s ease-in-out infinite;
}
@keyframes ccGlow {
    0%,100% { opacity:.4; transform:translateX(-50%) scale(1); }
    50% { opacity:.7; transform:translateX(-50%) scale(1.06); }
}
.cc-hero .badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 100px;
    background: linear-gradient(135deg, rgba(9,132,227,.2), rgba(108,92,231,.15));
    border: 1px solid rgba(9,132,227,.3);
    color: var(--cc-blue);
    font-size: .85rem;
    font-weight: 600;
    margin-bottom: 24px;
    letter-spacing: .5px;
}
.cc-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 20px;
    position: relative;
}
.cc-hero h1 .hl {
    background: linear-gradient(135deg, var(--cc-blue), var(--cc-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.cc-hero p {
    font-size: 1.15rem;
    color: var(--cc-text-muted);
    max-width: 640px;
    margin: 0 auto;
    line-height: 1.7;
}

/* Container */
.cc-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}
.cc-section {
    padding: 60px 0;
}
.cc-section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 12px;
    text-align: center;
}
.cc-section-sub {
    color: var(--cc-text-muted);
    text-align: center;
    max-width: 600px;
    margin: 0 auto 40px;
    font-size: 1rem;
}

/* Login prompt */
.cc-login-prompt {
    text-align: center;
    padding: 48px;
    background: var(--cc-surface);
    border: 1px solid var(--cc-border);
    border-radius: var(--cc-radius-lg);
    max-width: 600px;
    margin: 0 auto;
}
.cc-login-prompt i.lock-icon {
    font-size: 2.5rem;
    color: var(--cc-accent);
    margin-bottom: 16px;
    display: block;
}
.cc-login-prompt h3 {
    font-size: 1.2rem;
    margin-bottom: 12px;
}
.cc-login-prompt p {
    color: var(--cc-text-muted);
    margin-bottom: 24px;
}

/* ===== CAMPAIGN BUILDER ===== */
.cc-builder {
    background: var(--cc-surface);
    border: 1px solid var(--cc-border);
    border-radius: var(--cc-radius-lg);
    overflow: hidden;
    box-shadow: var(--cc-shadow);
}

/* Stepper */
.cc-stepper {
    display: flex;
    border-bottom: 1px solid var(--cc-border);
    overflow-x: auto;
}
.cc-step-tab {
    flex: 1;
    padding: 16px 20px;
    text-align: center;
    cursor: pointer;
    transition: var(--cc-transition);
    position: relative;
    min-width: 140px;
    border: none;
    background: none;
    color: var(--cc-text-dim);
    font-family: inherit;
    font-size: .85rem;
    font-weight: 600;
}
.cc-step-tab:hover {
    color: var(--cc-text);
    background: rgba(255,255,255,.02);
}
.cc-step-tab.active {
    color: var(--cc-accent-light);
    background: rgba(108,92,231,.05);
}
.cc-step-tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--cc-accent);
}
.cc-step-tab .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    font-size: .72rem;
    font-weight: 700;
    margin-right: 8px;
}
.cc-step-tab.active .step-num {
    background: var(--cc-accent);
    color: #fff;
}
.cc-step-tab.completed .step-num {
    background: var(--cc-green);
    color: #fff;
}

/* Step Content */
.cc-step-content {
    padding: 32px;
    display: none;
}
.cc-step-content.active {
    display: block;
}

/* Step 1: Upload */
.cc-upload-zone {
    border: 2px dashed var(--cc-border);
    border-radius: var(--cc-radius);
    padding: 60px 40px;
    text-align: center;
    cursor: pointer;
    transition: var(--cc-transition);
    position: relative;
}
.cc-upload-zone:hover,
.cc-upload-zone.dragover {
    border-color: var(--cc-accent);
    background: rgba(108,92,231,.05);
}
.cc-upload-zone i {
    font-size: 2.5rem;
    color: var(--cc-accent);
    margin-bottom: 16px;
    display: block;
}
.cc-upload-zone h3 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}
.cc-upload-zone p {
    color: var(--cc-text-muted);
    font-size: .85rem;
    margin-bottom: 16px;
}
.cc-upload-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}
.cc-upload-zone .file-formats {
    font-size: .75rem;
    color: var(--cc-text-dim);
}

/* Column Mapping */
.cc-column-map {
    margin-top: 24px;
    display: none;
}
.cc-column-map.visible {
    display: block;
}
.cc-column-map h4 {
    font-size: .95rem;
    margin-bottom: 16px;
}
.cc-map-table {
    width: 100%;
    border-collapse: collapse;
}
.cc-map-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--cc-text-dim);
    border-bottom: 1px solid var(--cc-border);
}
.cc-map-table td {
    padding: 10px 12px;
    border-bottom: 1px solid rgba(255,255,255,.03);
}
.cc-map-table select {
    width: 100%;
    padding: 8px 10px;
    border-radius: var(--cc-radius-sm);
    border: 1px solid var(--cc-border);
    background: var(--cc-surface-2);
    color: var(--cc-text);
    font-size: .82rem;
    font-family: inherit;
}
.cc-map-table .preview {
    font-size: .78rem;
    color: var(--cc-text-muted);
    font-family: monospace;
}
.cc-upload-summary {
    margin-top: 16px;
    padding: 12px 16px;
    background: rgba(0,184,148,.06);
    border: 1px solid rgba(0,184,148,.15);
    border-radius: var(--cc-radius-sm);
    font-size: .85rem;
    color: var(--cc-green);
    font-weight: 600;
    display: none;
}
.cc-upload-summary.visible { display: flex; align-items: center; gap: 8px; }

/* Step 2: Agent */
.cc-agent-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.cc-agent-card {
    background: var(--cc-surface-2);
    border: 2px solid var(--cc-border);
    border-radius: var(--cc-radius);
    padding: 20px;
    cursor: pointer;
    transition: var(--cc-transition);
}
.cc-agent-card:hover,
.cc-agent-card.selected {
    border-color: var(--cc-accent);
}
.cc-agent-card.selected {
    background: rgba(108,92,231,.06);
}
.cc-agent-card .agent-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    margin-bottom: 12px;
}
.cc-agent-card h4 {
    font-size: .92rem;
    font-weight: 700;
    margin-bottom: 4px;
}
.cc-agent-card p {
    font-size: .78rem;
    color: var(--cc-text-muted);
}

.cc-script-editor {
    margin-top: 24px;
}
.cc-script-editor label {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    color: var(--cc-text-muted);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.cc-script-editor textarea {
    width: 100%;
    min-height: 140px;
    padding: 16px;
    border-radius: var(--cc-radius);
    border: 1px solid var(--cc-border);
    background: var(--cc-surface-2);
    color: var(--cc-text);
    font-size: .88rem;
    font-family: inherit;
    resize: vertical;
    line-height: 1.6;
}
.cc-script-editor textarea:focus {
    outline: none;
    border-color: var(--cc-accent);
}
.cc-script-vars {
    margin-top: 8px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.cc-script-vars .var-tag {
    font-size: .72rem;
    padding: 3px 10px;
    border-radius: 20px;
    background: rgba(108,92,231,.1);
    color: var(--cc-accent-light);
    font-weight: 600;
    cursor: pointer;
    transition: var(--cc-transition);
}
.cc-script-vars .var-tag:hover {
    background: rgba(108,92,231,.2);
}

/* Step 3: Schedule */
.cc-schedule-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 700px) {
    .cc-schedule-grid { grid-template-columns: 1fr; }
}
.cc-form-group {
    margin-bottom: 20px;
}
.cc-form-group label {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    color: var(--cc-text-muted);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.cc-form-group input,
.cc-form-group select {
    width: 100%;
    padding: 10px 12px;
    border-radius: var(--cc-radius-sm);
    border: 1px solid var(--cc-border);
    background: var(--cc-surface-2);
    color: var(--cc-text);
    font-size: .85rem;
    font-family: inherit;
}
.cc-form-group input:focus,
.cc-form-group select:focus {
    outline: none;
    border-color: var(--cc-accent);
}
.cc-form-group .hint {
    font-size: .72rem;
    color: var(--cc-text-dim);
    margin-top: 4px;
}
.cc-pacing {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cc-pacing input[type="range"] {
    flex: 1;
    accent-color: var(--cc-accent);
    background: transparent;
    border: none;
    padding: 0;
}
.cc-pacing .pacing-value {
    font-family: 'Space Grotesk', monospace;
    font-weight: 700;
    font-size: 1.1rem;
    min-width: 30px;
    text-align: center;
}

/* Retry rules */
.cc-retry-rules {
    background: var(--cc-surface-2);
    border: 1px solid var(--cc-border);
    border-radius: var(--cc-radius);
    padding: 20px;
}
.cc-retry-rules h4 {
    font-size: .88rem;
    font-weight: 700;
    margin-bottom: 12px;
}
.cc-retry-rule {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,.03);
    font-size: .82rem;
}
.cc-retry-rule:last-child { border-bottom: none; }
.cc-retry-rule label {
    min-width: 100px;
    color: var(--cc-text-muted);
    font-size: .78rem;
    text-transform: none;
    letter-spacing: 0;
    margin-bottom: 0;
}
.cc-retry-rule select {
    padding: 6px 10px;
    border-radius: var(--cc-radius-sm);
    border: 1px solid var(--cc-border);
    background: var(--cc-surface-3);
    color: var(--cc-text);
    font-size: .78rem;
    font-family: inherit;
}

/* Step 4: Review */
.cc-review-summary {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.cc-review-item {
    background: var(--cc-surface-2);
    border: 1px solid var(--cc-border);
    border-radius: var(--cc-radius);
    padding: 20px;
}
.cc-review-item .ri-label {
    font-size: .72rem;
    color: var(--cc-text-dim);
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
    margin-bottom: 6px;
}
.cc-review-item .ri-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
}
.cc-review-item .ri-note {
    font-size: .75rem;
    color: var(--cc-text-muted);
    margin-top: 4px;
}
.cc-launch-area {
    text-align: center;
    padding: 24px;
    background: rgba(108,92,231,.04);
    border: 1px solid rgba(108,92,231,.15);
    border-radius: var(--cc-radius);
}
.cc-launch-area p {
    color: var(--cc-text-muted);
    font-size: .85rem;
    margin-bottom: 16px;
}

/* Step Navigation */
.cc-step-nav {
    display: flex;
    justify-content: space-between;
    padding: 20px 32px;
    border-top: 1px solid var(--cc-border);
}

/* ===== CAMPAIGN DASHBOARD ===== */
.cc-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
}
.cc-campaign-card {
    background: var(--cc-surface);
    border: 1px solid var(--cc-border);
    border-radius: var(--cc-radius);
    padding: 24px;
    transition: var(--cc-transition);
}
.cc-campaign-card:hover {
    border-color: var(--cc-accent);
}
.cc-campaign-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.cc-campaign-name {
    font-size: 1.05rem;
    font-weight: 700;
}
.cc-campaign-status {
    font-size: .7rem;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.cc-campaign-status.active { background: rgba(0,184,148,.12); color: var(--cc-green); }
.cc-campaign-status.paused { background: rgba(253,203,110,.12); color: var(--cc-yellow); }
.cc-campaign-status.completed { background: rgba(108,92,231,.12); color: var(--cc-accent-light); }

.cc-progress-bar {
    height: 6px;
    background: rgba(255,255,255,.05);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 12px;
}
.cc-progress-bar .fill {
    height: 100%;
    border-radius: 3px;
    background: linear-gradient(90deg, var(--cc-accent), var(--cc-blue));
    transition: width 1s ease;
}

.cc-campaign-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 16px;
}
.cc-campaign-stat {
    text-align: center;
    padding: 8px;
    background: var(--cc-surface-2);
    border-radius: var(--cc-radius-sm);
}
.cc-campaign-stat .val {
    font-family: 'Space Grotesk', sans-serif;
    font-size: .95rem;
    font-weight: 700;
}
.cc-campaign-stat .lbl {
    font-size: .65rem;
    color: var(--cc-text-dim);
    text-transform: uppercase;
    letter-spacing: .3px;
}

.cc-campaign-metrics {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.cc-metric-pill {
    font-size: .7rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.cc-campaign-actions {
    display: flex;
    gap: 8px;
}
.cc-campaign-actions button {
    padding: 6px 14px;
    border-radius: var(--cc-radius-sm);
    border: 1px solid var(--cc-border);
    background: transparent;
    color: var(--cc-text);
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--cc-transition);
    font-family: inherit;
    display: flex;
    align-items: center;
    gap: 4px;
}
.cc-campaign-actions button:hover {
    border-color: var(--cc-accent);
    color: var(--cc-accent-light);
}
.cc-campaign-actions .btn-pause { border-color: var(--cc-yellow); color: var(--cc-yellow); }
.cc-campaign-actions .btn-resume { background: var(--cc-green); border-color: var(--cc-green); color: #fff; }

/* ===== COMPLIANCE ===== */
.cc-compliance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}
.cc-compliance-card {
    background: var(--cc-surface);
    border: 1px solid var(--cc-border);
    border-radius: var(--cc-radius);
    padding: 24px;
    transition: var(--cc-transition);
}
.cc-compliance-card:hover {
    border-color: rgba(0,184,148,.2);
}
.cc-compliance-card .comp-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 16px;
}
.cc-compliance-card h3 {
    font-size: .92rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.cc-compliance-card p {
    font-size: .78rem;
    color: var(--cc-text-muted);
    line-height: 1.6;
}

/* ===== PRICING ===== */
.cc-pricing {
    text-align: center;
    background: var(--cc-surface);
    border: 1px solid var(--cc-border);
    border-radius: var(--cc-radius-lg);
    padding: 48px;
    max-width: 600px;
    margin: 0 auto;
}
.cc-pricing .price {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 3rem;
    font-weight: 800;
    margin: 16px 0 8px;
}
.cc-pricing .price span {
    font-size: 1rem;
    color: var(--cc-text-muted);
    font-weight: 400;
}
.cc-pricing .price-note {
    font-size: .85rem;
    color: var(--cc-text-muted);
    margin-bottom: 8px;
}
.cc-pricing .ent-note {
    font-size: .82rem;
    color: var(--cc-green);
    font-weight: 600;
    margin-bottom: 24px;
}
.cc-pricing ul {
    list-style: none;
    text-align: left;
    max-width: 340px;
    margin: 0 auto 28px;
}
.cc-pricing ul li {
    padding: 8px 0;
    font-size: .88rem;
    color: var(--cc-text-muted);
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,.03);
}
.cc-pricing ul li i { color: var(--cc-green); flex-shrink: 0; }

/* Buttons */
.btn-cc {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: var(--cc-radius-sm);
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    transition: var(--cc-transition);
    text-decoration: none;
    border: none;
    font-family: inherit;
}
.btn-cc-primary {
    background: var(--cc-accent);
    color: #fff;
}
.btn-cc-primary:hover {
    background: #7d6df0;
    transform: translateY(-1px);
    color: #fff;
}
.btn-cc-outline {
    background: transparent;
    border: 1px solid var(--cc-border);
    color: var(--cc-text);
}
.btn-cc-outline:hover {
    border-color: var(--cc-accent);
    color: var(--cc-accent-light);
}
.btn-cc-green {
    background: var(--cc-green);
    color: #fff;
}
.btn-cc-green:hover {
    background: #00d6a4;
    color: #fff;
}

/* CTA */
.cc-cta {
    text-align: center;
    padding: 80px 24px;
    position: relative;
}
.cc-cta h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 16px;
}
.cc-cta p {
    color: var(--cc-text-muted);
    max-width: 500px;
    margin: 0 auto 28px;
}

/* Responsive */
@media (max-width: 700px) {
    .cc-hero { padding: 100px 0 40px; }
    .cc-hero h1 { font-size: 1.8rem; }
    .cc-step-content { padding: 20px; }
    .cc-step-nav { padding: 16px 20px; }
    .cc-review-summary { grid-template-columns: 1fr 1fr; }
    .cc-dashboard-grid { grid-template-columns: 1fr; }
    .cc-compliance-grid { grid-template-columns: 1fr; }
    .cc-agent-options { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .cc-hero { padding: 80px 0 30px; }
    .cc-hero h1 { font-size: 1.4rem; }
    .cc-hero p { font-size: 0.9rem; }
    .cc-hero .badge { font-size: 0.7rem; padding: 6px 12px; }
    .cc-section-title { font-size: 1.2rem; }
    .cc-section-sub { font-size: 0.85rem; }
    .cc-campaign-stats { grid-template-columns: 1fr; gap: 6px; }
    .cc-campaign-stat .val { font-size: 0.85rem; }
    .cc-review-summary { grid-template-columns: 1fr; gap: 12px; }
    .cc-review-item { padding: 14px; }
    .cc-review-item .ri-value { font-size: 1.1rem; }
    .cc-step-content { padding: 16px; }
    .cc-step-nav { padding: 12px 16px; flex-wrap: wrap; gap: 8px; }
    .cc-stepper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .cc-step-tab { font-size: 0.75rem; padding: 10px 12px; white-space: nowrap; }
    .cc-form-group label { font-size: 0.72rem; }
    .cc-form-group input, .cc-form-group select { font-size: 0.8rem; padding: 8px 10px; }
    .cc-campaign-card { padding: 16px; }
    .cc-campaign-name { font-size: 0.95rem; }
    .cc-cta { padding: 50px 16px; }
    .cc-cta h2 { font-size: 1.5rem; }
    .cc-schedule-grid { grid-template-columns: 1fr; }
    .cc-upload-zone { padding: 24px 16px; }
}
@media (pointer: coarse) {
    .cc-step-tab { min-height: 44px; }
    .cc-form-group input, .cc-form-group select { min-height: 44px; }
    .btn-cc, .btn-cc-green { min-height: 44px; }
}
</style>

<div class="cc-page">

    <!-- Hero -->
    <section class="cc-hero">
        <div class="cc-container">
            <div class="badge" data-aos="fade-down"><i class="fa-solid fa-phone-volume"></i> Outbound Campaigns</div>
            <h1 data-aos="fade-up">AI Outbound <span class="hl">Calling Campaigns</span></h1>
            <p data-aos="fade-up" data-aos-delay="100">Let Alfred make calls for you. Upload contacts, configure your agent, and launch.</p>
        </div>
    </section>

    <!-- Campaign Builder -->
    <section class="cc-section">
        <div class="cc-container">
            <h2 class="cc-section-title" data-aos="fade-up">Campaign Builder</h2>
            <p class="cc-section-sub" data-aos="fade-up" data-aos-delay="50">Set up your outbound calling campaign in 4 simple steps.</p>

            <?php if ($is_logged_in): ?>
            <div class="cc-builder" data-aos="fade-up" data-aos-delay="100">
                <!-- Stepper -->
                <div class="cc-stepper">
                    <button class="cc-step-tab active" data-step="1" onclick="Campaign.goStep(1)"><span class="step-num">1</span> Upload Contacts</button>
                    <button class="cc-step-tab" data-step="2" onclick="Campaign.goStep(2)"><span class="step-num">2</span> Configure Agent</button>
                    <button class="cc-step-tab" data-step="3" onclick="Campaign.goStep(3)"><span class="step-num">3</span> Schedule</button>
                    <button class="cc-step-tab" data-step="4" onclick="Campaign.goStep(4)"><span class="step-num">4</span> Review &amp; Launch</button>
                </div>

                <!-- Step 1: Upload -->
                <div class="cc-step-content active" id="ccStep1">
                    <h3 style="font-size:1.1rem;margin-bottom:20px"><i class="fa-solid fa-upload" style="color:var(--cc-accent);margin-right:8px"></i> Upload Contact List</h3>
                    <div class="cc-upload-zone" id="ccUploadZone">
                        <input type="file" id="ccFileInput" accept=".csv,.txt" onchange="Campaign.handleFile(event)">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <h3>Drop your CSV file here</h3>
                        <p>or click to browse files</p>
                        <div class="file-formats">Supported: CSV, TXT — Max 50,000 contacts</div>
                    </div>
                    <div class="cc-upload-summary" id="ccUploadSummary">
                        <i class="fa-solid fa-check-circle"></i>
                        <span id="ccUploadInfo"></span>
                    </div>
                    <div class="cc-column-map" id="ccColumnMap">
                        <h4><i class="fa-solid fa-table-columns" style="color:var(--cc-blue);margin-right:8px"></i> Map Your Columns</h4>
                        <table class="cc-map-table">
                            <thead>
                                <tr>
                                    <th>Required Field</th>
                                    <th>Your Column</th>
                                    <th>Preview</th>
                                </tr>
                            </thead>
                            <tbody id="ccMapBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Step 2: Agent -->
                <div class="cc-step-content" id="ccStep2">
                    <h3 style="font-size:1.1rem;margin-bottom:20px"><i class="fa-solid fa-robot" style="color:var(--cc-accent);margin-right:8px"></i> Choose or Configure Agent</h3>
                    <div class="cc-agent-options">
                        <div class="cc-agent-card selected" onclick="Campaign.selectAgent(this, 'sales')">
                            <div class="agent-icon" style="background:rgba(9,132,227,.12);color:var(--cc-blue)"><i class="fa-solid fa-chart-line"></i></div>
                            <h4>Sales Agent</h4>
                            <p>Optimized for sales outreach, lead qualification, and appointment setting.</p>
                        </div>
                        <div class="cc-agent-card" onclick="Campaign.selectAgent(this, 'survey')">
                            <div class="agent-icon" style="background:rgba(253,203,110,.12);color:var(--cc-yellow)"><i class="fa-solid fa-clipboard-list"></i></div>
                            <h4>Survey Agent</h4>
                            <p>Conducts phone surveys with natural conversation and response collection.</p>
                        </div>
                        <div class="cc-agent-card" onclick="Campaign.selectAgent(this, 'reminder')">
                            <div class="agent-icon" style="background:rgba(0,184,148,.12);color:var(--cc-green)"><i class="fa-solid fa-bell"></i></div>
                            <h4>Reminder Agent</h4>
                            <p>Appointment reminders, payment reminders, and follow-up calls.</p>
                        </div>
                        <div class="cc-agent-card" onclick="Campaign.selectAgent(this, 'custom')">
                            <div class="agent-icon" style="background:rgba(108,92,231,.12);color:var(--cc-accent-light)"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                            <h4>Custom Agent</h4>
                            <p>Build a custom agent with your own script and behavior rules.</p>
                        </div>
                    </div>
                    <div class="cc-script-editor">
                        <label>Call Script Template</label>
                        <textarea id="ccScriptText">Hi {name}, this is Alfred calling from {company}. I'm reaching out to you regarding {reason}. Do you have a moment to chat?</textarea>
                        <div class="cc-script-vars">
                            <span>Available variables:</span>
                            <span class="var-tag" onclick="Campaign.insertVar('{name}')">{name}</span>
                            <span class="var-tag" onclick="Campaign.insertVar('{company}')">{company}</span>
                            <span class="var-tag" onclick="Campaign.insertVar('{phone}')">{phone}</span>
                            <span class="var-tag" onclick="Campaign.insertVar('{email}')">{email}</span>
                            <span class="var-tag" onclick="Campaign.insertVar('{reason}')">{reason}</span>
                            <span class="var-tag" onclick="Campaign.insertVar('{date}')">{date}</span>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Schedule -->
                <div class="cc-step-content" id="ccStep3">
                    <h3 style="font-size:1.1rem;margin-bottom:20px"><i class="fa-solid fa-clock" style="color:var(--cc-accent);margin-right:8px"></i> Schedule &amp; Pacing</h3>
                    <div class="cc-schedule-grid">
                        <div>
                            <div class="cc-form-group">
                                <label>Timezone</label>
                                <select id="ccTimezone">
                                    <option>America/New_York (EST)</option>
                                    <option>America/Chicago (CST)</option>
                                    <option>America/Denver (MST)</option>
                                    <option>America/Los_Angeles (PST)</option>
                                    <option>America/Toronto (EST)</option>
                                    <option>America/Vancouver (PST)</option>
                                    <option>Europe/London (GMT)</option>
                                    <option>Europe/Paris (CET)</option>
                                </select>
                            </div>
                            <div class="cc-form-group">
                                <label>Calling Window — Start</label>
                                <input type="time" id="ccStartTime" value="09:00">
                                <div class="hint">Earliest time to start making calls</div>
                            </div>
                            <div class="cc-form-group">
                                <label>Calling Window — End</label>
                                <input type="time" id="ccEndTime" value="17:00">
                                <div class="hint">Latest time to make calls</div>
                            </div>
                            <div class="cc-form-group">
                                <label>Start Date</label>
                                <input type="date" id="ccStartDate" value="2026-03-05">
                            </div>
                        </div>
                        <div>
                            <div class="cc-form-group">
                                <label>Concurrent Calls (Pacing)</label>
                                <div class="cc-pacing">
                                    <input type="range" id="ccPacing" min="1" max="10" value="3" oninput="document.getElementById('ccPacingVal').textContent=this.value">
                                    <span class="pacing-value" id="ccPacingVal">3</span>
                                </div>
                                <div class="hint">Number of simultaneous outbound calls</div>
                            </div>
                            <div class="cc-retry-rules">
                                <h4><i class="fa-solid fa-rotate" style="color:var(--cc-orange);margin-right:6px"></i> Retry Rules</h4>
                                <div class="cc-retry-rule">
                                    <label>No Answer</label>
                                    <select>
                                        <option>Retry after 2 hours</option>
                                        <option>Retry after 4 hours</option>
                                        <option>Retry next day</option>
                                        <option>Do not retry</option>
                                    </select>
                                </div>
                                <div class="cc-retry-rule">
                                    <label>Busy</label>
                                    <select>
                                        <option>Retry after 30 min</option>
                                        <option>Retry after 1 hour</option>
                                        <option>Retry after 2 hours</option>
                                        <option>Do not retry</option>
                                    </select>
                                </div>
                                <div class="cc-retry-rule">
                                    <label>Voicemail</label>
                                    <select>
                                        <option>Leave message, no retry</option>
                                        <option>Retry after 4 hours</option>
                                        <option>Retry next day</option>
                                        <option>Do not retry</option>
                                    </select>
                                </div>
                                <div class="cc-retry-rule">
                                    <label>Max Retries</label>
                                    <select>
                                        <option>3 attempts total</option>
                                        <option>2 attempts total</option>
                                        <option>5 attempts total</option>
                                        <option>1 attempt only</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div class="cc-step-content" id="ccStep4">
                    <h3 style="font-size:1.1rem;margin-bottom:20px"><i class="fa-solid fa-clipboard-check" style="color:var(--cc-accent);margin-right:8px"></i> Review &amp; Launch</h3>
                    <div class="cc-review-summary">
                        <div class="cc-review-item">
                            <div class="ri-label">Total Contacts</div>
                            <div class="ri-value" id="ccRevContacts" style="color:var(--cc-accent-light)">0</div>
                            <div class="ri-note">From uploaded CSV</div>
                        </div>
                        <div class="cc-review-item">
                            <div class="ri-label">Agent Type</div>
                            <div class="ri-value" id="ccRevAgent" style="color:var(--cc-blue)">Sales</div>
                            <div class="ri-note">AI-powered agent</div>
                        </div>
                        <div class="cc-review-item">
                            <div class="ri-label">Calling Window</div>
                            <div class="ri-value" id="ccRevWindow" style="color:var(--cc-green)">9-5</div>
                            <div class="ri-note" id="ccRevTZ">EST</div>
                        </div>
                        <div class="cc-review-item">
                            <div class="ri-label">Concurrent Calls</div>
                            <div class="ri-value" id="ccRevPacing" style="color:var(--cc-orange)">3</div>
                            <div class="ri-note">Simultaneous</div>
                        </div>
                        <div class="cc-review-item">
                            <div class="ri-label">Est. Duration</div>
                            <div class="ri-value" id="ccRevDuration" style="color:var(--cc-pink)">—</div>
                            <div class="ri-note">Based on settings</div>
                        </div>
                        <div class="cc-review-item">
                            <div class="ri-label">Est. Cost</div>
                            <div class="ri-value" id="ccRevCost" style="color:var(--cc-yellow)">—</div>
                            <div class="ri-note">$0.03/min outbound</div>
                        </div>
                    </div>
                    <div class="cc-launch-area">
                        <p><i class="fa-solid fa-shield-check" style="color:var(--cc-green);margin-right:6px"></i> Your campaign is CRTC/TCPA compliant. Opt-out will be offered on every call.</p>
                        <button class="btn-cc btn-cc-green" onclick="Campaign.launch()" id="ccLaunchBtn" style="font-size:1rem;padding:14px 40px">
                            <i class="fa-solid fa-rocket"></i> Launch Campaign
                        </button>
                    </div>
                </div>

                <!-- Step Navigation -->
                <div class="cc-step-nav">
                    <button class="btn-cc btn-cc-outline" id="ccPrevBtn" onclick="Campaign.prev()" style="display:none"><i class="fa-solid fa-arrow-left"></i> Back</button>
                    <button class="btn-cc btn-cc-primary" id="ccNextBtn" onclick="Campaign.next()">Next <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>
            <?php else: ?>
            <div class="cc-login-prompt" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-lock lock-icon"></i>
                <h3>Sign In to Build Campaigns</h3>
                <p>You need to be logged in to create and manage outbound calling campaigns.</p>
                <a href="/login" class="btn-cc btn-cc-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Campaign Dashboard -->
    <section class="cc-section">
        <div class="cc-container">
            <h2 class="cc-section-title" data-aos="fade-up">Campaign Dashboard</h2>
            <p class="cc-section-sub" data-aos="fade-up" data-aos-delay="50">Monitor active campaigns and track results in real time.</p>

            <div class="cc-dashboard-grid" data-aos="fade-up" data-aos-delay="100">
                <!-- Demo Campaign 1 -->
                <div class="cc-campaign-card">
                    <div class="cc-campaign-header">
                        <div class="cc-campaign-name">Q1 Lead Outreach</div>
                        <span class="cc-campaign-status active">Active</span>
                    </div>
                    <div class="cc-progress-bar"><div class="fill" style="width:67%"></div></div>
                    <div class="cc-campaign-stats">
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-accent-light)">1,340</div>
                            <div class="lbl">Calls Made</div>
                        </div>
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-blue)">2,000</div>
                            <div class="lbl">Total</div>
                        </div>
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-green)">23%</div>
                            <div class="lbl">Conversion</div>
                        </div>
                    </div>
                    <div class="cc-campaign-metrics">
                        <span class="cc-metric-pill" style="background:rgba(0,184,148,.08);color:var(--cc-green)"><i class="fa-solid fa-phone"></i> 892 Connected</span>
                        <span class="cc-metric-pill" style="background:rgba(225,112,85,.08);color:var(--cc-orange)"><i class="fa-solid fa-voicemail"></i> 312 Voicemail</span>
                        <span class="cc-metric-pill" style="background:rgba(255,255,255,.04);color:var(--cc-text-dim)"><i class="fa-solid fa-phone-slash"></i> 136 No Answer</span>
                    </div>
                    <div class="cc-campaign-actions">
                        <button class="btn-pause"><i class="fa-solid fa-pause"></i> Pause</button>
                        <button><i class="fa-solid fa-chart-bar"></i> Details</button>
                    </div>
                </div>

                <!-- Demo Campaign 2 -->
                <div class="cc-campaign-card">
                    <div class="cc-campaign-header">
                        <div class="cc-campaign-name">Appointment Reminders</div>
                        <span class="cc-campaign-status paused">Paused</span>
                    </div>
                    <div class="cc-progress-bar"><div class="fill" style="width:42%"></div></div>
                    <div class="cc-campaign-stats">
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-accent-light)">210</div>
                            <div class="lbl">Calls Made</div>
                        </div>
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-blue)">500</div>
                            <div class="lbl">Total</div>
                        </div>
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-green)">89%</div>
                            <div class="lbl">Confirmed</div>
                        </div>
                    </div>
                    <div class="cc-campaign-metrics">
                        <span class="cc-metric-pill" style="background:rgba(0,184,148,.08);color:var(--cc-green)"><i class="fa-solid fa-phone"></i> 187 Connected</span>
                        <span class="cc-metric-pill" style="background:rgba(253,203,110,.08);color:var(--cc-yellow)"><i class="fa-solid fa-phone-flip"></i> 15 Busy</span>
                        <span class="cc-metric-pill" style="background:rgba(255,255,255,.04);color:var(--cc-text-dim)"><i class="fa-solid fa-phone-slash"></i> 8 No Answer</span>
                    </div>
                    <div class="cc-campaign-actions">
                        <button class="btn-resume"><i class="fa-solid fa-play"></i> Resume</button>
                        <button><i class="fa-solid fa-chart-bar"></i> Details</button>
                    </div>
                </div>

                <!-- Demo Campaign 3 -->
                <div class="cc-campaign-card">
                    <div class="cc-campaign-header">
                        <div class="cc-campaign-name">Customer Satisfaction Survey</div>
                        <span class="cc-campaign-status completed">Completed</span>
                    </div>
                    <div class="cc-progress-bar"><div class="fill" style="width:100%"></div></div>
                    <div class="cc-campaign-stats">
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-accent-light)">750</div>
                            <div class="lbl">Calls Made</div>
                        </div>
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-blue)">750</div>
                            <div class="lbl">Total</div>
                        </div>
                        <div class="cc-campaign-stat">
                            <div class="val" style="color:var(--cc-green)">4.2/5</div>
                            <div class="lbl">Avg Rating</div>
                        </div>
                    </div>
                    <div class="cc-campaign-metrics">
                        <span class="cc-metric-pill" style="background:rgba(0,184,148,.08);color:var(--cc-green)"><i class="fa-solid fa-phone"></i> 612 Connected</span>
                        <span class="cc-metric-pill" style="background:rgba(108,92,231,.08);color:var(--cc-accent-light)"><i class="fa-solid fa-star"></i> 534 Completed</span>
                        <span class="cc-metric-pill" style="background:rgba(225,112,85,.08);color:var(--cc-orange)"><i class="fa-solid fa-voicemail"></i> 98 Voicemail</span>
                    </div>
                    <div class="cc-campaign-actions">
                        <button><i class="fa-solid fa-chart-bar"></i> Full Report</button>
                        <button><i class="fa-solid fa-download"></i> Export</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Compliance -->
    <section class="cc-section">
        <div class="cc-container">
            <h2 class="cc-section-title" data-aos="fade-up"><i class="fa-solid fa-shield-halved" style="color:var(--cc-green);margin-right:8px"></i> Compliance &amp; Safety</h2>
            <p class="cc-section-sub" data-aos="fade-up" data-aos-delay="50">Every campaign is fully compliant with Canadian and US regulations.</p>

            <div class="cc-compliance-grid">
                <div class="cc-compliance-card" data-aos="fade-up" data-aos-delay="0">
                    <div class="comp-icon" style="background:rgba(214,48,49,.12);color:var(--cc-red)"><i class="fa-solid fa-flag"></i></div>
                    <h3>CRTC DNCL</h3>
                    <p>Automatic cross-reference with Canada's National Do Not Call List before every campaign. Non-listed numbers only.</p>
                </div>
                <div class="cc-compliance-card" data-aos="fade-up" data-aos-delay="50">
                    <div class="comp-icon" style="background:rgba(9,132,227,.12);color:var(--cc-blue)"><i class="fa-solid fa-gavel"></i></div>
                    <h3>TCPA Compliance</h3>
                    <p>Full Telephone Consumer Protection Act compliance for US contacts including prior express consent verification.</p>
                </div>
                <div class="cc-compliance-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="comp-icon" style="background:rgba(0,184,148,.12);color:var(--cc-green)"><i class="fa-solid fa-certificate"></i></div>
                    <h3>STIR/SHAKEN</h3>
                    <p>All calls are authenticated with STIR/SHAKEN attestation to prevent caller ID spoofing and build trust.</p>
                </div>
                <div class="cc-compliance-card" data-aos="fade-up" data-aos-delay="150">
                    <div class="comp-icon" style="background:rgba(253,203,110,.12);color:var(--cc-yellow)"><i class="fa-solid fa-hand"></i></div>
                    <h3>Opt-Out on Every Call</h3>
                    <p>Every outbound call includes an opt-out option. Recipients can say "stop" at any time to be removed immediately.</p>
                </div>
                <div class="cc-compliance-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="comp-icon" style="background:rgba(108,92,231,.12);color:var(--cc-accent-light)"><i class="fa-solid fa-microphone-lines"></i></div>
                    <h3>Recording Consent</h3>
                    <p>Automatic recording consent announcement where required by law. Two-party consent states handled automatically.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="cc-section">
        <div class="cc-container">
            <h2 class="cc-section-title" data-aos="fade-up">Campaign Pricing</h2>
            <p class="cc-section-sub" data-aos="fade-up" data-aos-delay="50">Pay only for what you use. No minimums, no hidden fees.</p>

            <div class="cc-pricing" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-phone-volume" style="font-size:2rem;color:var(--cc-blue)"></i>
                <div class="price">$0.03 <span>/ minute</span></div>
                <div class="price-note">Per outbound call minute</div>
                <div class="ent-note"><i class="fa-solid fa-gem"></i> Included in Enterprise plans</div>
                <ul>
                    <li><i class="fa-solid fa-check"></i> All AI agent types included</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited campaigns</li>
                    <li><i class="fa-solid fa-check"></i> Real-time analytics</li>
                    <li><i class="fa-solid fa-check"></i> CRTC/TCPA compliance built-in</li>
                    <li><i class="fa-solid fa-check"></i> STIR/SHAKEN verified</li>
                    <li><i class="fa-solid fa-check"></i> CSV import / export</li>
                    <li><i class="fa-solid fa-check"></i> Up to 10 concurrent calls</li>
                </ul>
                <a href="/pricing.php" class="btn-cc btn-cc-primary"><i class="fa-solid fa-rocket"></i> Get Started</a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cc-cta">
        <h2 data-aos="fade-up">Ready to Launch Your First Campaign?</h2>
        <p data-aos="fade-up" data-aos-delay="50">Upload your contacts and let Alfred handle the rest.</p>
        <div data-aos="fade-up" data-aos-delay="100">
            <?php if ($is_logged_in): ?>
            <a href="#" onclick="document.querySelector('.cc-builder')?.scrollIntoView({behavior:'smooth'});return false;" class="btn-cc btn-cc-primary"><i class="fa-solid fa-rocket"></i> Build a Campaign</a>
            <?php else: ?>
            <a href="/login" class="btn-cc btn-cc-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In to Start</a>
            <?php endif; ?>
        </div>
    </section>

</div>

<script src="/assets/js/call-campaigns-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
