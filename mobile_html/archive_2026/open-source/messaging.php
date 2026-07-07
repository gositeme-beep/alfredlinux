<?php
/**
 * Element / Matrix — Self-Hosted Messaging | GoSiteMe
 */
$page_title = 'Element — Self-Hosted Secure Messaging, Slack Alternative | GoSiteMe';
$page_description = 'Deploy Element with Matrix Synapse on your server. End-to-end encrypted team messaging — voice, video, threads, spaces. Bridges to Slack, Discord, Telegram. Included with GoSiteMe hosting.';
$page_canonical = 'https://gositeme.com/open-source/messaging.php';
$page_og_title = 'Element — Self-Hosted Secure Messaging';
$page_og_description = $page_description;
$page_twitter_description = $page_og_description;
$page_og_image = 'https://gositeme.com/assets/hero-banner.png';
$page_robots = 'index, follow';
$preload_hero = false;
require_once __DIR__ . '/../includes/site-header.inc.php';
?>
<style>
.tp-hero{padding:100px 0 60px;text-align:center;position:relative;overflow:hidden;background:linear-gradient(180deg,rgba(0,212,170,.08) 0%,transparent 60%)}
.tp-hero::before{content:'';position:absolute;top:-200px;left:50%;transform:translateX(-50%);width:800px;height:600px;background:radial-gradient(circle,rgba(0,212,170,.15),transparent 60%);border-radius:50%}
.tp-hero .container{position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:0 24px}
.tp-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(0,212,170,.12);border:1px solid rgba(0,212,170,.3);padding:6px 16px;border-radius:20px;font-size:.85rem;color:#00d4aa;margin-bottom:20px;font-weight:600}
.tp-hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3rem);font-weight:800;color:#fff;margin:0 0 16px;line-height:1.15}
.tp-hero h1 span{color:#00d4aa}
.tp-hero p.sub{color:#a0a0b8;font-size:1.1rem;max-width:700px;margin:0 auto 30px;line-height:1.7}
.tp-hero-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.tp-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;font-size:.9rem;font-weight:600;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer;font-family:'Inter',sans-serif}
.tp-btn-primary{background:#00d4aa;color:#000;font-weight:700}
.tp-btn-primary:hover{background:#00e8bb;transform:translateY(-2px);box-shadow:0 10px 40px rgba(0,212,170,.3)}
.tp-btn-ghost{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.15)}
.tp-btn-ghost:hover{background:rgba(255,255,255,.1)}
.tp-section{padding:80px 24px}
.tp-section .container{max-width:1100px;margin:0 auto}
.tp-section-alt{background:rgba(18,18,42,.4)}
.tp-title{font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;text-align:center;margin-bottom:12px}
.tp-subtitle{color:#a0a0b8;text-align:center;margin-bottom:50px;font-size:1rem;max-width:600px;margin-left:auto;margin-right:auto}
.tp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.tp-card{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px;transition:all .3s}
.tp-card:hover{border-color:rgba(0,212,170,.3);transform:translateY(-3px)}
.tp-card .icon{font-size:2rem;margin-bottom:16px}
.tp-card h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:10px}
.tp-card p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-bridges{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:40px}
.tp-bridge{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:20px;text-align:center;transition:all .3s}
.tp-bridge:hover{border-color:rgba(0,212,170,.3)}
.tp-bridge .emoji{font-size:2rem;margin-bottom:8px}
.tp-bridge h4{color:#fff;font-size:.9rem;margin-bottom:4px;font-family:'Space Grotesk',sans-serif}
.tp-bridge p{color:#a0a0b8;font-size:.75rem}
.tp-vs{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:40px}
.tp-vs-col{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px}
.tp-vs-col h3{font-family:'Space Grotesk',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:20px}
.tp-vs-col.them h3{color:#ff4757}
.tp-vs-col.us h3{color:#00d4aa}
.tp-vs-col ul{list-style:none;padding:0}
.tp-vs-col ul li{padding:8px 0;font-size:.9rem;color:#c0c0d0;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(30,30,62,.5)}
.tp-vs-col.them li::before{content:'✕';color:#ff4757;font-weight:700}
.tp-vs-col.us li::before{content:'✓';color:#00d4aa;font-weight:700}
.tp-steps{counter-reset:step}
.tp-step{display:flex;gap:24px;align-items:flex-start;margin-bottom:30px}
.tp-step::before{counter-increment:step;content:counter(step);font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#00d4aa;background:rgba(0,212,170,.1);width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tp-step-content h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:6px}
.tp-step-content p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-cta{padding:80px 24px;text-align:center}
.tp-cta .container{max-width:800px;margin:0 auto}
.tp-cta h2{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff;margin-bottom:16px}
.tp-cta h2 span{color:#00d4aa}
.tp-cta p{color:#a0a0b8;margin-bottom:30px;line-height:1.7}
.tp-cta-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
@media(max-width:768px){.tp-vs{grid-template-columns:1fr}.tp-bridges{grid-template-columns:1fr 1fr}}
</style>

<section class="tp-hero">
    <div class="container">
        <div class="tp-badge"><i class="fas fa-comments"></i> Secure Messaging</div>
        <h1>Your Own <span>Slack</span> — Encrypted,<br>Federated, Self-Hosted</h1>
        <p class="sub">Element is the open-source messaging platform built on the Matrix protocol. End-to-end encrypted by default. Bridges to Slack, Discord, Telegram, and more. Self-host on GoSiteMe.</p>
        <div class="tp-hero-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Deploy Element</a>
            <a href="https://github.com/element-hq/element-web" target="_blank" rel="noopener" class="tp-btn tp-btn-ghost"><i class="fab fa-github"></i> View on GitHub</a>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Features</h2>
        <p class="tp-subtitle">Enterprise messaging that puts privacy and control first.</p>
        <div class="tp-grid">
            <div class="tp-card"><div class="icon">🔐</div><h3>End-to-End Encryption</h3><p>Every message, file, and call encrypted with Olm/Megolm (Double Ratchet). Even the server admin can't read messages.</p></div>
            <div class="tp-card"><div class="icon">📹</div><h3>Voice & Video Calls</h3><p>1:1 and group calls with screen sharing. Powered by WebRTC — low latency, high quality, fully encrypted.</p></div>
            <div class="tp-card"><div class="icon">🧵</div><h3>Threads & Spaces</h3><p>Organize conversations with threads. Group Rooms into Spaces for teams, projects, or departments.</p></div>
            <div class="tp-card"><div class="icon">🌐</div><h3>Federation</h3><p>Talk to users on any Matrix server worldwide. Like email — your server, their server, everyone connected.</p></div>
            <div class="tp-card"><div class="icon">📎</div><h3>Rich Media</h3><p>Share files, images, stickers, reactions, and formatted text. Markdown support, code blocks, and inline LaTeX.</p></div>
            <div class="tp-card"><div class="icon">🤖</div><h3>Bots & Integrations</h3><p>Connect to RSS feeds, GitHub, Jira, CI/CD, and custom webhooks. Build bots with the Matrix Bot SDK.</p></div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">Bridge to Everything</h2>
        <p class="tp-subtitle">Connect your Matrix server to existing platforms your contacts already use.</p>
        <div class="tp-bridges">
            <div class="tp-bridge"><div class="emoji">💬</div><h4>Slack</h4><p>Bidirectional bridge</p></div>
            <div class="tp-bridge"><div class="emoji">🎮</div><h4>Discord</h4><p>Full message sync</p></div>
            <div class="tp-bridge"><div class="emoji">✈️</div><h4>Telegram</h4><p>Puppet bridging</p></div>
            <div class="tp-bridge"><div class="emoji">📱</div><h4>WhatsApp</h4><p>Via mautrix-whatsapp</p></div>
            <div class="tp-bridge"><div class="emoji">💌</div><h4>Signal</h4><p>Via mautrix-signal</p></div>
            <div class="tp-bridge"><div class="emoji">📧</div><h4>Email</h4><p>Via matrix-email</p></div>
            <div class="tp-bridge"><div class="emoji">💻</div><h4>IRC</h4><p>Native bridge</p></div>
            <div class="tp-bridge"><div class="emoji">📞</div><h4>SMS</h4><p>Via bridges</p></div>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Slack vs. Element</h2>
        <p class="tp-subtitle">Why security-conscious teams choose Element.</p>
        <div class="tp-vs">
            <div class="tp-vs-col them">
                <h3>Slack Pro</h3>
                <ul>
                    <li>$8.75/user/month ($1,050/yr for 10)</li>
                    <li>Messages hosted on Slack servers</li>
                    <li>No end-to-end encryption</li>
                    <li>Slack reads your data for AI features</li>
                    <li>Closed source, no self-hosting</li>
                    <li>Vendor lock-in — hard to export</li>
                </ul>
            </div>
            <div class="tp-vs-col us">
                <h3>Element + GoSiteMe</h3>
                <ul>
                    <li>$0 extra — included with hosting</li>
                    <li>All data on YOUR server</li>
                    <li>End-to-end encryption by default</li>
                    <li>No data mining, no AI training</li>
                    <li>Open source — full transparency</li>
                    <li>Federated — no lock-in, easy migration</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">How It Works</h2>
        <p class="tp-subtitle">From zero to secure messaging in three steps.</p>
        <div class="tp-steps">
            <div class="tp-step"><div class="tp-step-content"><h3>Get a GoSiteMe Server</h3><p>Choose any hosting plan. Matrix Synapse homeserver + Element web client are pre-configured in Docker.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Deploy with One Click</h3><p>Launch Synapse + Element from your dashboard. Federation, SSL, and TURN server auto-configured.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Invite Your Team</h3><p>Create accounts, set up Spaces, and optionally bridge to Slack/Discord. Mobile apps available for iOS and Android.</p></div></div>
        </div>
    </div>
</section>

<section class="tp-cta">
    <div class="container">
        <h2>Messaging That <span>Respects Privacy</span></h2>
        <p>End-to-end encrypted. Self-hosted. Federated. No per-seat fees. Deploy Element on your GoSiteMe server and take back control.</p>
        <div class="tp-cta-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Get Hosting + Element</a>
            <a href="/open-source/" class="tp-btn tp-btn-ghost"><i class="fas fa-arrow-left"></i> All Tools</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/site-footer.inc.php'; ?>
