<?php
/**
 * GET INVOLVED — Public Recruitment & Participation Landing Page
 * ==============================================================
 * The front door for EVERYONE on Earth to join the GoSiteMe mission.
 * 351 War Room systems. 9 Pillars. Room for every human being.
 * No login required — this is the global invitation.
 */
$page_title       = 'Get Involved — Join 351 Systems Changing the Planet | GoSiteMe';
$page_description = 'GoSiteMe has room for everyone. 351 operational systems, 8 sovereign pillars, and a mission to give privacy, security, and sovereignty back to the world. Find your role — no tech skills needed.';
$page_canonical   = 'https://root.com/get-involved';
$page_og_image    = 'https://root.com/assets/img/og-get-involved.png';
$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';

// Pull live stats
try {
    require_once __DIR__ . '/includes/db-config.inc.php';
    $db = getSharedDB();
    $warRoomCount  = 351;
    $rosterCount   = $db->query("SELECT COUNT(*) FROM user_ranks WHERE is_active=1")->fetchColumn() ?: 0;
    $deptCount     = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn() ?: 12;
    $agentCount    = $db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn() ?: 0;
    $pulseCount    = $db->query("SELECT COUNT(*) FROM pulse_posts")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $warRoomCount = 351; $rosterCount = 3; $deptCount = 12; $agentCount = 11300000; $pulseCount = 0;
}
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Get Involved with GoSiteMe",
  "description": "<?= htmlspecialchars($page_description) ?>",
  "url": "https://root.com/get-involved",
  "isPartOf": {
    "@type": "WebSite",
    "name": "GoSiteMe",
    "url": "https://root.com"
  },
  "about": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "description": "A sovereign digital ecosystem with 351 operational command systems, 9 pillars, and a mission to help the planet.",
    "founder": {
      "@type": "Person",
      "name": "Danny William Perez"
    },
    "knowsAbout": ["Post-Quantum Encryption", "AI Agents", "Zero-Tracking Search", "Privacy Browser", "VR Worlds", "Voice AI", "Sovereign Hosting", "Open Source IDE"]
  },
  "mainEntity": {
    "@type": "JoinAction",
    "name": "Enlist in GoSiteMe",
    "target": "https://root.com/docs/field-manual",
    "description": "Join a digital institution with 351 operational systems and room for every skill level."
  }
}
</script>

<style>
:root {
  --gold: #d4a017;
  --green: #22c55e;
  --bg-dark: #020208;
  --bg-card: rgba(255,255,255,.03);
  --border: rgba(255,255,255,.06);
  --text: #e2e8f0;
  --muted: #94a3b8;
}
* { margin:0; padding:0; box-sizing:border-box; }

.gi-hero {
  position: relative;
  padding: 5rem 1.5rem 4rem;
  text-align: center;
  background: linear-gradient(135deg, #020208 0%, #0a0a1a 40%, #0d1117 100%);
  overflow: hidden;
}
.gi-hero::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle at 30% 40%, rgba(212,160,23,.06) 0%, transparent 50%),
              radial-gradient(circle at 70% 60%, rgba(34,197,94,.04) 0%, transparent 40%);
  animation: heroGlow 15s ease-in-out infinite;
}
@keyframes heroGlow {
  0%,100% { transform: translate(0,0); }
  50% { transform: translate(-30px, -20px); }
}
.gi-hero h1 {
  font-family: 'Inter', sans-serif;
  font-size: clamp(2.2rem, 5vw, 3.8rem);
  font-weight: 800;
  color: var(--gold);
  letter-spacing: -.02em;
  position: relative;
  z-index: 1;
}
.gi-hero h1 span { color: var(--green); }
.gi-hero .subtitle {
  font-size: clamp(1rem, 2vw, 1.35rem);
  color: var(--muted);
  max-width: 750px;
  margin: 1rem auto 2rem;
  line-height: 1.6;
  position: relative;
  z-index: 1;
}

/* Live counters */
.gi-counters {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1.5rem;
  margin: 2rem auto;
  max-width: 900px;
  position: relative;
  z-index: 1;
}
.gi-counter {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.2rem 1.8rem;
  min-width: 150px;
  text-align: center;
}
.gi-counter .num {
  font-size: 2rem;
  font-weight: 800;
  color: var(--gold);
  display: block;
}
.gi-counter .lab {
  font-size: .75rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-top: 4px;
}

