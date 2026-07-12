<?php
$page_title       = 'Voice Cloning — Clone Your Voice for AI Agents | GoSiteMe';
$page_description = 'Record a 5-minute sample and your AI agents will sound exactly like you. Secure voice cloning with consent verification, watermarking, and full usage controls.';
$page_canonical   = 'https://gositeme.com/voice-cloning.php';
$page_og_title    = 'Clone Your Voice for AI Agents — Alfred Voice Cloning';
$page_og_description = 'Record a 5-minute sample and deploy your own voice to AI agents. Secure, consent-verified voice cloning.';
$page_twitter_description = 'Clone your voice for AI agents. Record 5 minutes, deploy everywhere. Consent-verified and watermarked.';
include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>

<style>
/* ===== VOICE CLONING — SCOPED STYLES ===== */
.vc-page {
    --vc-bg: #0a0a14;
    --vc-surface: #12121e;
    --vc-surface-2: #1a1a2e;
    --vc-surface-3: #222240;
    --vc-border: rgba(255,255,255,.06);
    --vc-border-hover: rgba(255,255,255,.12);
    --vc-text: #e4e4ec;
    --vc-text-muted: #8892b0;
    --vc-text-dim: #5a6380;
    --vc-accent: #6c5ce7;
    --vc-accent-light: #a29bfe;
    --vc-blue: #0984e3;
    --vc-green: #00b894;
    --vc-cyan: #00cec9;
    --vc-orange: #e17055;
    --vc-red: #d63031;
    --vc-yellow: #fdcb6e;
    --vc-pink: #fd79a8;
    --vc-radius: 14px;
    --vc-radius-sm: 10px;
    --vc-radius-lg: 18px;
    --vc-shadow: 0 4px 24px rgba(0,0,0,.3);
    --vc-transition: all .25s cubic-bezier(.4,0,.2,1);
}

/* Hero */
.vc-hero {
    padding: 140px 0 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.vc-hero::before {
    content: '';
    position: absolute;
    top: -300px;
    left: 50%;
    transform: translateX(-50%);
    width: 900px;
    height: 900px;
    background: radial-gradient(circle, rgba(108,92,231,.2) 0%, rgba(253,121,168,.1) 40%, transparent 70%);
    pointer-events: none;
    animation: vcGlow 6s ease-in-out infinite;
}
@keyframes vcGlow {
    0%,100% { opacity:.4; transform:translateX(-50%) scale(1); }
    50% { opacity:.7; transform:translateX(-50%) scale(1.06); }
}
.vc-hero .badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 100px;
    background: linear-gradient(135deg, rgba(108,92,231,.2), rgba(253,121,168,.15));
    border: 1px solid rgba(108,92,231,.3);
    color: var(--vc-accent-light);
    font-size: .85rem;
    font-weight: 600;
    margin-bottom: 24px;
    letter-spacing: .5px;
}
.vc-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 20px;
    position: relative;
}
.vc-hero h1 .hl {
    background: linear-gradient(135deg, var(--vc-accent-light), var(--vc-pink));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.vc-hero p {
    font-size: 1.15rem;
    color: var(--vc-text-muted);
    max-width: 640px;
    margin: 0 auto;
    line-height: 1.7;
}

/* Container */
.vc-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}
.vc-section {
    padding: 60px 0;
}
.vc-section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 12px;
    text-align: center;
}
.vc-section-sub {
    color: var(--vc-text-muted);
    text-align: center;
    max-width: 600px;
    margin: 0 auto 40px;
    font-size: 1rem;
}

