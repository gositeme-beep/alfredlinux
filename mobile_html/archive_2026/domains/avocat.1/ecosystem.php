<?php
/**
 * Alfred Ecosystem — The Sovereign Internet Platform
 * Marketing launch page connecting all ecosystem components
 */
$page_title = 'Alfred Ecosystem — The Sovereign Internet | GoSiteMe';
$page_description = 'The complete sovereign internet ecosystem: Alfred Search, Alfred Browser, Veil Protocol, Emergency Mesh, Post-Quantum Encryption, and AI that serves you — not corporations. Take back your digital life.';
$page_canonical = 'https://gositeme.com/ecosystem';
require_once __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
:root {
    --ec-void: #030308;
    --ec-surface: #0c0c1a;
    --ec-card: rgba(91,156,245,0.03);
    --ec-border: rgba(100,140,255,0.08);
    --ec-blue: #5b9cf5;
    --ec-indigo: #7c5cfc;
    --ec-cyan: #22d3ee;
    --ec-green: #34d399;
    --ec-amber: #fbbf24;
    --ec-red: #ef4444;
    --ec-text: rgba(255,255,255,0.88);
    --ec-muted: rgba(255,255,255,0.5);
    --ec-grad: linear-gradient(135deg, #5b9cf5, #7c5cfc, #a855f7);
    --ec-radius: 16px;
}
.ec-page { background: var(--ec-void); color: var(--ec-text); font-family: 'Inter','DM Sans',system-ui,sans-serif; min-height: 100vh; overflow-x: hidden; }
.ec-page a { color: var(--ec-blue); text-decoration: none; }
.ec-page a:hover { text-decoration: underline; }
.ec-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }

/* Ambient */
.ec-ambient {
    position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden;
}
.ec-ambient .orb {
    position: absolute; border-radius: 50%; filter: blur(140px); opacity: 0.05;
    animation: ec-drift 35s ease-in-out infinite alternate;
}
.ec-ambient .o1 { width: 700px; height: 700px; background: #5b9cf5; top: -200px; left: -200px; }
.ec-ambient .o2 { width: 500px; height: 500px; background: #a855f7; bottom: -100px; right: -100px; animation-delay: -12s; }
.ec-ambient .o3 { width: 400px; height: 400px; background: #22d3ee; top: 50%; left: 50%; animation-delay: -24s; }
@keyframes ec-drift {
    0% { transform: translate(0,0) scale(1); }
    50% { transform: translate(30px,-20px) scale(1.1); }
    100% { transform: translate(-20px,30px) scale(0.95); }
}

/* Hero */
.ec-hero {
    position: relative; z-index: 1;
    text-align: center;
    padding: clamp(80px,14vw,160px) 20px 80px;
}
.ec-hero::before {
    content: '';
    position: absolute;
    top: -30%; left: 50%; width: 1200px; height: 1200px;
    transform: translateX(-50%);
    background: radial-gradient(circle, rgba(91,156,245,0.05) 0%, rgba(124,92,252,0.03) 40%, transparent 65%);
    pointer-events: none;
}
.ec-sovereign-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 50px;
    background: rgba(52,211,153,0.08);
    border: 1px solid rgba(52,211,153,0.15);
    color: var(--ec-green);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 28px;
}
.ec-hero h1 {
    font-size: clamp(42px, 7vw, 80px);
    font-weight: 900;
    letter-spacing: -3px;
    line-height: 1.05;
    margin-bottom: 24px;
    background: var(--ec-grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ec-hero p {
    font-size: clamp(16px, 2vw, 20px);
    color: var(--ec-muted);
    max-width: 700px;
    margin: 0 auto 40px;
    line-height: 1.7;
}
.ec-hero-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.ec-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 700;
    transition: all 0.2s;
    font-family: inherit;
    border: none;
    cursor: pointer;
    text-decoration: none !important;
}
.ec-btn-primary {
    background: var(--ec-grad);
    color: #fff;
}
.ec-btn-primary:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 8px 32px rgba(91,156,245,0.25); }
.ec-btn-secondary {
    background: rgba(255,255,255,0.06);
    color: var(--ec-text);
    border: 1px solid rgba(255,255,255,0.1) !important;
}
.ec-btn-secondary:hover { border-color: rgba(255,255,255,0.2) !important; }

/* Section */
.ec-section { position: relative; z-index: 1; padding: 80px 0; }
.ec-section-header { text-align: center; margin-bottom: 48px; }
.ec-section-title {
    font-size: clamp(28px, 5vw, 48px);
    font-weight: 900;
    letter-spacing: -2px;
    margin-bottom: 14px;
}
.ec-section-sub {
    color: var(--ec-muted);
    font-size: 16px;
    max-width: 640px;
    margin: 0 auto;
    line-height: 1.7;
}

/* Ecosystem Map — The Hub */
.ec-hub { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
.ec-node {
    background: var(--ec-card);
    border: 1px solid var(--ec-border);
    border-radius: var(--ec-radius);
    padding: 32px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
}
.ec-node:hover {
    transform: translateY(-3px);
    border-color: rgba(91,156,245,0.2);
    box-shadow: 0 12px 48px rgba(0,0,0,0.3);
}
.ec-node::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    opacity: 0.6;
}
.ec-node-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 20px;
}
.ec-node h3 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px; }
.ec-node p { font-size: 14px; color: var(--ec-muted); line-height: 1.7; margin-bottom: 16px; }
.ec-node-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.ec-status-live { background: rgba(52,211,153,0.1); color: var(--ec-green); }
.ec-status-beta { background: rgba(251,191,36,0.1); color: var(--ec-amber); }
.ec-node-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 14px;
    font-size: 13px;
    font-weight: 600;
    color: var(--ec-blue) !important;
}