/* CTA buttons */
.gi-cta-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
  position: relative;
  z-index: 1;
}
.gi-btn {
  padding: .9rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  transition: all .25s;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}
.gi-btn-gold {
  background: var(--gold);
  color: #000;
}
.gi-btn-gold:hover { background: #e8b620; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(212,160,23,.3); }
.gi-btn-outline {
  border: 2px solid var(--gold);
  color: var(--gold);
  background: transparent;
}
.gi-btn-outline:hover { background: rgba(212,160,23,.1); }

/* Sections */
.gi-section {
  padding: 4rem 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}
.gi-section h2 {
  font-size: clamp(1.5rem, 3vw, 2.2rem);
  font-weight: 800;
  color: var(--text);
  text-align: center;
  margin-bottom: .5rem;
}
.gi-section h2 .accent { color: var(--gold); }
.gi-section .section-sub {
  text-align: center;
  color: var(--muted);
  max-width: 700px;
  margin: 0 auto 2.5rem;
  font-size: .95rem;
  line-height: 1.6;
}

/* Role cards */
.gi-roles {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}
.gi-role {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 2rem;
  transition: all .3s;
}
.gi-role:hover {
  border-color: var(--gold);
  transform: translateY(-4px);
  box-shadow: 0 12px 36px rgba(0,0,0,.4);
}
.gi-role .icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}
.gi-role h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--gold);
  margin-bottom: .5rem;
}
.gi-role p {
  color: var(--muted);
  font-size: .85rem;
  line-height: 1.6;
  margin-bottom: 1rem;
}
.gi-role ul {
  list-style: none;
  padding: 0;
}
.gi-role ul li {
  font-size: .8rem;
  color: var(--text);
  padding: .3rem 0;
  border-bottom: 1px solid rgba(255,255,255,.03);
}
.gi-role ul li::before {
  content: '\2713';
  color: var(--green);
  margin-right: 8px;
  font-weight: 700;
}

/* Pillars grid */
.gi-pillars {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.2rem;
}
.gi-pillar {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 1.5rem;
  transition: border-color .2s;
}
.gi-pillar:hover { border-color: rgba(212,160,23,.3); }
.gi-pillar .p-icon { font-size: 1.8rem; margin-bottom: .75rem; }
.gi-pillar h4 { color: var(--gold); font-size: .95rem; margin-bottom: .4rem; }
.gi-pillar p { color: var(--muted); font-size: .78rem; line-height: 1.5; margin-bottom: .6rem; }
.gi-pillar a {
  color: var(--green);
  font-size: .75rem;
  text-decoration: none;
  font-weight: 600;
}
.gi-pillar a:hover { text-decoration: underline; }

/* Rank ladder */
.gi-ranks {
  display: flex;
  flex-direction: column;
  gap: 0;
  max-width: 700px;
  margin: 0 auto;
}
.gi-rank-row {
  display: flex;
  align-items: center;
  padding: 1rem 1.5rem;
  border-left: 3px solid var(--border);
  position: relative;
  transition: border-color .2s;
}
.gi-rank-row:hover { border-left-color: var(--gold); }
.gi-rank-row .tier {
  font-size: .65rem;
  color: var(--muted);
  min-width: 55px;
  text-transform: uppercase;
  letter-spacing: .1em;
}
.gi-rank-row .name {
  font-weight: 700;
  color: var(--text);
  font-size: .95rem;
  flex: 1;
}
.gi-rank-row .unlock {
  font-size: .75rem;
  color: var(--muted);
  text-align: right;
  max-width: 250px;
}
.gi-rank-row.supreme { border-left-color: var(--gold); background: rgba(212,160,23,.05); }

/* How it works steps */
.gi-steps {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}
.gi-step {
  text-align: center;
  padding: 1.5rem;
}
.gi-step .step-num {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(212,160,23,.15);
  color: var(--gold);
  font-size: 1.2rem;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}
.gi-step h4 { color: var(--text); font-size: .95rem; margin-bottom: .5rem; }
.gi-step p { color: var(--muted); font-size: .8rem; line-height: 1.5; }