/* ===== HOW IT WORKS ===== */
.vc-steps {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
}
.vc-step {
    flex: 1;
    min-width: 180px;
    max-width: 220px;
    text-align: center;
    position: relative;
}
.vc-step-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 16px;
    position: relative;
    z-index: 2;
}
.vc-step-num {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--vc-accent);
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
}
.vc-step h3 {
    font-size: .95rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.vc-step p {
    font-size: .8rem;
    color: var(--vc-text-muted);
    line-height: 1.5;
}
.vc-step-arrow {
    display: flex;
    align-items: center;
    color: var(--vc-text-dim);
    font-size: 1.2rem;
    margin-top: 30px;
}
@media (max-width:768px) {
    .vc-step-arrow { display: none; }
    .vc-steps { flex-direction: column; align-items: center; }
    .vc-step { max-width: 100%; }
}

/* ===== RECORDING INTERFACE ===== */
.vc-recorder {
    background: var(--vc-surface);
    border: 1px solid var(--vc-border);
    border-radius: var(--vc-radius-lg);
    padding: 40px;
    max-width: 800px;
    margin: 0 auto;
    box-shadow: var(--vc-shadow);
}
.vc-recorder-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.vc-progress-bar {
    flex: 1;
    min-width: 200px;
}
.vc-progress-bar .progress-label {
    display: flex;
    justify-content: space-between;
    font-size: .78rem;
    color: var(--vc-text-muted);
    margin-bottom: 6px;
}
.vc-progress-bar .progress-track {
    height: 6px;
    background: rgba(255,255,255,.05);
    border-radius: 3px;
    overflow: hidden;
}
.vc-progress-bar .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--vc-accent), var(--vc-pink));
    border-radius: 3px;
    transition: width .5s ease;
    width: 0%;
}
.vc-quality {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .82rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 20px;
}
.vc-quality.good { background: rgba(0,184,148,.1); color: var(--vc-green); }
.vc-quality.poor { background: rgba(214,48,49,.1); color: var(--vc-red); }
.vc-quality.idle { background: rgba(255,255,255,.05); color: var(--vc-text-dim); }

.vc-script {
    background: var(--vc-surface-2);
    border: 1px solid var(--vc-border);
    border-radius: var(--vc-radius);
    padding: 28px;
    margin-bottom: 24px;
    text-align: center;
}
.vc-script-label {
    font-size: .72rem;
    color: var(--vc-text-dim);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
    font-weight: 600;
}
.vc-script-text {
    font-size: 1.15rem;
    line-height: 1.7;
    font-weight: 500;
    min-height: 60px;
}
.vc-script-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 16px;
}
.vc-script-nav button {
    background: var(--vc-surface-3);
    border: 1px solid var(--vc-border);
    color: var(--vc-text);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: .85rem;
    transition: var(--vc-transition);
}
.vc-script-nav button:hover {
    border-color: var(--vc-accent);
    color: var(--vc-accent-light);
}
.vc-script-nav .counter {
    font-size: .85rem;
    color: var(--vc-text-muted);
    font-weight: 600;
}

/* Waveform Canvas */
.vc-waveform-wrap {
    background: var(--vc-surface-2);
    border: 1px solid var(--vc-border);
    border-radius: var(--vc-radius);
    padding: 16px;
    margin-bottom: 24px;
    position: relative;
    height: 120px;
}
.vc-waveform-wrap canvas {
    width: 100%;
    height: 100%;
    display: block;
}
.vc-waveform-time {
    position: absolute;
    bottom: 8px;
    right: 12px;
    font-family: 'Space Grotesk', monospace;
    font-size: .85rem;
    color: var(--vc-text-muted);
    font-weight: 600;
}

