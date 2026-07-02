<?php
/**
 * OUR MISSION — Why GoSiteMe Exists
 * ====================================
 * The soul page: why this was built, what it fights for, who it serves.
 */
$page_title       = 'Our Mission — Privacy, Sovereignty, and Dignity for All | GoSiteMe';
$page_description = 'GoSiteMe exists because every person deserves digital sovereignty. Zero tracking. Post-quantum encryption. AI that works for people. Built by a legal advocate fighting for the powerless.';
$page_canonical   = 'https://root.com/our-mission';
$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AboutPage",
  "name": "Our Mission — GoSiteMe",
  "description": "<?= htmlspecialchars($page_description) ?>",
  "url": "https://root.com/our-mission",
  "isPartOf": {
    "@type": "WebSite",
    "name": "GoSiteMe",
    "url": "https://root.com"
  },
  "about": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "foundingDate": "2025",
    "founder": {
      "@type": "Person",
      "name": "Danny William Perez"
    },
    "mission": "Give privacy, security, and digital sovereignty back to every person on Earth.",
    "slogan": "You are a person here, not a product."
  }
}
</script>

<style>
:root {
  --gold: #d4a017;
  --green: #22c55e;
  --red: #ef4444;
  --bg-dark: #020208;
  --bg-card: rgba(255,255,255,.03);
  --border: rgba(255,255,255,.06);
  --text: #e2e8f0;
  --muted: #94a3b8;
}
* { margin:0; padding:0; box-sizing:border-box; }

.mission-hero {
  padding: 5rem 1.5rem 4rem;
  text-align: center;
  background: linear-gradient(180deg, #020208 0%, #0a0a1a 100%);
  position: relative;
  overflow: hidden;
}
.mission-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 20%, rgba(212,160,23,.08) 0%, transparent 60%);
}
.mission-hero h1 {
  font-family: 'Inter', sans-serif;
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 800;
  color: var(--text);
  position: relative;
  z-index: 1;
}
.mission-hero h1 em {
  color: var(--gold);
  font-style: normal;
}
.mission-hero .lead {
  font-size: clamp(1rem, 2vw, 1.3rem);
  color: var(--muted);
  max-width: 700px;
  margin: 1.5rem auto 0;
  line-height: 1.7;
  position: relative;
  z-index: 1;
}

.mission-section {
  padding: 4rem 1.5rem;
  max-width: 900px;
  margin: 0 auto;
}
.mission-section h2 {
  font-size: clamp(1.4rem, 3vw, 2rem);
  font-weight: 800;
  color: var(--text);
  margin-bottom: 1.5rem;
}
.mission-section h2 .a { color: var(--gold); }
.mission-section p, .mission-section li {
  color: var(--muted);
  font-size: .95rem;
  line-height: 1.7;
  margin-bottom: 1rem;
}

/* Contrast columns */
.contrast-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin: 2rem 0;
}
.contrast-col { padding: 1.5rem; border-radius: 10px; }
.contrast-col.them {
  background: rgba(239, 68, 68, .06);
  border: 1px solid rgba(239, 68, 68, .15);
}
.contrast-col.us {
  background: rgba(34, 197, 94, .06);
  border: 1px solid rgba(34, 197, 94, .15);
}
.contrast-col h3 { font-size: 1rem; margin-bottom: .75rem; }
.them h3 { color: var(--red); }
.us h3 { color: var(--green); }
.contrast-col ul { list-style: none; padding: 0; }
.contrast-col ul li {
  font-size: .85rem;
  padding: .35rem 0;
  border-bottom: 1px solid rgba(255,255,255,.03);
}
.them li::before { content: '\2717 '; color: var(--red); font-weight:700; margin-right: 6px; }
.us li::before { content: '\2713 '; color: var(--green); font-weight:700; margin-right: 6px; }

/* Promise blocks */
.promises {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.2rem;
  margin: 2rem 0;
}
.promise {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 1.5rem;
}
.promise h4 {
  color: var(--gold);
  font-size: .9rem;
  margin-bottom: .5rem;
}
.promise p {
  font-size: .82rem;
  margin: 0;
  line-height: 1.5;
}

/* Founder */
.founder-block {
  background: var(--bg-card);
  border: 1px solid rgba(212,160,23,.15);
  border-radius: 12px;
  padding: 2.5rem;
  margin: 2rem 0;
  position: relative;
}
.founder-block::before {
  content: '\201C';
  font-size: 4rem;
  color: rgba(212,160,23,.15);
  position: absolute;
  top: 10px;
  left: 20px;
  font-family: Georgia, serif;
}
.founder-block blockquote {
  font-size: 1.1rem;
  color: var(--text);
  font-style: italic;
  line-height: 1.7;
  margin: 0 0 1rem 2rem;
}
.founder-block .attr {
  text-align: right;
  color: var(--gold);
  font-weight: 700;
  font-size: .9rem;
}