/* FAQ */
.gi-faq { max-width: 800px; margin: 0 auto; }
.gi-faq details {
  border-bottom: 1px solid var(--border);
  padding: 1rem 0;
}
.gi-faq summary {
  cursor: pointer;
  font-weight: 700;
  color: var(--text);
  font-size: .95rem;
  list-style: none;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.gi-faq summary::after { content: '+'; color: var(--gold); font-size: 1.2rem; }
.gi-faq details[open] summary::after { content: '−'; }
.gi-faq .answer {
  color: var(--muted);
  font-size: .85rem;
  line-height: 1.6;
  margin-top: .75rem;
  padding-left: .5rem;
}

/* Final CTA */
.gi-final-cta {
  text-align: center;
  padding: 4rem 1.5rem;
  background: linear-gradient(135deg, rgba(212,160,23,.05) 0%, rgba(34,197,94,.03) 100%);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
.gi-final-cta h2 { font-size: clamp(1.5rem, 3vw, 2.5rem); color: var(--gold); margin-bottom: 1rem; }
.gi-final-cta p { color: var(--muted); max-width: 600px; margin: 0 auto 2rem; font-size: 1rem; }

@media (max-width: 768px) {
  .gi-roles { grid-template-columns: 1fr; }
  .gi-pillars { grid-template-columns: 1fr 1fr; }
  .gi-rank-row .unlock { display: none; }
}
</style>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HERO -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gi-hero">
  <h1>Room for <span>Everyone</span></h1>
  <p class="subtitle">
    351 operational systems. 8 sovereign pillars. Post-quantum encryption. Zero-tracking search.
    AI agents. VR worlds. Voice AI. And a mission to give privacy, security, and sovereignty back to
    every person on Earth. <strong>We need you.</strong>
  </p>

  <div class="gi-counters">
    <div class="gi-counter"><span class="num"><?= number_format($warRoomCount) ?></span><span class="lab">Command Systems</span></div>
    <div class="gi-counter"><span class="num"><?= number_format($agentCount) ?></span><span class="lab">AI Agents</span></div>
    <div class="gi-counter"><span class="num"><?= $deptCount ?></span><span class="lab">Departments</span></div>
    <div class="gi-counter"><span class="num"><?= number_format($rosterCount) ?></span><span class="lab">Active Personnel</span></div>
  </div>

  <div class="gi-cta-row">
    <a href="/docs/field-manual" class="gi-btn gi-btn-gold"><i class="fas fa-book"></i> Read the Field Manual</a>
    <a href="/login.php" class="gi-btn gi-btn-outline"><i class="fas fa-user-plus"></i> Enlist Now</a>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- FIND YOUR ROLE -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gi-section">
  <h2>Find Your <span class="accent">Role</span></h2>
  <p class="section-sub">
    You don't need to be a developer. You don't need to be technical. Every skill helps.
    Every person matters. Here's how you can contribute:
  </p>

  <div class="gi-roles">

    <div class="gi-role">
      <div class="icon">&#x1F30D;</div>
      <h3>Community Ambassador</h3>
      <p>Share the mission with your network. Help people discover privacy tools that respect them.</p>
      <ul>
        <li>Spread the word on social media</li>
        <li>Host local meetups or online sessions</li>
        <li>Help new recruits get oriented</li>
        <li>Earn 100 XP per referral</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F4AC;</div>
      <h3>Content Moderator</h3>
      <p>Keep Pulse (our social network) positive, inclusive, and free from abuse. Build community.</p>
      <ul>
        <li>Review flagged content on Pulse</li>
        <li>Welcome newcomers</li>
        <li>Maintain community standards</li>
        <li>Rank up to NCO for mod powers</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F310;</div>
      <h3>Translator</h3>
      <p>Make GoSiteMe accessible to the entire world. We support English and French — and need more.</p>
      <ul>
        <li>Translate pages and documentation</li>
        <li>Localize the Field Manual</li>
        <li>Help with Voice AI language packs</li>
        <li>Earn 30 XP per translation</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F4BB;</div>
      <h3>Agent Developer</h3>
      <p>Build AI agents using 13,000+ tools. Deploy them to help people automate tasks and solve problems.</p>
      <ul>
        <li>Build custom AI agents</li>
        <li>Create IDE extensions</li>
        <li>Contribute to open source core</li>
        <li>Earn 200 XP per extension published</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F3AE;</div>
      <h3>VR World Builder</h3>
      <p>Design 3D experiences in MetaDome. Build art galleries, concert halls, classrooms, parks.</p>
      <ul>
        <li>Create WebXR experiences</li>
        <li>Design virtual classrooms</li>
        <li>Build game worlds</li>
        <li>Earn KGD currency for creations</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x2696;</div>
      <h3>Legal Advocate</h3>
      <p>Fight for digital sovereignty, privacy rights, and internet freedom. The legal battle matters.</p>
      <ul>
        <li>Review terms and policies</li>
        <li>Advocate for digital rights</li>
        <li>Support the sovereignty framework</li>
        <li>Serve in Military Court (Officer rank)</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F3A4;</div>
      <h3>Voice & Creative</h3>
      <p>Voice actors, musicians, artists, writers — your creativity powers the ecosystem.</p>
      <ul>
        <li>Clone voices for AI phone agents</li>
        <li>Create music for VR concerts</li>
        <li>Design UI/graphics/branding</li>
        <li>Write blog posts and documentation</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F6E1;</div>
      <h3>Security Researcher</h3>
      <p>Find vulnerabilities, report bugs, and help keep the kingdom fortress-grade secure.</p>
      <ul>
        <li>Penetration testing</li>
        <li>Code review and audits</li>
        <li>Report vulnerabilities (500 XP each)</li>
        <li>Help maintain post-quantum standards</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F393;</div>
      <h3>Teacher & Mentor</h3>
      <p>Create educational content, teach coding, mentor new recruits through their first steps.</p>
      <ul>
        <li>Build tutorials and courses</li>
        <li>Mentor recruits 1-on-1</li>
        <li>Create department training materials</li>
        <li>Lead educational events</li>
      </ul>
    </div>

    <div class="gi-role">
      <div class="icon">&#x1F50D;</div>
      <h3>Just Use It</h3>
      <p>Even browsing the web with Alfred Browser or searching with Alfred Search helps the mission.</p>
      <ul>
        <li>Use Alfred Search (zero-tracking)</li>
        <li>Browse with Alfred Browser</li>
        <li>Play games in MetaDome</li>
        <li>Post on Pulse — earn 5 XP each</li>
      </ul>
    </div>

  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- THE 9 PILLARS -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gi-section">
  <h2>The <span class="accent">9 Pillars</span></h2>
  <p class="section-sub">Every pillar is a dimension of digital sovereignty. Together, they form a complete alternative to Big Tech — built with love, encrypted with science, and open to everyone.</p>

  <div class="gi-pillars">
    <div class="gi-pillar">
      <div class="p-icon">&#x1F512;</div>
      <h4>Veil — Encrypted Comms</h4>
      <p>Post-quantum encryption (Kyber-1024 + AES-256-GCM). Messages no computer — not even future quantum computers — can break.</p>
      <a href="/post-quantum.php">Learn about our encryption &rarr;</a>
    </div>
    <div class="gi-pillar">
      <div class="p-icon">&#x1F310;</div>
      <h4>Alfred Browser</h4>
      <p>Zero-tracking Chromium-based browser with built-in VPN mesh networking. Browse without being watched.</p>
      <a href="/alfred-browser.php">Download now &rarr;</a>
    </div>
    <div class="gi-pillar">
      <div class="p-icon">&#x1F50E;</div>
      <h4>Alfred Search</h4>
      <p>Zero-tracking AI search engine. 7 search modes. No cookies, no profiles, no data selling. Ever.</p>
      <a href="/search.php">Try it free &rarr;</a>
    </div>
    <div class="gi-pillar">
      <div class="p-icon">&#x1F916;</div>
      <h4>Alfred AI</h4>
      <p>13,000+ tools, 50 million agents in registry. Build, deploy, and orchestrate AI agents for any task.</p>
      <a href="/alfred-tools.php">Explore AI tools &rarr;</a>
    </div>
    <div class="gi-pillar">
      <div class="p-icon">&#x1F4F1;</div>
      <h4>Pulse</h4>
      <p>Social network where you're the person, not the product. Post, connect, share — without exploitation.</p>
      <a href="/pulse.php">Join the conversation &rarr;</a>
    </div>
    <div class="gi-pillar">
      <div class="p-icon">&#x1F30C;</div>
      <h4>MetaDome</h4>
      <p>VR worlds, games, concerts, art galleries. Free to enter, free to build. Earn KGD currency.</p>
      <a href="/metadome-landing.php">Enter the MetaDome &rarr;</a>
    </div>
    <div class="gi-pillar">
      <div class="p-icon">&#x1F399;</div>
      <h4>Voice AI</h4>
      <p>AI phone agents that speak 6 languages. Speech-to-text, text-to-speech, voice cloning, campaigns.</p>
      <a href="/voice-products.php">Explore Voice AI &rarr;</a>
    </div>
    <div class="gi-pillar">
      <div class="p-icon">&#x1F4BB;</div>
      <h4>Alfred IDE</h4>
      <p>Free cloud development environment. Write code, build extensions, ship products. No Big Tech lock-in.</p>
      <a href="/alfred-ide.php">Launch IDE &rarr;</a>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HOW IT WORKS -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gi-section">
  <h2>How It <span class="accent">Works</span></h2>
  <p class="section-sub">From civilian to commander. Every action earns XP. Every rank unlocks more.</p>

  <div class="gi-steps">
    <div class="gi-step">
      <div class="step-num">1</div>
      <h4>Discover</h4>
      <p>Download Alfred Browser or try Alfred Search. Browse privately. See what's different.</p>
    </div>
    <div class="gi-step">
      <div class="step-num">2</div>
      <h4>Read</h4>
      <p>Read the Field Manual. Understand the mission, the ranks, the code of conduct.</p>
    </div>
    <div class="gi-step">
      <div class="step-num">3</div>
      <h4>Enlist</h4>
      <p>Create an account. Take the Oath. You start as Recruit (Tier 1). Your journey begins.</p>
    </div>
    <div class="gi-step">
      <div class="step-num">4</div>
      <h4>Contribute</h4>
      <p>Post on Pulse. Play in MetaDome. Write code in Alfred IDE. Report bugs. Every action = XP.</p>
    </div>
    <div class="gi-step">
      <div class="step-num">5</div>
      <h4>Rise</h4>
      <p>Earn XP. Advance through 11 ranks. Unlock new tools, mesh access, moderation, and more.</p>
    </div>
    <div class="gi-step">
      <div class="step-num">6</div>
      <h4>Lead</h4>
      <p>At NCO and Officer tiers, you lead others. Moderate. Coordinate. Help shape the future.</p>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- RANK PROGRESSION -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gi-section">
  <h2>The <span class="accent">Rank Ladder</span></h2>
  <p class="section-sub">Every action earns XP. Every rank opens new doors across all 9 pillars.</p>

  <div class="gi-ranks">
    <div class="gi-rank-row">
      <span class="tier">Tier 0</span>
      <span class="name">Civilian</span>
      <span class="unlock">Public pages, search, browser downloads</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 1</span>
      <span class="name">&#9675; Recruit</span>
      <span class="unlock">Pulse, MetaDome basic zones, Alfred Search history</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 2</span>
      <span class="name">&#9675; Private</span>
      <span class="unlock">VR squad access, basic Voice AI, IDE free tier</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 3</span>
      <span class="name">&#9675; Corporal</span>
      <span class="unlock">Extended features, game creation, peer mentoring</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 4</span>
      <span class="name">&#9733; Sergeant</span>
      <span class="unlock">Pulse moderation, VPN mesh basic, War Room view (50 systems)</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 5</span>
      <span class="name">&#9733; Staff Sergeant</span>
      <span class="unlock">Squad channels, squad leadership, advanced mesh</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 6</span>
      <span class="name">&#9733;&#9733; Lieutenant</span>
      <span class="unlock">Veil comms, full War Room view, IDE pro tier</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 7</span>
      <span class="name">&#9733;&#9733; Captain</span>
      <span class="unlock">Department management, campaign creation, trending curation</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 8</span>
      <span class="name">&#9733;&#9733; Major</span>
      <span class="unlock">Full campaign ops, VR experience creation, officer channels</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 9</span>
      <span class="name">&#9733;&#9733;&#9733; Colonel</span>
      <span class="unlock">Full system control, rank management, court judge</span>
    </div>
    <div class="gi-rank-row">
      <span class="tier">Tier 10</span>
      <span class="name">&#9733;&#9733;&#9733;&#9733; General</span>
      <span class="unlock">Veil emergency access, global mesh admin, enterprise IDE</span>
    </div>
    <div class="gi-rank-row supreme">
      <span class="tier">Tier 11</span>
      <span class="name">&#9733;&#9733;&#9733;&#9733;&#9733; &#x1F451; Supreme Commander</span>
      <span class="unlock">All access. All systems. The founder.</span>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- FAQ -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gi-section">
  <h2>Questions? <span class="accent">Answers.</span></h2>
  <div class="gi-faq">

    <details>
      <summary>Is this a real military?</summary>
      <div class="answer">It's a digital institution with military-inspired governance. We use ranks, departments, and chain-of-command to organize a massive technology ecosystem fairly. There are real responsibilities, real court proceedings, and real consequences — but this is about building technology, not fighting wars. We fight for privacy, sovereignty, and the right of every person to control their digital life.</div>
    </details>

    <details>
      <summary>Do I need to pay anything?</summary>
      <div class="answer">No. Enlisting is free. Using Alfred Search is free. Alfred Browser is free to download. MetaDome games are free to play. Pulse is free to post on. Alfred IDE has a free tier. Premium features and hosting plans are available through GoHostMe, but the core mission is accessible to everyone.</div>
    </details>

    <details>
      <summary>What if I'm not technical at all?</summary>
      <div class="answer">Perfect. We need community ambassadors, content moderators, translators, writers, artists, voice actors, teachers, mentors, and legal advocates. Some of the most valuable roles are non-technical. You can earn XP and rank up just by being an active, positive community member.</div>
    </details>

    <details>
      <summary>What is post-quantum encryption?</summary>
      <div class="answer">Current encryption (like AES-256) is safe today but could be broken by future quantum computers. Our Veil messaging system uses Kyber-1024 — a crystal-lattice-based algorithm that even quantum computers cannot crack. Your messages are safe against both today's AND tomorrow's threats.</div>
    </details>

    <details>
      <summary>How is this different from Big Tech?</summary>
      <div class="answer">Big Tech sells your data, tracks your searches, reads your messages, and controls your digital life. GoSiteMe does none of this. Zero tracking. Zero data selling. Post-quantum encryption. Sovereign hosting. Open development. Military-grade governance with transparency. You're a person here, not a product.</div>
    </details>

    <details>
      <summary>What are the 351 command systems?</summary>
      <div class="answer">The War Room is our operational command center containing 351 live systems that monitor, manage, and coordinate everything from AI agent deployment to security scanning, from fleet logistics to community health, from code quality to emergency response. Each system has its own database, dashboard, and real-time metrics.</div>
    </details>

    <details>
      <summary>How does the mesh network work?</summary>
      <div class="answer">We run Headscale (WireGuard-based VPN) that creates encrypted peer-to-peer connections between members. If the internet goes down — natural disaster, censorship, outage — the mesh keeps local nodes connected. It's the T-1000 architecture: it heals itself.</div>
    </details>

    <details>
      <summary>Who founded this?</summary>
      <div class="answer">Commander Danny William Perez — a legal advocate from Quebec who won a major class action against the prison system. He built this ecosystem to give the same power of technology sovereignty to every person on Earth. His daughter is the designated heir. This is a family legacy, not a corporate venture.</div>
    </details>

  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- FINAL CTA -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="gi-final-cta">
  <h2>The World Needs You</h2>
  <p>351 systems protecting privacy. 9 pillars of sovereignty. 50 million AI agents. And room for you. Start your journey today.</p>
  <div class="gi-cta-row">
    <a href="/docs/field-manual" class="gi-btn gi-btn-gold"><i class="fas fa-book"></i> Read the Field Manual</a>
    <a href="/login.php" class="gi-btn gi-btn-outline"><i class="fas fa-user-plus"></i> Enlist Today</a>
    <a href="/alfred-browser.php" class="gi-btn gi-btn-outline"><i class="fas fa-globe"></i> Download Browser</a>
    <a href="/search.php" class="gi-btn gi-btn-outline"><i class="fas fa-search"></i> Try Private Search</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
