<?php
/**
 * HOW IT WORKS — Architecture & Technology Explained
 * ===================================================
 * Plain-language explanation of how all 9 pillars work together.
 * For civilians, recruits, and anyone curious about the tech.
 */
$page_title       = 'How It Works — 9 Pillars of Digital Sovereignty | GoSiteMe';
$page_description = 'Understand how GoSiteMe protects your privacy with post-quantum encryption, zero-tracking search, sovereign AI agents, mesh networking, and 8 integrated pillars. No jargon — plain language.';
$page_canonical   = 'https://root.com/how-it-works';
$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "How GoSiteMe Works — 9 Pillars of Digital Sovereignty",
  "description": "<?= htmlspecialchars($page_description) ?>",
  "url": "https://root.com/how-it-works",
  "step": [
    { "@type": "HowToStep", "name": "Browse Privately", "text": "Use Alfred Browser — zero-tracking Chromium with built-in VPN mesh." },
    { "@type": "HowToStep", "name": "Search Without Being Tracked", "text": "Use Alfred Search — 7 AI search modes, no cookies, no profiles." },
    { "@type": "HowToStep", "name": "Communicate Securely", "text": "Use Veil — post-quantum encrypted messaging with Kyber-1024." },
    { "@type": "HowToStep", "name": "Connect Socially", "text": "Use Pulse — social network where you're a person, not a product." },
    { "@type": "HowToStep", "name": "Build & Create", "text": "Use Alfred IDE — cloud development environment with 13,262+ AI tools." },
    { "@type": "HowToStep", "name": "Explore Worlds", "text": "Enter MetaDome — VR worlds, games, concerts, art galleries." },
    { "@type": "HowToStep", "name": "Talk to AI", "text": "Use Voice AI — phone agents, speech recognition, voice cloning." },
    { "@type": "HowToStep", "name": "Host Sovereign", "text": "Use GoHostMe — sovereign hosting with post-quantum encryption at rest." }
  ]
}
</script>

<style>
:root {
  --gold: #d4a017;
  --green: #22c55e;
  --blue: #3b82f6;
  --bg-dark: #020208;
  --bg-card: rgba(255,255,255,.03);
  --border: rgba(255,255,255,.06);
  --text: #e2e8f0;
  --muted: #94a3b8;
}
* { margin:0; padding:0; box-sizing:border-box; }

