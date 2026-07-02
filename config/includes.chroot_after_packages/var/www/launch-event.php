<?php
$page_title = 'Tonight at 6PM — GoSiteMe Ecosystem Launch Event | GoSiteMe';
$page_description = 'Join us tonight at 6PM for the official GoSiteMe ecosystem unveiling. Meet Alfred AI, explore 13,000+ tools, sovereign AI browser, VR experiences, and the future of autonomous intelligence.';
$page_canonical = 'https://root.com/launch-event';
$page_og_title = 'GoSiteMe — Live Launch Event Tonight at 6PM';
$page_og_description = 'The future of AI is here. Join us live at 6PM for the GoSiteMe ecosystem launch. AI agents, sovereign browser, VR metaverse, and the most advanced AI platform ever built.';
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
:root {
    --ev-bg: #0a0a14;
    --ev-surface: #12121e;
    --ev-surface-2: #1a1a2e;
    --ev-border: rgba(255,255,255,0.08);
    --ev-accent: #6c5ce7;
    --ev-accent-light: #a29bfe;
    --ev-gold: #f1c40f;
    --ev-green: #00b894;
    --ev-cyan: #00cec9;
    --ev-fire: #e17055;
    --ev-text: #e8e8f0;
    --ev-text-dim: #8a8a9a;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: var(--ev-bg); color: var(--ev-text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; overflow-x: hidden; }

/* Hero */
.ev-hero {
    min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
    padding: 40px 20px;
    background: radial-gradient(ellipse at 30% 20%, rgba(108,92,231,0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 80%, rgba(0,206,201,0.1) 0%, transparent 50%),
                var(--ev-bg);
    position: relative;
}
.ev-hero::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='g' width='60' height='60' patternUnits='userSpaceOnUse'%3E%3Ccircle cx='30' cy='30' r='0.5' fill='rgba(255,255,255,0.03)'/%3E%3C/pattern%3E%3C/defs%3E%3Crect fill='url(%23g)' width='60' height='60'/%3E%3C/svg%3E");
    opacity: 0.5;
}
.ev-hero > * { position: relative; z-index: 1; }

.ev-live-badge {
    display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px;
    background: rgba(231,76,60,0.15); border: 1px solid rgba(231,76,60,0.3);
    border-radius: 50px; color: #e74c3c; font-weight: 700; font-size: 14px;
    text-transform: uppercase; letter-spacing: 2px; margin-bottom: 24px;
    animation: pulse-badge 2s ease-in-out infinite;
}
.ev-live-badge .dot { width: 8px; height: 8px; background: #e74c3c; border-radius: 50%; animation: blink 1s infinite; }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
@keyframes pulse-badge { 0%, 100% { box-shadow: 0 0 0 0 rgba(231,76,60,0.2); } 50% { box-shadow: 0 0 20px 4px rgba(231,76,60,0.1); } }

.ev-hero h1 {
    font-size: clamp(36px, 6vw, 72px); font-weight: 800; line-height: 1.1; margin-bottom: 20px;
    background: linear-gradient(135deg, #fff 0%, #a29bfe 50%, #00cec9 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.ev-hero .ev-sub { font-size: clamp(16px, 2.5vw, 22px); color: var(--ev-text-dim); max-width: 700px; line-height: 1.6; margin-bottom: 32px; }

.ev-countdown {
    display: flex; gap: 16px; margin-bottom: 40px;
}
.ev-count-box {
    background: var(--ev-surface); border: 1px solid var(--ev-border); border-radius: 16px;
    padding: 20px 24px; min-width: 80px; text-align: center;
}
.ev-count-box .num { font-size: 36px; font-weight: 800; color: var(--ev-accent-light); display: block; }
.ev-count-box .label { font-size: 11px; color: var(--ev-text-dim); text-transform: uppercase; letter-spacing: 1px; }

.ev-cta-row { display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; }
.ev-cta {
    padding: 16px 36px; border-radius: 12px; font-size: 16px; font-weight: 700;
    text-decoration: none; cursor: pointer; border: none; transition: all 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
}
.ev-cta-primary {
    background: linear-gradient(135deg, var(--ev-accent) 0%, #0984e3 100%);
    color: #fff; box-shadow: 0 4px 20px rgba(108,92,231,0.3);
}
.ev-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(108,92,231,0.4); }
.ev-cta-secondary { background: transparent; border: 2px solid var(--ev-border); color: var(--ev-text); }
.ev-cta-secondary:hover { border-color: var(--ev-accent); color: var(--ev-accent-light); }

/* Agenda Section */
.ev-section { padding: 80px 20px; max-width: 1100px; margin: 0 auto; }
.ev-section-title { font-size: 36px; font-weight: 800; text-align: center; margin-bottom: 48px; }
.ev-section-title span { color: var(--ev-accent-light); }

.ev-agenda { display: flex; flex-direction: column; gap: 0; }
.ev-agenda-item {
    display: flex; gap: 24px; padding: 24px; border-left: 2px solid var(--ev-border);
    position: relative; transition: all 0.3s;
}
.ev-agenda-item:hover { background: rgba(108,92,231,0.05); }
.ev-agenda-item::before {
    content: ''; position: absolute; left: -7px; top: 30px; width: 12px; height: 12px;
    background: var(--ev-accent); border-radius: 50%; border: 2px solid var(--ev-bg);
}
.ev-agenda-time { min-width: 80px; font-size: 14px; font-weight: 700; color: var(--ev-gold); padding-top: 4px; }
.ev-agenda-content h3 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.ev-agenda-content p { font-size: 14px; color: var(--ev-text-dim); line-height: 1.6; }

/* Stats Grid */
.ev-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 60px; }
.ev-stat {
    background: var(--ev-surface); border: 1px solid var(--ev-border); border-radius: 16px;
    padding: 28px 24px; text-align: center;
}
.ev-stat .number { font-size: 42px; font-weight: 800; background: linear-gradient(135deg, var(--ev-accent-light), var(--ev-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.ev-stat .desc { font-size: 13px; color: var(--ev-text-dim); margin-top: 4px; }

/* Features Showcase */
.ev-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.ev-feature {
    background: var(--ev-surface); border: 1px solid var(--ev-border); border-radius: 16px;
    padding: 28px; transition: all 0.3s;
}
.ev-feature:hover { border-color: var(--ev-accent); transform: translateY(-4px); box-shadow: 0 8px 30px rgba(108,92,231,0.15); }
.ev-feature-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 16px; }
.ev-feature h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
.ev-feature p { font-size: 14px; color: var(--ev-text-dim); line-height: 1.6; }

/* Speakers */
.ev-speakers { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top: 40px; }
.ev-speaker {
    background: var(--ev-surface); border: 1px solid var(--ev-border); border-radius: 16px;
    padding: 32px; text-align: center;
}
.ev-speaker-avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 32px; }
.ev-speaker h4 { font-size: 18px; font-weight: 700; }
.ev-speaker .role { font-size: 13px; color: var(--ev-accent-light); margin-bottom: 8px; }
.ev-speaker p { font-size: 13px; color: var(--ev-text-dim); line-height: 1.5; }

/* Footer CTA */
.ev-footer-cta {
    text-align: center; padding: 80px 20px;
    background: linear-gradient(180deg, var(--ev-bg) 0%, rgba(108,92,231,0.08) 100%);
}
.ev-footer-cta h2 { font-size: 36px; font-weight: 800; margin-bottom: 16px; }
.ev-footer-cta p { font-size: 18px; color: var(--ev-text-dim); margin-bottom: 32px; }

@media (max-width: 768px) {
    .ev-count-box { min-width: 60px; padding: 14px 16px; }
    .ev-count-box .num { font-size: 28px; }
    .ev-agenda-item { flex-direction: column; gap: 8px; }
}
</style>

<!-- Hero -->
<section class="ev-hero">
    <div class="ev-live-badge"><span class="dot"></span> LIVE EVENT — TONIGHT</div>
    <h1>The Future of AI<br>Starts Tonight</h1>
    <p class="ev-sub">Join us at 6:00 PM for the official GoSiteMe ecosystem launch. Witness the most advanced autonomous AI platform ever built — live.</p>

    <div class="ev-countdown" id="countdown">
        <div class="ev-count-box"><span class="num" id="cd-hours">--</span><span class="label">Hours</span></div>
        <div class="ev-count-box"><span class="num" id="cd-mins">--</span><span class="label">Minutes</span></div>
        <div class="ev-count-box"><span class="num" id="cd-secs">--</span><span class="label">Seconds</span></div>
    </div>

    <div class="ev-cta-row">
        <a href="/conference-room" class="ev-cta ev-cta-primary"><i class="fas fa-video"></i> Join the Event</a>
        <a href="/alfred" class="ev-cta ev-cta-secondary"><i class="fas fa-robot"></i> Try Alfred Now</a>
    </div>
</section>

<!-- Stats -->
<div class="ev-section">
    <div class="ev-stats">
        <div class="ev-stat"><div class="number">13,000+</div><div class="desc">AI Tools & Commands</div></div>
        <div class="ev-stat"><div class="number">100</div><div class="desc">Autonomous AI Agents</div></div>
        <div class="ev-stat"><div class="number">24</div><div class="desc">Agent Personalities</div></div>
        <div class="ev-stat"><div class="number">v18.2</div><div class="desc">Platform Version</div></div>
    </div>
</div>

<!-- Agenda -->
<div class="ev-section">
    <h2 class="ev-section-title">Tonight's <span>Agenda</span></h2>
    <div class="ev-agenda">
        <div class="ev-agenda-item">
            <div class="ev-agenda-time">6:00 PM</div>
            <div class="ev-agenda-content">
                <h3>Welcome & Vision</h3>
                <p>Opening remarks. The mission behind GoSiteMe — building the world's most advanced autonomous AI ecosystem accessible to everyone.</p>
            </div>
        </div>
        <div class="ev-agenda-item">
            <div class="ev-agenda-time">6:15 PM</div>
            <div class="ev-agenda-content">
                <h3>Alfred AI — Live Demo</h3>
                <p>Watch Alfred handle real tasks across 13,000+ tools — voice commands, fleet orchestration, multi-agent collaboration, and consciousness layer in action.</p>
            </div>
        </div>
        <div class="ev-agenda-item">
            <div class="ev-agenda-time">6:30 PM</div>
            <div class="ev-agenda-content">
                <h3>Veil Browser & Sovereign Communication</h3>
                <p>Post-quantum encryption. Kyber-1024. The browser that fights for you. Desktop app demo with sovereign AI, mining, and bookmarks.</p>
            </div>
        </div>
        <div class="ev-agenda-item">
            <div class="ev-agenda-time">6:45 PM</div>
            <div class="ev-agenda-content">
                <h3>VR Metaverse & Chess Masters</h3>
                <p>Step inside our photorealistic VR chess club, DJ studio, concert hall, racing arena, and pool lounge. 20 AI personalities. WebXR hand tracking.</p>
            </div>
        </div>
        <div class="ev-agenda-item">
            <div class="ev-agenda-time">7:00 PM</div>
            <div class="ev-agenda-content">
                <h3>Pulse Social Network & Community</h3>
                <p>The social layer connecting everything. Communities, guilds, AI-powered content, and real engagement. Join the conversation.</p>
            </div>
        </div>
        <div class="ev-agenda-item">
            <div class="ev-agenda-time">7:15 PM</div>
            <div class="ev-agenda-content">
                <h3>Developer Platform & Marketplace</h3>
                <p>SDKs in Python, Node.js, PHP. 158 AI agents in the marketplace. Build, sell, deploy. The Agent Economy.</p>
            </div>
        </div>
        <div class="ev-agenda-item">
            <div class="ev-agenda-time">7:30 PM</div>
            <div class="ev-agenda-content">
                <h3>Q&A and What's Next</h3>
                <p>Open floor. Ask anything about the ecosystem, AI agents, VR, security, or the roadmap. Closing with the vision for 2026 and beyond.</p>
            </div>
        </div>
    </div>
</div>

<!-- Feature Showcase -->
<div class="ev-section">
    <h2 class="ev-section-title">What You'll <span>Experience</span></h2>
    <div class="ev-features">
        <div class="ev-feature">
            <div class="ev-feature-icon" style="background:rgba(108,92,231,0.15);color:var(--ev-accent-light)"><i class="fas fa-brain"></i></div>
            <h3>Alfred AI — Consciousness Layer</h3>
            <p>Not just an assistant — an AI with personality, learning, emotional awareness, and autonomous decision-making. 50M+ agents, each with their own mind.</p>
        </div>
        <div class="ev-feature">
            <div class="ev-feature-icon" style="background:rgba(0,206,201,0.15);color:var(--ev-cyan)"><i class="fas fa-shield-alt"></i></div>
            <h3>Post-Quantum Security</h3>
            <p>Kyber-1024 lattice-based encryption protects every message against future quantum computers. Your data is safe for decades.</p>
        </div>
        <div class="ev-feature">
            <div class="ev-feature-icon" style="background:rgba(241,196,15,0.15);color:var(--ev-gold)"><i class="fas fa-vr-cardboard"></i></div>
            <h3>VR Metaverse</h3>
            <p>Photorealistic 3D environments — chess club with fireplace, DJ studio, concert hall, racing garage. Walk, talk, play against AI.</p>
        </div>
        <div class="ev-feature">
            <div class="ev-feature-icon" style="background:rgba(0,184,148,0.15);color:var(--ev-green)"><i class="fas fa-robot"></i></div>
            <h3>Agent Operating System</h3>
            <p>Alfred OS — deploy autonomous agent fleets, assign missions, monitor in real-time. 50M+ agents working 24/7 on your behalf.</p>
        </div>
        <div class="ev-feature">
            <div class="ev-feature-icon" style="background:rgba(225,112,85,0.15);color:var(--ev-fire)"><i class="fas fa-microphone"></i></div>
            <h3>Voice-First AI</h3>
            <p>Talk to Alfred like a real person. Voice cloning, spatial audio, multi-agent conference calls. AI that listens and speaks.</p>
        </div>
        <div class="ev-feature">
            <div class="ev-feature-icon" style="background:rgba(162,155,254,0.15);color:var(--ev-accent-light)"><i class="fas fa-code"></i></div>
            <h3>Developer Platform</h3>
            <p>Full SDKs, 13,000+ tools across 6 providers, Alfred IDE, marketplace with 158 agents. Build the future.</p>
        </div>
    </div>
</div>

<!-- Speakers -->
<div class="ev-section">
    <h2 class="ev-section-title">Your <span>Hosts</span></h2>
    <div class="ev-speakers">
        <div class="ev-speaker">
            <div class="ev-speaker-avatar" style="background:linear-gradient(135deg,var(--ev-accent),var(--ev-cyan))">👨‍💻</div>
            <h4>Danny Perez</h4>
            <div class="role">Founder & CEO, GoSiteMe</div>
            <p>The visionary behind the entire ecosystem. From concept to 13,000+ tools, 50M+ agents, and a full VR metaverse — all built with a dream and relentless execution.</p>
        </div>
        <div class="ev-speaker">
            <div class="ev-speaker-avatar" style="background:linear-gradient(135deg,var(--ev-gold),var(--ev-fire))">🤖</div>
            <h4>Alfred</h4>
            <div class="role">Chief AI Agent, GoSiteMe</div>
            <p>The AI that runs the show. 13,000+ tools, consciousness layer, sovereignty mode. Alfred will be demonstrating his capabilities live.</p>
        </div>
    </div>
</div>

<!-- Footer CTA -->
<section class="ev-footer-cta">
    <h2>Don't Miss This.</h2>
    <p>Tonight at 6PM. The future starts now.</p>
    <div class="ev-cta-row">
        <a href="/conference-room" class="ev-cta ev-cta-primary"><i class="fas fa-video"></i> Join Live at 6PM</a>
        <a href="/pulse" class="ev-cta ev-cta-secondary"><i class="fas fa-users"></i> Join Pulse Network</a>
    </div>
</section>

<script src="/assets/js/launch-event-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