/* Controls */
.vc-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}
.vc-rec-btn {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 3px solid var(--vc-red);
    background: transparent;
    cursor: pointer;
    position: relative;
    transition: var(--vc-transition);
    display: flex;
    align-items: center;
    justify-content: center;
}
.vc-rec-btn::after {
    content: '';
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--vc-red);
    transition: all .2s;
}
.vc-rec-btn.recording::after {
    border-radius: 4px;
    width: 20px;
    height: 20px;
}
.vc-rec-btn.recording {
    border-color: var(--vc-red);
    animation: recPulse 1.5s infinite;
}
@keyframes recPulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(214,48,49,.3); }
    50% { box-shadow: 0 0 0 12px rgba(214,48,49,0); }
}
.vc-rec-label {
    font-size: .82rem;
    color: var(--vc-text-muted);
    font-weight: 600;
}
.vc-upload-btn {
    padding: 10px 24px;
    border-radius: var(--vc-radius-sm);
    background: var(--vc-accent);
    color: #fff;
    border: none;
    font-weight: 600;
    font-size: .85rem;
    cursor: pointer;
    font-family: inherit;
    transition: var(--vc-transition);
    display: none;
}
.vc-upload-btn.visible { display: inline-flex; align-items: center; gap: 8px; }
.vc-upload-btn:hover { background: #7d6df0; }

/* ===== VOICE PROFILES ===== */
.vc-profiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.vc-profile-card {
    background: var(--vc-surface);
    border: 1px solid var(--vc-border);
    border-radius: var(--vc-radius);
    padding: 24px;
    transition: var(--vc-transition);
}
.vc-profile-card:hover {
    border-color: var(--vc-accent);
    box-shadow: 0 4px 20px rgba(108,92,231,.08);
}
.vc-profile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.vc-profile-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--vc-accent), var(--vc-pink));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
}
.vc-profile-status {
    font-size: .72rem;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.vc-profile-status.ready { background: rgba(0,184,148,.12); color: var(--vc-green); }
.vc-profile-status.training { background: rgba(108,92,231,.12); color: var(--vc-accent-light); }
.vc-profile-status.inactive { background: rgba(255,255,255,.05); color: var(--vc-text-dim); }
.vc-profile-name {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: 4px;
}
.vc-profile-meta {
    font-size: .78rem;
    color: var(--vc-text-muted);
    margin-bottom: 16px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.vc-profile-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}
.vc-profile-actions {
    display: flex;
    gap: 8px;
}
.vc-profile-actions button {
    padding: 6px 14px;
    border-radius: var(--vc-radius-sm);
    border: 1px solid var(--vc-border);
    background: transparent;
    color: var(--vc-text);
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--vc-transition);
    font-family: inherit;
    display: flex;
    align-items: center;
    gap: 4px;
}
.vc-profile-actions button:hover {
    border-color: var(--vc-accent);
    color: var(--vc-accent-light);
}
.vc-profile-actions .btn-play {
    background: var(--vc-accent);
    border-color: var(--vc-accent);
    color: #fff;
}
.vc-profile-actions .btn-play:hover {
    background: #7d6df0;
}
.vc-profile-actions .btn-delete:hover {
    border-color: var(--vc-red);
    color: var(--vc-red);
}

/* ===== SAFEGUARDS ===== */
.vc-safeguards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}
.vc-safeguard-card {
    background: var(--vc-surface);
    border: 1px solid var(--vc-border);
    border-radius: var(--vc-radius);
    padding: 24px;
    transition: var(--vc-transition);
}
.vc-safeguard-card:hover {
    border-color: rgba(0,184,148,.3);
}
.vc-safeguard-card .sg-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 16px;
}
.vc-safeguard-card h3 {
    font-size: .95rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.vc-safeguard-card p {
    font-size: .82rem;
    color: var(--vc-text-muted);
    line-height: 1.6;
}

/* ===== PRICING ===== */
.vc-pricing {
    text-align: center;
    background: var(--vc-surface);
    border: 1px solid var(--vc-border);
    border-radius: var(--vc-radius-lg);
    padding: 48px;
    max-width: 600px;
    margin: 0 auto;
}
.vc-pricing .price {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 3rem;
    font-weight: 800;
    margin: 16px 0 8px;
}
.vc-pricing .price span {
    font-size: 1rem;
    color: var(--vc-text-muted);
    font-weight: 400;
}
.vc-pricing .price-note {
    font-size: .85rem;
    color: var(--vc-text-muted);
    margin-bottom: 8px;
}
.vc-pricing .ent-note {
    font-size: .82rem;
    color: var(--vc-green);
    font-weight: 600;
    margin-bottom: 24px;
}
.vc-pricing ul {
    list-style: none;
    text-align: left;
    max-width: 340px;
    margin: 0 auto 28px;
}
.vc-pricing ul li {
    padding: 8px 0;
    font-size: .88rem;
    color: var(--vc-text-muted);
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,.03);
}
.vc-pricing ul li i {
    color: var(--vc-green);
    flex-shrink: 0;
}

/* Buttons */
.btn-vc {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: var(--vc-radius-sm);
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    transition: var(--vc-transition);
    text-decoration: none;
    border: none;
    font-family: inherit;
}
.btn-vc-primary {
    background: var(--vc-accent);
    color: #fff;
}
.btn-vc-primary:hover {
    background: #7d6df0;
    transform: translateY(-1px);
    color: #fff;
}

/* CTA */
.vc-cta {
    text-align: center;
    padding: 80px 24px;
    position: relative;
}
.vc-cta h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 16px;
}
.vc-cta p {
    color: var(--vc-text-muted);
    max-width: 500px;
    margin: 0 auto 28px;
}