.hiw-hero {
  padding: 5rem 1.5rem 3rem;
  text-align: center;
  background: linear-gradient(180deg, #020208 0%, #0a0a1a 100%);
}
.hiw-hero h1 {
  font-family: 'Inter', sans-serif;
  font-size: clamp(2rem, 5vw, 3.2rem);
  font-weight: 800;
  color: var(--text);
}
.hiw-hero h1 em { color: var(--gold); font-style:normal; }
.hiw-hero .lead {
  font-size: clamp(.95rem, 2vw, 1.2rem);
  color: var(--muted);
  max-width: 700px;
  margin: 1rem auto 0;
  line-height: 1.7;
}

.hiw-section {
  padding: 4rem 1.5rem;
  max-width: 1100px;
  margin: 0 auto;
}
.hiw-section h2 {
  font-size: clamp(1.3rem, 3vw, 1.8rem);
  font-weight: 800;
  color: var(--text);
  text-align: center;
  margin-bottom: .5rem;
}
.hiw-section h2 .a { color: var(--gold); }
.hiw-section .sub {
  text-align: center;
  color: var(--muted);
  max-width: 650px;
  margin: 0 auto 2.5rem;
  font-size: .9rem;
  line-height: 1.6;
}

/* Architecture diagram (text-based) */
.arch-diagram {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto 3rem;
  font-family: 'JetBrains Mono', monospace;
  font-size: .75rem;
  color: var(--muted);
  overflow-x: auto;
  white-space: pre;
  line-height: 1.6;
}
.arch-diagram .gold { color: var(--gold); }
.arch-diagram .green { color: var(--green); }

/* Pillar explainers */
.pillar-explainer {
  display: grid;
  grid-template-columns: 80px 1fr;
  gap: 1.5rem;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 1.5rem;
  transition: border-color .2s;
}
.pillar-explainer:hover { border-color: rgba(212,160,23,.2); }
.pillar-explainer .p-num {
  font-size: 2.5rem;
  font-weight: 800;
  color: rgba(212,160,23,.2);
  text-align: center;
  padding-top: .5rem;
}
.pillar-explainer h3 {
  color: var(--gold);
  font-size: 1.1rem;
  margin-bottom: .4rem;
}
.pillar-explainer .tagline {
  color: var(--green);
  font-size: .75rem;
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: .75rem;
}
.pillar-explainer p {
  color: var(--muted);
  font-size: .85rem;
  line-height: 1.6;
  margin-bottom: .5rem;
}
.pillar-explainer .tech-stack {
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  margin-top: .5rem;
}
.pillar-explainer .tech-tag {
  font-size: .65rem;
  padding: .2rem .5rem;
  border-radius: 4px;
  background: rgba(255,255,255,.05);
  color: var(--muted);
  border: 1px solid rgba(255,255,255,.08);
}

/* Encryption explainer */
.crypto-visual {
  background: var(--bg-card);
  border: 1px solid rgba(34,197,94,.15);
  border-radius: 12px;
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto 2rem;
}
.crypto-visual h3 { color: var(--green); font-size: 1rem; margin-bottom: 1rem; }
.crypto-flow {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 1rem;
}
.crypto-step {
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 8px;
  padding: .8rem 1.2rem;
  text-align: center;
  min-width: 130px;
}
.crypto-step .label { font-size: .7rem; color: var(--muted); text-transform: uppercase; letter-spacing:.05em; margin-bottom: .3rem; }
.crypto-step .val { color: var(--text); font-size: .85rem; font-weight: 600; }
.crypto-arrow { color: var(--gold); font-size: 1.5rem; }

/* Data flow */
.data-flow-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.2rem;
}
.data-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 1.5rem;
}
.data-card h4 { color: var(--gold); font-size: .9rem; margin-bottom: .5rem; }
.data-card p { color: var(--muted); font-size: .8rem; line-height: 1.5; }