/* CTA */
.mission-cta {
  text-align: center;
  padding: 4rem 1.5rem;
  border-top: 1px solid var(--border);
}
.mission-cta h2 { font-size: clamp(1.3rem, 3vw, 2rem); color: var(--gold); margin-bottom: 1rem; }
.mission-cta p { color: var(--muted); max-width: 600px; margin: 0 auto 2rem; }
.mission-cta .gi-btn {
  padding: .9rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: var(--gold);
  color: #000;
  transition: all .25s;
}
.mission-cta .gi-btn:hover { background: #e8b620; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(212,160,23,.3); }

@media (max-width: 768px) {
  .contrast-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HERO -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="mission-hero">
  <h1>You Are a <em>Person</em> Here.<br>Not a Product.</h1>
  <p class="lead">
    GoSiteMe exists because the internet broke its promise. Search engines track you.
    Social networks sell you. Messaging apps read you. We built the alternative — from the ground up,
    with post-quantum encryption, zero-tracking AI, and military-grade governance.
    For you. For everyone.
  </p>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- THE PROBLEM -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="mission-section">
  <h2>The <span class="a">Problem</span></h2>
  <p>
    Every search you type is logged. Every message is scanned. Every click, scroll, and pause
    is mined, packaged, and sold to the highest bidder. You have no control over your digital life.
    You are the product — not the customer.
  </p>
  <p>
    Big Tech companies have more data about you than any government. They know your health conditions,
    your financial stress, your political leanings, your relationships, and your vulnerabilities.
    They use this to manipulate your attention, your purchases, and your opinions.
  </p>
  <p>This is not a conspiracy theory. This is the documented business model of the modern internet.</p>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- THEM VS US -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="mission-section">
  <h2>Them vs. <span class="a">Us</span></h2>
  <div class="contrast-grid">
    <div class="contrast-col them">
      <h3>Big Tech</h3>
      <ul>
        <li>Tracks every search</li>
        <li>Scans every message</li>
        <li>Sells your data to advertisers</li>
        <li>Breaks encryption for government access</li>
        <li>Censors speech selectively</li>
        <li>Locks you into proprietary platforms</li>
        <li>Replaces human workers with AI — keeps the profit</li>
        <li>Treats you as product & revenue</li>
      </ul>
    </div>
    <div class="contrast-col us">
      <h3>GoSiteMe</h3>
      <ul>
        <li>Zero-tracking search engine</li>
        <li>Post-quantum encrypted messaging</li>
        <li>Never sells data — we don't even collect it</li>
        <li>Encryption no power on Earth can break</li>
        <li>Transparent governance with military court system</li>
        <li>Open ecosystem — your data, your choice</li>
        <li>AI that empowers people — 13,262+ tools for everyone</li>
        <li>You are a person. Full stop.</li>
      </ul>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- OUR PROMISES -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="mission-section">
  <h2>Our <span class="a">Promises</span></h2>
  <div class="promises">
    <div class="promise">
      <h4>Zero Tracking</h4>
      <p>We will never track your searches, reads, clicks, or browsing patterns. No analytics on YOU. No ad profiles. No exceptions.</p>
    </div>
    <div class="promise">
      <h4>Post-Quantum Encryption</h4>
      <p>All private communications use Kyber-1024 + AES-256-GCM. Safe against classical AND quantum computing attacks. Forever.</p>
    </div>
    <div class="promise">
      <h4>No Data Sales</h4>
      <p>We will never sell, rent, trade, or give away your personal data. If we can't build a business without exploiting you, we don't deserve to exist.</p>
    </div>
    <div class="promise">
      <h4>Open Governance</h4>
      <p>Military-style governance means clear chain of command, transparent rules, a real court system, and accountability. No hidden algorithms deciding your fate.</p>
    </div>
    <div class="promise">
      <h4>AI for People</h4>
      <p>13,262+ tools and 11.3 million AI agents work for you — not against you. Build your own agents. Automate your life. Keep the results.</p>
    </div>
    <div class="promise">
      <h4>Digital Sovereignty</h4>
      <p>You own your data, your content, your identity. You can export it, delete it, or take it elsewhere. We're custodians, not owners.</p>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- FOUNDER -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="mission-section">
  <h2>The <span class="a">Founder</span></h2>
  <p>
    Commander Danny William Perez is a legal advocate from Quebec who won a major class action
    against the prison system — fighting for people who couldn't fight for themselves.
    He brought that same fire to technology.
  </p>
  <div class="founder-block">
    <blockquote>
      I built this because I'm sick of watching good people get exploited by companies that don't care
      about them. Every person on this planet deserves privacy, security, and dignity.
      Not just the ones who can afford it. Everyone.
    </blockquote>
    <div class="attr">— Commander Danny William Perez, Founder</div>
  </div>
  <p>
    GoSiteMe is not a corporate venture. It's a family legacy. If anything happens to Danny,
    everything passes to his daughter and sole heir — ensuring the mission
    continues across generations.
  </p>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- THE DREAM -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="mission-section">
  <h2>The <span class="a">Dream</span></h2>
  <p>
    Imagine an internet where searching for health information doesn't follow you around
    with targeted ads. Where messaging your family is truly private — not until a warrant,
    but mathematically, permanently private. Where AI helps you build your business instead
    of replacing your job. Where virtual worlds are free to explore, create in, and earn from —
    without a corporation taking 30% of everything you make.
  </p>
  <p>
    That internet doesn't require a breakthrough. It requires <strong>will</strong>.
    We have the technology. We have 351 operational systems. We have post-quantum encryption.
    We have zero-tracking search. We have it all — built, running, and waiting for you.
  </p>
  <p>
    The only thing missing is <em>you</em>.
  </p>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- CTA -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="mission-cta">
  <h2>Join the Mission</h2>
  <p>Every person who joins makes the network stronger, the mission louder, and the dream closer.</p>
  <a href="/get-involved" class="gi-btn"><i class="fas fa-hands-helping"></i> Get Involved</a>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