/* Manifesto */
.ec-manifesto {
    position: relative; z-index: 1;
    background: linear-gradient(135deg, rgba(91,156,245,0.04), rgba(124,92,252,0.04));
    border: 1px solid rgba(91,156,245,0.08);
    border-radius: 24px;
    padding: clamp(40px,6vw,80px);
    margin: 40px 0;
    text-align: center;
}
.ec-manifesto h2 {
    font-size: clamp(24px, 4vw, 40px);
    font-weight: 900;
    letter-spacing: -1.5px;
    margin-bottom: 24px;
}
.ec-manifesto p {
    font-size: 17px;
    color: var(--ec-muted);
    line-height: 2;
    max-width: 700px;
    margin: 0 auto 24px;
}
.ec-manifesto strong { color: #fff; }

/* Stats */
.ec-stats {
    display: flex;
    gap: 40px;
    justify-content: center;
    flex-wrap: wrap;
    padding: 48px 0;
    position: relative;
    z-index: 1;
}
.ec-stat { text-align: center; }
.ec-stat-val {
    font-size: 36px;
    font-weight: 900;
    background: var(--ec-grad);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.ec-stat-lbl {
    font-size: 12px;
    color: var(--ec-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 4px;
}

/* Timeline */
.ec-timeline { position: relative; padding-left: 32px; max-width: 700px; margin: 0 auto; }
.ec-timeline::before {
    content: '';
    position: absolute;
    left: 8px; top: 0; bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--ec-blue), var(--ec-indigo), transparent);
}
.ec-tl-item {
    position: relative;
    padding: 0 0 40px 32px;
}
.ec-tl-item::before {
    content: '';
    position: absolute;
    left: -28px; top: 4px;
    width: 12px; height: 12px;
    border-radius: 50%;
    background: var(--ec-blue);
    border: 2px solid var(--ec-void);
}
.ec-tl-item.done::before { background: var(--ec-green); }
.ec-tl-item.next::before { background: var(--ec-amber); }
.ec-tl-date { font-size: 12px; color: var(--ec-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
.ec-tl-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px; }
.ec-tl-desc { font-size: 14px; color: var(--ec-muted); line-height: 1.6; }

/* CTA */
.ec-cta {
    text-align: center;
    padding: 80px 20px;
    position: relative;
    z-index: 1;
}

@media (max-width: 768px) {
    .ec-hero h1 { letter-spacing: -2px; }
    .ec-hub { grid-template-columns: 1fr; }
    .ec-stats { gap: 24px; }
    .ec-stat-val { font-size: 24px; }
}
</style>

<div class="ec-page">
    <div class="ec-ambient">
        <div class="orb o1"></div>
        <div class="orb o2"></div>
        <div class="orb o3"></div>
    </div>

    <!-- Hero -->
    <div class="ec-hero">
        <div class="ec-sovereign-badge"><i class="fas fa-crown"></i> 100% Sovereign Infrastructure</div>
        <h1>The Sovereign<br>Internet</h1>
        <p>A complete ecosystem that replaces every surveillance tool with a sovereign alternative. Search, browse, communicate, encrypt, survive — all under your control, all self-hosted, all zero-tracking.</p>
        <div class="ec-hero-actions">
            <a href="/alfred-browser" class="ec-btn ec-btn-primary"><i class="fas fa-download"></i> Get Alfred Browser</a>
            <a href="/search" class="ec-btn ec-btn-secondary"><i class="fas fa-search"></i> Try Alfred Search</a>
        </div>
    </div>

    <div class="ec-container">
        <!-- Stats -->
        <div class="ec-stats">
            <div class="ec-stat"><div class="ec-stat-val">0</div><div class="ec-stat-lbl">Trackers</div></div>
            <div class="ec-stat"><div class="ec-stat-val">0</div><div class="ec-stat-lbl">Ads</div></div>
            <div class="ec-stat"><div class="ec-stat-val">0</div><div class="ec-stat-lbl">Data Sales</div></div>
            <div class="ec-stat"><div class="ec-stat-val">10</div><div class="ec-stat-lbl">Encryption Layers</div></div>
            <div class="ec-stat"><div class="ec-stat-val">PQ</div><div class="ec-stat-lbl">Quantum-Safe</div></div>
            <div class="ec-stat"><div class="ec-stat-val">100%</div><div class="ec-stat-lbl">Self-Hosted</div></div>
        </div>

        <!-- Ecosystem Map -->
        <div class="ec-section">
            <div class="ec-section-header">
                <h2 class="ec-section-title">The Ecosystem</h2>
                <p class="ec-section-sub">Every piece works independently. Together, they form an impenetrable sovereign platform.</p>
            </div>
            <div class="ec-hub">
                <!-- Alfred Search -->
                <div class="ec-node" style="--accent: var(--ec-blue);">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ec-blue),var(--ec-indigo));opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(91,156,245,0.1);color:var(--ec-blue);"><i class="fas fa-search"></i></div>
                    <h3>Alfred Search</h3>
                    <p>Sovereign search engine with AI-powered instant answers, deep research mode, voice input, and encrypted query logging. Your own Meilisearch index + web crawler.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/search" class="ec-node-link"><i class="fas fa-arrow-right"></i> Search now</a>
                </div>

                <!-- Alfred Browser -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ec-indigo),#a855f7);opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(124,92,252,0.1);color:var(--ec-indigo);"><i class="fas fa-globe-americas"></i></div>
                    <h3>Alfred Browser</h3>
                    <p>Sovereign browser with built-in Veil encryption, anti-fingerprinting, AI assistant, and emergency mesh networking. Available for Windows, macOS, Linux, Android.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/alfred-browser" class="ec-node-link"><i class="fas fa-download"></i> Download</a>
                </div>

                <!-- Veil Protocol -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ec-green),var(--ec-cyan));opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(52,211,153,0.1);color:var(--ec-green);"><i class="fas fa-shield-alt"></i></div>
                    <h3>Veil Protocol</h3>
                    <p>10-layer encryption fortress: Kyber-768 + AES-256-GCM + ECDH + Double Ratchet + steganographic obfuscation. Post-quantum E2E encryption for all communications.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/veil/" class="ec-node-link"><i class="fas fa-arrow-right"></i> Explore</a>
                </div>

                <!-- Emergency Kit -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ec-red),var(--ec-amber));opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(239,68,68,0.1);color:var(--ec-red);"><i class="fas fa-broadcast-tower"></i></div>
                    <h3>Emergency Kit</h3>
                    <p>Apocalypse-ready survival systems: offline medical guides, mesh communications, cached maps, water purification, shelter construction — all cached locally via Service Worker.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/emergency-kit" class="ec-node-link"><i class="fas fa-arrow-right"></i> Prepare</a>
                </div>

                <!-- Post-Quantum -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ec-cyan),var(--ec-blue));opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(34,211,238,0.1);color:var(--ec-cyan);"><i class="fas fa-atom"></i></div>
                    <h3>Post-Quantum Cryptography</h3>
                    <p>NIST ML-KEM (Kyber-768) key encapsulation + Dilithium signatures. Harvest-now-decrypt-later attacks neutralized. Future-proof security today.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/post-quantum" class="ec-node-link"><i class="fas fa-arrow-right"></i> Learn more</a>
                </div>

                <!-- Sovereign Crawler -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ec-amber),var(--ec-green));opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(251,191,36,0.1);color:var(--ec-amber);"><i class="fas fa-spider"></i></div>
                    <h3>AlfredSearchBot</h3>
                    <p>Our own web crawler with robots.txt compliance, quality scoring, and Meilisearch indexing. Building a sovereign web index independent of Google, Bing, or any third party.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/about-crawler" class="ec-node-link"><i class="fas fa-arrow-right"></i> About</a>
                </div>

                <!-- AI Stack -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#a855f7,#ec4899);opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(168,85,247,0.1);color:#a855f7;"><i class="fas fa-brain"></i></div>
                    <h3>Alfred AI</h3>
                    <p>Self-hosted AI with Ollama (Llama 3.1) running locally, plus Groq and OpenAI fallback chain. AI-powered search, voice transcription, page summarization — no data leaves your server.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/alfred" class="ec-node-link"><i class="fas fa-arrow-right"></i> Meet Alfred</a>
                </div>

                <!-- GSM Token & Mining -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#fbbf24,#f59e0b);opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;"><i class="fas fa-coins"></i></div>
                    <h3>GSM Token & Mining</h3>
                    <p>Earn GSM tokens by searching and contributing compute power. Browser mining with 80/20 split — you keep 80%. Built on Solana for instant, low-fee transfers. Like Brave BAT, but better.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/wallet" class="ec-node-link"><i class="fas fa-arrow-right"></i> Open Wallet</a>
                </div>

                <!-- WebSocket / Real-time -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ec-green),var(--ec-blue));opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(52,211,153,0.1);color:var(--ec-green);"><i class="fas fa-bolt"></i></div>
                    <h3>Real-Time Infrastructure</h3>
                    <p>WebSocket server (port 3010) with Redis pub/sub, HMAC authentication, heartbeat monitoring. Fleet management, agent coordination, live chat, and presence tracking.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/command-center" class="ec-node-link"><i class="fas fa-arrow-right"></i> Command Center</a>
                </div>

                <!-- Health Research -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981,#22d3ee);opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i class="fas fa-dna"></i></div>
                    <h3>Health Research Portal</h3>
                    <p>59,000+ AI agents researching genetics, nutrition, longevity, cannabis science, natural compounds, mental health, and ancient knowledge. Ask any health question.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/health-research" class="ec-node-link"><i class="fas fa-arrow-right"></i> Ask a question</a>
                </div>

                <!-- Fleet Command -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#6c5ce7,#18ffff);opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(108,92,231,0.1);color:#6c5ce7;"><i class="fas fa-satellite-dish"></i></div>
                    <h3>Fleet Command</h3>
                    <p>Orchestrate swarms of AI agents in parallel. Create fleets from mission templates, monitor topology in real-time, and coordinate thousands of agents simultaneously.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/fleet-dashboard" class="ec-node-link"><i class="fas fa-arrow-right"></i> Launch fleets</a>
                </div>

                <!-- GoCodeMe IDE -->
                <div class="ec-node">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#ef4444,#f97316);opacity:0.6;"></div>
                    <div class="ec-node-icon" style="background:rgba(239,68,68,0.1);color:#ef4444;"><i class="fas fa-laptop-code"></i></div>
                    <h3>GoCodeMe IDE</h3>
                    <p>Cloud-based development environment powered by Eclipse Theia. Full VS Code experience in the browser with terminal, file sync, extensions, and AI-assisted coding.</p>
                    <span class="ec-node-status ec-status-live"><i class="fas fa-circle" style="font-size:6px;"></i> Live</span>
                    <br><a href="/gocodeme" class="ec-node-link"><i class="fas fa-arrow-right"></i> Start coding</a>
                </div>
            </div>
        </div>

        <!-- Manifesto -->
        <div class="ec-manifesto">
            <h2>The Sovereign Manifesto</h2>
            <p>The internet was built to be free. Then corporations captured it — turning users into products, conversations into data, and privacy into a luxury.</p>
            <p><strong>We're taking it back.</strong></p>
            <p>Every search you make on Alfred is encrypted and stays yours. Every message through Veil is sealed with post-quantum cryptography. Every page you visit through Alfred Browser leaves zero fingerprints. Every emergency guide is cached offline — because survival shouldn't depend on a cell tower.</p>
            <p>This isn't a product. <strong>It's infrastructure for human sovereignty.</strong></p>
        </div>

        <!-- Roadmap -->
        <div class="ec-section">
            <div class="ec-section-header">
                <h2 class="ec-section-title">Roadmap</h2>
                <p class="ec-section-sub">Where we've been and where we're going.</p>
            </div>
            <div class="ec-timeline">
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Veil Protocol — 10-Layer Encryption</div>
                    <div class="ec-tl-desc">AES-256-GCM + Kyber-768 + ECDH + Double Ratchet + steganographic obfuscation. Zero-knowledge server design.</div>
                </div>
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Full Sovereignty Audit</div>
                    <div class="ec-tl-desc">Eliminated all external CDN dependencies, Google Fonts, GA4, Facebook Pixel. 100% self-hosted assets. Zero leaks.</div>
                </div>
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Alfred Search Engine</div>
                    <div class="ec-tl-desc">AI-powered sovereign search with Meilisearch index, web crawler, encrypted logging, voice input, cost tracking.</div>
                </div>
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Emergency Survival Kit</div>
                    <div class="ec-tl-desc">Offline-first emergency systems: medical triage, water purification, navigation, shelter, mesh comms.</div>
                </div>
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Global Security Hardening</div>
                    <div class="ec-tl-desc">HSTS preload, Referrer-Policy, Permissions-Policy, gateway authentication, session fixation fixes, cache protection.</div>
                </div>
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Pulse Social Hub</div>
                    <div class="ec-tl-desc">Sovereign social platform with Veil-encrypted messaging, communities, agent personalities, and federated identity.</div>
                </div>
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Health Research Pipeline — 59K Agents</div>
                    <div class="ec-tl-desc">12 research divisions, 59,000+ AI agents researching genetics, nutrition, longevity, cannabis, ancient knowledge, and more.</div>
                </div>
                <div class="ec-tl-item done">
                    <div class="ec-tl-date">Completed</div>
                    <div class="ec-tl-title">Fleet Command v2.0</div>
                    <div class="ec-tl-desc">Swarm orchestration with mission templates, real-time topology, performance heatmaps, history tracking, and CSV export.</div>
                </div>
                <div class="ec-tl-item next">
                    <div class="ec-tl-date">Next</div>
                    <div class="ec-tl-title">Desktop ↔ WebSocket Bridge</div>
                    <div class="ec-tl-desc">Connect Alfred Browser to the real-time WebSocket infrastructure for live fleet updates and agent coordination.</div>
                </div>
                <div class="ec-tl-item">
                    <div class="ec-tl-date">Planned</div>
                    <div class="ec-tl-title">Cross-Device Sync</div>
                    <div class="ec-tl-desc">Zero-knowledge encrypted sync between desktop, mobile, and browser extension instances.</div>
                </div>
                <div class="ec-tl-item">
                    <div class="ec-tl-date">Planned</div>
                    <div class="ec-tl-title">Mesh Network v2</div>
                    <div class="ec-tl-desc">Multi-hop relay mesh via BLE and WiFi Direct. Fully decentralized emergency communication without any server.</div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="ec-cta">
            <h2 class="ec-section-title" style="margin-bottom:16px;">Join the Sovereign Internet</h2>
            <p style="color:var(--ec-muted);max-width:500px;margin:0 auto 32px;font-size:16px;line-height:1.7;">
                Start with any piece. They all work independently.<br>Together, they make you <strong style="color:#fff;">untouchable</strong>.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="/alfred-browser" class="ec-btn ec-btn-primary"><i class="fas fa-download"></i> Download Browser</a>
                <a href="/search" class="ec-btn ec-btn-secondary"><i class="fas fa-search"></i> Try Search</a>
                <a href="/health-research" class="ec-btn ec-btn-secondary"><i class="fas fa-dna"></i> Health Research</a>
                <a href="/emergency-kit" class="ec-btn ec-btn-secondary"><i class="fas fa-first-aid"></i> Emergency Kit</a>
            </div>
            <div style="margin-top:24px;display:flex;gap:20px;justify-content:center;flex-wrap:wrap;">
                <a href="/veil/" style="font-size:13px;color:var(--ec-muted);"><i class="fas fa-shield-alt" style="margin-right:4px;"></i> Veil Protocol</a>
                <a href="/post-quantum" style="font-size:13px;color:var(--ec-muted);"><i class="fas fa-atom" style="margin-right:4px;"></i> Post-Quantum</a>
                <a href="/fleet-dashboard" style="font-size:13px;color:var(--ec-muted);"><i class="fas fa-satellite-dish" style="margin-right:4px;"></i> Fleet Command</a>
                <a href="/security" style="font-size:13px;color:var(--ec-muted);"><i class="fas fa-lock" style="margin-right:4px;"></i> Security</a>
                <a href="/gocodeme" style="font-size:13px;color:var(--ec-muted);"><i class="fas fa-laptop-code" style="margin-right:4px;"></i> GoCodeMe IDE</a>
                <a href="/about-crawler" style="font-size:13px;color:var(--ec-muted);"><i class="fas fa-spider" style="margin-right:4px;"></i> Crawler</a>
                <a href="/about" style="font-size:13px;color:var(--ec-muted);"><i class="fas fa-info-circle" style="margin-right:4px;"></i> About</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