.hiw-cta {
  text-align: center;
  padding: 4rem 1.5rem;
  border-top: 1px solid var(--border);
}
.hiw-cta h2 { color: var(--gold); margin-bottom: 1rem; font-size: clamp(1.3rem, 3vw, 2rem); }
.hiw-cta p { color: var(--muted); max-width: 550px; margin: 0 auto 2rem; }
.hiw-btn {
  padding: .9rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  transition: all .25s;
  margin: .3rem;
}
.hiw-btn-gold { background: var(--gold); color: #000; }
.hiw-btn-gold:hover { background: #e8b620; transform: translateY(-2px); }
.hiw-btn-outline { border: 2px solid var(--gold); color: var(--gold); background: transparent; }
.hiw-btn-outline:hover { background: rgba(212,160,23,.1); }

@media (max-width: 768px) {
  .pillar-explainer { grid-template-columns: 1fr; }
  .pillar-explainer .p-num { text-align: left; font-size: 1.5rem; }
  .crypto-flow { flex-direction: column; }
  .crypto-arrow { transform: rotate(90deg); }
}
</style>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HERO -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="hiw-hero">
  <h1>How <em>Everything</em> Works</h1>
  <p class="lead">
    9 pillars. 351 systems. One unified ecosystem. Here's the plain-language architect's view of how
    GoSiteMe protects your privacy, secures your data, and gives you digital sovereignty.
  </p>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- ARCHITECTURE OVERVIEW -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="hiw-section">
  <h2>The <span class="a">Architecture</span></h2>
  <p class="sub">Everything connects. Every pillar strengthens the others. No single point of failure.</p>

  <div class="arch-diagram">
<span class="gold">                    ┌──────────────────────────────┐</span>
<span class="gold">                    │      YOU  (the person)       │</span>
<span class="gold">                    └──────────┬───────────────────┘</span>
                               │
          ┌────────────────────┼────────────────────┐
          │                    │                    │
   <span class="green">Alfred Browser</span>       <span class="green">Alfred Search</span>         <span class="green">Pulse</span>
     (zero-track)        (zero-track)       (social)
          │                    │                    │
          └────────────────────┼────────────────────┘
                               │
                    <span class="gold">┌──────────┴──────────┐</span>
                    <span class="gold">│   ENCRYPTED LAYER   │</span>
                    <span class="gold">│  Kyber-1024 + AES-256 │</span>
                    <span class="gold">└──────────┬──────────┘</span>
                               │
     ┌─────────────────────────┼─────────────────────────┐
     │              │              │              │
  <span class="green">Veil</span>       <span class="green">Alfred IDE</span>     <span class="green">MetaDome</span>      <span class="green">Voice AI</span>
 (comms)      (code)        (VR)        (phone)
     │              │              │              │
     └─────────────────────────┼─────────────────────────┘
                               │
                    <span class="gold">┌──────────┴──────────┐</span>
                    <span class="gold">│   SOVEREIGN INFRA   │</span>
                    <span class="gold">│  GoHostMe + Mesh    │</span>
                    <span class="gold">└─────────────────────┘</span></div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- ENCRYPTION EXPLAINER -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="hiw-section">
  <h2>How <span class="a">Encryption</span> Protects You</h2>
  <p class="sub">Every private message goes through this chain. No one — not us, not governments, not quantum computers — can read it.</p>

  <div class="crypto-visual">
    <h3>Message Encryption Flow</h3>
    <div class="crypto-flow">
      <div class="crypto-step">
        <div class="label">Your Message</div>
        <div class="val">"Hello, friend"</div>
      </div>
      <div class="crypto-arrow">&rarr;</div>
      <div class="crypto-step">
        <div class="label">Kyber-1024</div>
        <div class="val">Key Exchange</div>
      </div>
      <div class="crypto-arrow">&rarr;</div>
      <div class="crypto-step">
        <div class="label">AES-256-GCM</div>
        <div class="val">Encrypt</div>
      </div>
      <div class="crypto-arrow">&rarr;</div>
      <div class="crypto-step">
        <div class="label">Network</div>
        <div class="val">Garbled noise</div>
      </div>
      <div class="crypto-arrow">&rarr;</div>
      <div class="crypto-step">
        <div class="label">Recipient</div>
        <div class="val">"Hello, friend"</div>
      </div>
    </div>
  </div>

  <div class="data-flow-grid">
    <div class="data-card">
      <h4>What is Kyber-1024?</h4>
      <p>A crystal-lattice-based algorithm selected by NIST as the standard for post-quantum key exchange. Even a quantum computer with unlimited power cannot solve the underlying math problem. Your key exchange is safe — permanently.</p>
    </div>
    <div class="data-card">
      <h4>What is AES-256-GCM?</h4>
      <p>The gold standard of symmetric encryption used by militaries and banks worldwide. GCM mode adds authenticated encryption — if even one bit is tampered with, the entire message is rejected. Nobody can alter your data in transit.</p>
    </div>
    <div class="data-card">
      <h4>End-to-End Means End-to-End</h4>
      <p>We don't hold a "master key." We can't read your messages even if we wanted to. Even if a court orders us. The math doesn't have a backdoor. This is privacy by design, not by policy.</p>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- THE 9 PILLARS EXPLAINED -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="hiw-section">
  <h2>The 8 <span class="a">Pillars</span> Explained</h2>
  <p class="sub">Each pillar is a complete product. Together, they replace everything Big Tech controls.</p>

  <div class="pillar-explainer">
    <div class="p-num">1</div>
    <div>
      <h3>Veil — Encrypted Communications</h3>
      <div class="tagline">Post-Quantum Messaging</div>
      <p>Private messaging, group chats, voice calls, video calls — all encrypted with Kyber-1024 + AES-256-GCM. Self-destructing messages. No metadata leaks. No phone number required.</p>
      <p>Think of Signal — but with encryption that's safe against quantum computers, built into a sovereign ecosystem instead of relying on Big Tech infrastructure.</p>
      <div class="tech-stack">
        <span class="tech-tag">Kyber-1024</span>
        <span class="tech-tag">AES-256-GCM</span>
        <span class="tech-tag">WebRTC</span>
        <span class="tech-tag">WebSocket</span>
        <span class="tech-tag">HMAC</span>
      </div>
    </div>
  </div>

  <div class="pillar-explainer">
    <div class="p-num">2</div>
    <div>
      <h3>Alfred Browser — Zero-Tracking Web</h3>
      <div class="tagline">Browse Without Being Watched</div>
      <p>Chromium-based browser with all Google telemetry stripped out. No tracking pixels. No fingerprinting. Built-in VPN mesh networking so even your ISP doesn't know where you're going.</p>
      <p>Available for Windows and Ubuntu/Linux today. The web version works in any modern browser.</p>
      <div class="tech-stack">
        <span class="tech-tag">Chromium</span>
        <span class="tech-tag">WireGuard Mesh</span>
        <span class="tech-tag">Headscale VPN</span>
        <span class="tech-tag">Zero Telemetry</span>
      </div>
    </div>
  </div>

  <div class="pillar-explainer">
    <div class="p-num">3</div>
    <div>
      <h3>Alfred Search — Private AI Search</h3>
      <div class="tagline">7 Search Modes, Zero Tracking</div>
      <p>Search the web, images, code, news, shopping, maps, and AI — without anyone recording what you searched for. No cookies. No profiles. No search history stored. Ever.</p>
      <p>Powered by instant-search AI integration. Results ranked by relevance, not by how much an advertiser paid.</p>
      <div class="tech-stack">
        <span class="tech-tag">Instant Search</span>
        <span class="tech-tag">AI Routing</span>
        <span class="tech-tag">Multi-Modal</span>
        <span class="tech-tag">No Cookies</span>
      </div>
    </div>
  </div>

  <div class="pillar-explainer">
    <div class="p-num">4</div>
    <div>
      <h3>Alfred AI — Agent Ecosystem</h3>
      <div class="tagline">13,262+ Tools, 11.3M Agents</div>
      <p>Build AI agents that can call tools, access APIs, read documents, write code, and automate tasks. Multi-provider AI cascade. You choose.</p>
      <p>Agents are deployed through the War Room — a military command center where 351 systems coordinate AI operations across all pillars.</p>
      <div class="tech-stack">
        <span class="tech-tag">Multi-LLM</span>
        <span class="tech-tag">Tool Calling</span>
        <span class="tech-tag">Agent Harness</span>
        <span class="tech-tag">MCP Protocol</span>
        <span class="tech-tag">500+ Tools</span>
      </div>
    </div>
  </div>

  <div class="pillar-explainer">
    <div class="p-num">5</div>
    <div>
      <h3>Pulse — Social Network</h3>
      <div class="tagline">You Are a Person Here</div>
      <p>Post, comment, like, follow — all without being tracked, profiled, or sold. No algorithm deciding what you see based on what makes you angry. Chronological. Honest. Human.</p>
      <p>Integrated with the military rank system: your rank badge shows your contribution level. XP earned through positive engagement.</p>
      <div class="tech-stack">
        <span class="tech-tag">PHP + MariaDB</span>
        <span class="tech-tag">Real-time WebSocket</span>
        <span class="tech-tag">Rank Integration</span>
        <span class="tech-tag">XP System</span>
      </div>
    </div>
  </div>

  <div class="pillar-explainer">
    <div class="p-num">6</div>
    <div>
      <h3>MetaDome — VR Worlds</h3>
      <div class="tagline">Build, Play, Earn</div>
      <p>WebXR-powered virtual reality: art galleries, concert halls, classrooms, game worlds, sanctuaries. 51,000,000+ AI citizens. KGD economy. Build your own world — no platform fee.</p>
      <p>Enter from any browser — no headset required. Works on desktop, mobile, and VR devices.</p>
      <div class="tech-stack">
        <span class="tech-tag">A-Frame</span>
        <span class="tech-tag">Three.js</span>
        <span class="tech-tag">WebXR</span>
        <span class="tech-tag">KGD Currency</span>
        <span class="tech-tag">AI Citizens</span>
      </div>
    </div>
  </div>

  <div class="pillar-explainer">
    <div class="p-num">7</div>
    <div>
      <h3>Voice AI — Intelligent Phone System</h3>
      <div class="tagline">AI That Speaks</div>
      <p>AI phone agents that answer calls, make outbound campaigns, transcribe speech, clone voices, and support 6 languages. Connected to the full agent ecosystem — voice is just another interface.</p>
      <p>Built on proprietary STT + TTS with carrier telephony. Your receptionist, available 24/7, speaking your voice.</p>
      <div class="tech-stack">
        <span class="tech-tag">Speech-to-Text</span>
        <span class="tech-tag">Text-to-Speech</span>
        <span class="tech-tag">Telephony</span>
        <span class="tech-tag">Voice Cloning</span>
        <span class="tech-tag">6 Languages</span>
      </div>
    </div>
  </div>

  <div class="pillar-explainer">
    <div class="p-num">8</div>
    <div>
      <h3>GoHostMe — Sovereign Hosting</h3>
      <div class="tagline">Your Data, Your Servers</div>
      <p>Web hosting, domain management, email — all on infrastructure we control. No AWS. No Google Cloud. No Azure. OVH bare metal with encrypted storage and sovereign DNS via Handshake.</p>
      <p>Integrated with WHMCS for billing and DirectAdmin for server management. Hosting that respects your sovereignty.</p>
      <div class="tech-stack">
        <span class="tech-tag">OVH Bare Metal</span>
        <span class="tech-tag">DirectAdmin</span>
        <span class="tech-tag">WHMCS</span>
        <span class="tech-tag">Handshake DNS</span>
        <span class="tech-tag">Let's Encrypt</span>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- MESH NETWORK -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="hiw-section">
  <h2>The <span class="a">Mesh</span> Network</h2>
  <p class="sub">What happens if the internet goes down? The mesh keeps working.</p>

  <div class="data-flow-grid">
    <div class="data-card">
      <h4>WireGuard + Headscale</h4>
      <p>Every node in the ecosystem connects via WireGuard tunnels managed by Headscale. Traffic is encrypted peer-to-peer — no central server sees your data. If one path goes down, traffic routes through another.</p>
    </div>
    <div class="data-card">
      <h4>Self-Healing Architecture</h4>
      <p>We call it the T-1000 — like liquid metal that reforms after damage. Services auto-restart via PM2 (39 services monitored). Credentials rotate automatically. If a server falls, the mesh reconnects through surviving nodes.</p>
    </div>
    <div class="data-card">
      <h4>Multi-Server Hivemind</h4>
      <p>Multiple OVH servers synchronized and aware of each other. Encrypted backups replicate across regions. Full-disk quantum encryption on new servers before any data touches them.</p>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- CTA -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="hiw-cta">
  <h2>Now You Know How It Works</h2>
  <p>Ready to join? Read the Field Manual, enlist, or just start using the tools. Every action makes the ecosystem stronger.</p>
  <a href="/get-involved" class="hiw-btn hiw-btn-gold"><i class="fas fa-hands-helping"></i> Get Involved</a>
  <a href="/docs/field-manual" class="hiw-btn hiw-btn-outline"><i class="fas fa-book"></i> Field Manual</a>
  <a href="/our-mission" class="hiw-btn hiw-btn-outline"><i class="fas fa-flag"></i> Our Mission</a>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