/* Login prompt */
.vc-login-prompt {
    text-align: center;
    padding: 48px;
    background: var(--vc-surface);
    border: 1px solid var(--vc-border);
    border-radius: var(--vc-radius-lg);
    max-width: 600px;
    margin: 0 auto;
}
.vc-login-prompt i {
    font-size: 2.5rem;
    color: var(--vc-accent);
    margin-bottom: 16px;
}
.vc-login-prompt h3 {
    font-size: 1.2rem;
    margin-bottom: 12px;
}
.vc-login-prompt p {
    color: var(--vc-text-muted);
    margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 700px) {
    .vc-recorder { padding: 24px; }
    .vc-hero { padding: 100px 0 40px; }
    .vc-hero h1 { font-size: 1.8rem; }
    .vc-profiles-grid, .vc-safeguards-grid { grid-template-columns: 1fr; }
}
</style>

<div class="vc-page">

    <!-- Hero -->
    <section class="vc-hero">
        <div class="vc-container">
            <div class="badge" data-aos="fade-down"><i class="fa-solid fa-microphone-lines"></i> Voice Cloning</div>
            <h1 data-aos="fade-up">Clone Your Voice <span class="hl">for AI Agents</span></h1>
            <p data-aos="fade-up" data-aos-delay="100">Record a 5-minute sample and your AI agents will sound exactly like you.</p>
        </div>
    </section>

    <!-- How It Works -->
    <section class="vc-section">
        <div class="vc-container">
            <h2 class="vc-section-title" data-aos="fade-up">How It Works</h2>
            <p class="vc-section-sub" data-aos="fade-up" data-aos-delay="50">Five simple steps from recording to deployment.</p>

            <div class="vc-steps" data-aos="fade-up" data-aos-delay="100">
                <div class="vc-step">
                    <div class="vc-step-icon" style="background:rgba(214,48,49,.12);color:var(--vc-red)">
                        <i class="fa-solid fa-microphone"></i>
                        <span class="vc-step-num">1</span>
                    </div>
                    <h3>Record Sample</h3>
                    <p>Read 50 guided sentences aloud. Takes about 5 minutes in a quiet environment.</p>
                </div>
                <div class="vc-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                <div class="vc-step">
                    <div class="vc-step-icon" style="background:rgba(9,132,227,.12);color:var(--vc-blue)">
                        <i class="fa-solid fa-waveform-lines"></i>
                        <span class="vc-step-num">2</span>
                    </div>
                    <h3>Processing</h3>
                    <p>Audio is cleaned, normalized, and prepared for AI model training.</p>
                </div>
                <div class="vc-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                <div class="vc-step">
                    <div class="vc-step-icon" style="background:rgba(108,92,231,.12);color:var(--vc-accent-light)">
                        <i class="fa-solid fa-brain"></i>
                        <span class="vc-step-num">3</span>
                    </div>
                    <h3>AI Training</h3>
                    <p>Our model learns your voice patterns, tone, and cadence. Usually takes ~30 minutes.</p>
                </div>
                <div class="vc-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                <div class="vc-step">
                    <div class="vc-step-icon" style="background:rgba(0,184,148,.12);color:var(--vc-green)">
                        <i class="fa-solid fa-play"></i>
                        <span class="vc-step-num">4</span>
                    </div>
                    <h3>Preview</h3>
                    <p>Listen to your cloned voice and fine-tune before going live.</p>
                </div>
                <div class="vc-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                <div class="vc-step">
                    <div class="vc-step-icon" style="background:rgba(253,121,168,.12);color:var(--vc-pink)">
                        <i class="fa-solid fa-rocket"></i>
                        <span class="vc-step-num">5</span>
                    </div>
                    <h3>Deploy</h3>
                    <p>Assign your cloned voice to any AI agent in your fleet with one click.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Recording Interface -->
    <section class="vc-section">
        <div class="vc-container">
            <h2 class="vc-section-title" data-aos="fade-up">Voice Recording Studio</h2>
            <p class="vc-section-sub" data-aos="fade-up" data-aos-delay="50">Record your voice samples in our guided recording interface.</p>

            <?php if ($is_logged_in): ?>
            <div class="vc-recorder" data-aos="fade-up" data-aos-delay="100">
                <div class="vc-recorder-header">
                    <div class="vc-progress-bar">
                        <div class="progress-label">
                            <span>Recording Progress</span>
                            <span id="vcProgressText">0 / 50 sentences</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" id="vcProgressFill"></div>
                        </div>
                    </div>
                    <div class="vc-quality idle" id="vcQuality">
                        <i class="fa-solid fa-signal"></i>
                        <span>Ready</span>
                    </div>
                </div>

                <div class="vc-script">
                    <div class="vc-script-label">Read the following sentence</div>
                    <div class="vc-script-text" id="vcScriptText">Press the record button below to begin your voice cloning session.</div>
                    <div class="vc-script-nav">
                        <button onclick="VoiceClone.prevSentence()" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                        <span class="counter" id="vcCounter">0 / 50</span>
                        <button onclick="VoiceClone.nextSentence()" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>

                <div class="vc-waveform-wrap">
                    <canvas id="vcWaveformCanvas"></canvas>
                    <div class="vc-waveform-time" id="vcTimer">0:00</div>
                </div>

                <div class="vc-controls">
                    <span class="vc-rec-label" id="vcRecLabel">Tap to Record</span>
                    <button class="vc-rec-btn" id="vcRecBtn" onclick="VoiceClone.toggleRecord()" title="Record">
                    </button>
                    <button class="vc-upload-btn" id="vcUploadBtn" onclick="VoiceClone.upload()">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload All Recordings
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="vc-login-prompt" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-lock"></i>
                <h3>Sign In to Record</h3>
                <p>You need to be logged in to access the voice recording studio and manage your voice profiles.</p>
                <a href="/login" class="btn-vc btn-vc-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Voice Profiles -->
    <section class="vc-section">
        <div class="vc-container">
            <h2 class="vc-section-title" data-aos="fade-up">Your Voice Profiles</h2>
            <p class="vc-section-sub" data-aos="fade-up" data-aos-delay="50">Manage your cloned voice profiles and assignments.</p>

            <?php if ($is_logged_in): ?>
            <div class="vc-profiles-grid" data-aos="fade-up" data-aos-delay="100">
                <!-- Demo profiles - would be dynamically loaded -->
                <div class="vc-profile-card">
                    <div class="vc-profile-header">
                        <div class="vc-profile-avatar"><i class="fa-solid fa-microphone"></i></div>
                        <span class="vc-profile-status ready">Ready</span>
                    </div>
                    <div class="vc-profile-name">My Business Voice</div>
                    <div class="vc-profile-meta">
                        <span><i class="fa-solid fa-globe"></i> English</span>
                        <span><i class="fa-solid fa-calendar"></i> Mar 1, 2026</span>
                        <span><i class="fa-solid fa-robot"></i> 3 agents</span>
                    </div>
                    <div class="vc-profile-actions">
                        <button class="btn-play"><i class="fa-solid fa-play"></i> Play</button>
                        <button><i class="fa-solid fa-pen"></i> Edit</button>
                        <button class="btn-delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <div class="vc-profile-card">
                    <div class="vc-profile-header">
                        <div class="vc-profile-avatar"><i class="fa-solid fa-microphone"></i></div>
                        <span class="vc-profile-status training">Training</span>
                    </div>
                    <div class="vc-profile-name">French Voice</div>
                    <div class="vc-profile-meta">
                        <span><i class="fa-solid fa-globe"></i> French</span>
                        <span><i class="fa-solid fa-calendar"></i> Mar 4, 2026</span>
                        <span><i class="fa-solid fa-robot"></i> 0 agents</span>
                    </div>
                    <div class="vc-profile-actions">
                        <button class="btn-play" disabled><i class="fa-solid fa-hourglass-half"></i> Training...</button>
                        <button><i class="fa-solid fa-pen"></i> Edit</button>
                        <button class="btn-delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <div class="vc-profile-card">
                    <div class="vc-profile-header">
                        <div class="vc-profile-avatar"><i class="fa-solid fa-microphone"></i></div>
                        <span class="vc-profile-status inactive">Inactive</span>
                    </div>
                    <div class="vc-profile-name">Customer Support Voice</div>
                    <div class="vc-profile-meta">
                        <span><i class="fa-solid fa-globe"></i> English</span>
                        <span><i class="fa-solid fa-calendar"></i> Feb 15, 2026</span>
                        <span><i class="fa-solid fa-robot"></i> 0 agents</span>
                    </div>
                    <div class="vc-profile-actions">
                        <button class="btn-play"><i class="fa-solid fa-play"></i> Play</button>
                        <button><i class="fa-solid fa-pen"></i> Edit</button>
                        <button class="btn-delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="vc-login-prompt" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-user-lock"></i>
                <h3>Sign In to View Profiles</h3>
                <p>Log in to see and manage your cloned voice profiles.</p>
                <a href="/login" class="btn-vc btn-vc-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Safeguards -->
    <section class="vc-section">
        <div class="vc-container">
            <h2 class="vc-section-title" data-aos="fade-up"><i class="fa-solid fa-shield-halved" style="color:var(--vc-green);margin-right:8px"></i> Voice Cloning Safeguards</h2>
            <p class="vc-section-sub" data-aos="fade-up" data-aos-delay="50">Your voice is protected by multiple layers of security and consent.</p>

            <div class="vc-safeguards-grid">
                <div class="vc-safeguard-card" data-aos="fade-up" data-aos-delay="0">
                    <div class="sg-icon" style="background:rgba(0,184,148,.12);color:var(--vc-green)"><i class="fa-solid fa-user-check"></i></div>
                    <h3>Consent Verification</h3>
                    <p>Multi-step consent verification with identity confirmation required before any voice cloning session begins.</p>
                </div>
                <div class="vc-safeguard-card" data-aos="fade-up" data-aos-delay="50">
                    <div class="sg-icon" style="background:rgba(9,132,227,.12);color:var(--vc-blue)"><i class="fa-solid fa-fingerprint"></i></div>
                    <h3>Audio Watermarking</h3>
                    <p>Every cloned voice output contains an inaudible digital watermark for traceability and authenticity verification.</p>
                </div>
                <div class="vc-safeguard-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="sg-icon" style="background:rgba(108,92,231,.12);color:var(--vc-accent-light)"><i class="fa-solid fa-list-check"></i></div>
                    <h3>Usage Logging</h3>
                    <p>Complete audit trails of when, where, and how your cloned voice is used across all agents and calls.</p>
                </div>
                <div class="vc-safeguard-card" data-aos="fade-up" data-aos-delay="150">
                    <div class="sg-icon" style="background:rgba(214,48,49,.12);color:var(--vc-red)"><i class="fa-solid fa-power-off"></i></div>
                    <h3>Kill Switch</h3>
                    <p>Instantly disable your cloned voice across all agents and calls with a single click. Immediate and irreversible deactivation.</p>
                </div>
                <div class="vc-safeguard-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="sg-icon" style="background:rgba(253,121,168,.12);color:var(--vc-pink)"><i class="fa-solid fa-lock"></i></div>
                    <h3>Your Voice Only</h3>
                    <p>Only you can clone your own voice. Biometric verification ensures no unauthorized voice cloning is possible.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="vc-section">
        <div class="vc-container">
            <h2 class="vc-section-title" data-aos="fade-up">Voice Cloning Pricing</h2>
            <p class="vc-section-sub" data-aos="fade-up" data-aos-delay="50">Simple, one-time pricing per voice profile.</p>

            <div class="vc-pricing" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-microphone-lines" style="font-size:2rem;color:var(--vc-accent-light)"></i>
                <div class="price">$5 <span>/ voice profile</span></div>
                <div class="price-note">One-time fee per cloned voice</div>
                <div class="ent-note"><i class="fa-solid fa-gem"></i> Included in Enterprise+ plans</div>
                <ul>
                    <li><i class="fa-solid fa-check"></i> Unlimited agent assignments</li>
                    <li><i class="fa-solid fa-check"></i> All supported languages</li>
                    <li><i class="fa-solid fa-check"></i> Audio watermarking included</li>
                    <li><i class="fa-solid fa-check"></i> Full usage logging & controls</li>
                    <li><i class="fa-solid fa-check"></i> Re-training as needed</li>
                    <li><i class="fa-solid fa-check"></i> Kill switch access</li>
                </ul>
                <a href="/pricing.php" class="btn-vc btn-vc-primary"><i class="fa-solid fa-rocket"></i> Get Started</a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="vc-cta">
        <h2 data-aos="fade-up">Ready to Clone Your Voice?</h2>
        <p data-aos="fade-up" data-aos-delay="50">Start recording in minutes. Your AI agents will sound just like you.</p>
        <div data-aos="fade-up" data-aos-delay="100">
            <?php if ($is_logged_in): ?>
            <a href="#" onclick="document.querySelector('.vc-recorder')?.scrollIntoView({behavior:'smooth'});return false;" class="btn-vc btn-vc-primary"><i class="fa-solid fa-microphone"></i> Start Recording</a>
            <?php else: ?>
            <a href="/login" class="btn-vc btn-vc-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In to Start</a>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php if ($is_logged_in): ?>
<script src="/assets/js/voice-cloning-engine.js"></script>
<?php endif; ?>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
