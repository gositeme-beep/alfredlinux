<?php
/**
 * Veil — GoSiteMe's E2E Encrypted Communications Platform
 * This page now redirects to /veil/ (the live Veil Messenger app).
 * Kept for SEO and backward compatibility with old links.
 */
header('HTTP/1.1 301 Moved Permanently');
header('Location: /veil/');
exit;

?>

<style>
/* ── Veil page variables ────────────────────────────────────── */
:root {
    --v-accent:        #8b5cf6;
    --v-accent-light:  #a78bfa;
    --v-green:         #34d399;
    --v-blue:          #60a5fa;
    --v-orange:        #fbbf24;
    --v-red:           #f87171;
    --v-cyan:          #22d3ee;
    --v-pink:          #f472b6;
    --v-bg:            #0a0a14;
    --v-card:          rgba(255,255,255,0.04);
    --v-border:        rgba(255,255,255,0.08);
    --v-text:          rgba(255,255,255,0.85);
    --v-muted:         rgba(255,255,255,0.55);
    --v-radius:        16px;
}

/* ── Hero ─────────────────────────────────────────────────────── */
.v-hero {
    text-align: center;
    padding: 140px 2rem 5rem;
    position: relative;
    overflow: hidden;
}
.v-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(139,92,246,.2) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(34,211,238,.06) 0%, transparent 35%),
                radial-gradient(ellipse at 20% 70%, rgba(244,114,182,.06) 0%, transparent 35%);
    pointer-events: none;
}
.v-hero-brand {
    display: inline-flex;
    align-items: center;
    gap: .75rem;
    font-size: .85rem;
    font-weight: 600;
    color: var(--v-accent-light);
    text-transform: uppercase;
    letter-spacing: 3px;
    margin-bottom: 1.5rem;
}
.v-hero-brand img { height: 28px; width: 28px; border-radius: 8px; }
.v-hero h1 {
    font-size: clamp(2.5rem, 6vw, 4.5rem);
    font-weight: 900;
    margin-bottom: 1.25rem;
    line-height: 1.05;
    letter-spacing: -1px;
}
.v-hero h1 .v-name {
    background: linear-gradient(135deg, var(--v-accent), var(--v-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.v-hero .v-tagline {
    font-size: 1.35rem;
    color: var(--v-muted);
    max-width: 700px;
    margin: 0 auto 2.5rem;
    line-height: 1.6;
    font-weight: 400;
}
.v-hero-pills {
    display: flex;
    gap: .75rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2.5rem;
}
.v-pill {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem 1.1rem;
    border-radius: 999px;
    background: var(--v-card);
    border: 1px solid var(--v-border);
    font-size: .82rem;
    color: var(--v-text);
    font-weight: 600;
}
.v-pill i { font-size: .75rem; }
.v-pill.pq i  { color: var(--v-accent); }
.v-pill.e2e i { color: var(--v-green); }
.v-pill.ai i  { color: var(--v-cyan); }
.v-pill.pay i { color: var(--v-orange); }
.v-pill.voice i { color: var(--v-pink); }

.v-hero-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 3rem;
}
.v-btn {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .85rem 2.25rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    text-decoration: none;
    transition: transform .2s, box-shadow .2s;
    cursor: pointer;
    border: none;
}
.v-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(139,92,246,.25); }
.v-btn-primary {
    background: linear-gradient(135deg, var(--v-accent), #6d28d9);
    color: #fff;
}
.v-btn-secondary {
    background: var(--v-card);
    border: 1px solid var(--v-border);
    color: var(--v-text);
}
.v-btn-green {
    background: linear-gradient(135deg, #34d399, #059669);
    color: #fff;
}
.v-hero-img {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
}
.v-hero-img img {
    width: 100%;
    border-radius: 20px;
    border: 1px solid var(--v-border);
}
.v-hero-img .v-hero-placeholder {
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 20px;
    border: 1px solid var(--v-border);
    background: linear-gradient(135deg, rgba(139,92,246,.08), rgba(34,211,238,.05));
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--v-muted);
    font-size: 1rem;
}

/* ── Sections ────────────────────────────────────────────────── */
.v-section {
    max-width: 1140px;
    margin: 0 auto;
    padding: 5rem 2rem;
}
.v-section h2 {
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: .5rem;
    text-align: center;
    letter-spacing: -.5px;
}
.v-section h2 span { color: var(--v-accent); }
.v-section .v-sub {
    color: var(--v-muted);
    font-size: 1.05rem;
    text-align: center;
    max-width: 700px;
    margin: 0 auto 3rem;
    line-height: 1.6;
}
.v-divider { border-top: 1px solid var(--v-border); }

/* ── Feature Grid ────────────────────────────────────────────── */
.v-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}
.v-feat {
    background: var(--v-card);
    border: 1px solid var(--v-border);
    border-radius: var(--v-radius);
    padding: 2rem 1.75rem;
    transition: border-color .25s, transform .25s;
}
.v-feat:hover {
    border-color: var(--v-accent);
    transform: translateY(-4px);
}
.v-feat-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 1.25rem;
}
.v-feat h3 { font-size: 1.1rem; margin-bottom: .5rem; font-weight: 700; }
.v-feat p { color: var(--v-muted); font-size: .92rem; line-height: 1.6; }

/* ── Big Feature Rows (alternating image + text) ─────────────── */
.v-showcase {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
    margin-bottom: 5rem;
}
.v-showcase.reverse { direction: rtl; }
.v-showcase.reverse > * { direction: ltr; }
.v-showcase-visual {
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--v-border);
    aspect-ratio: 4/3;
    background: linear-gradient(135deg, rgba(139,92,246,.06), rgba(34,211,238,.04));
    display: flex;
    align-items: center;
    justify-content: center;
}
.v-showcase-visual img { width: 100%; height: 100%; object-fit: cover; }
.v-showcase-visual .v-placeholder {
    color: var(--v-muted);
    font-size: .9rem;
    text-align: center;
    padding: 2rem;
}
.v-showcase-text h3 {
    font-size: 1.65rem;
    font-weight: 800;
    margin-bottom: .75rem;
    line-height: 1.2;
}
.v-showcase-text h3 span { color: var(--v-accent); }
.v-showcase-text p {
    color: var(--v-muted);
    font-size: 1rem;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}
.v-showcase-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.v-showcase-list li {
    padding: .4rem 0;
    font-size: .95rem;
    color: var(--v-text);
    display: flex;
    align-items: center;
    gap: .65rem;
}
.v-showcase-list li i { color: var(--v-green); font-size: .8rem; width: 16px; text-align: center; }

/* ── Stats Bar ───────────────────────────────────────────────── */
.v-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1.5rem;
    padding: 4rem 2rem;
    max-width: 1000px;
    margin: 0 auto;
}
.v-stat {
    text-align: center;
}
.v-stat-val {
    font-size: 2.5rem;
    font-weight: 900;
    background: linear-gradient(135deg, var(--v-accent), var(--v-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.1;
    margin-bottom: .25rem;
}
.v-stat-label {
    font-size: .82rem;
    color: var(--v-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
}

/* ── Comparison Table ────────────────────────────────────────── */
.v-compare-wrap {
    overflow-x: auto;
    margin: 0 auto;
    max-width: 1000px;
}
.v-compare {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: var(--v-radius);
    overflow: hidden;
    border: 1px solid var(--v-border);
    font-size: .88rem;
}
.v-compare th, .v-compare td {
    padding: .9rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--v-border);
}
.v-compare thead th {
    background: rgba(139,92,246,.08);
    font-weight: 700;
    font-size: .9rem;
    color: var(--v-text);
}
.v-compare tbody tr { background: var(--v-card); }
.v-compare tbody tr:hover { background: rgba(255,255,255,0.06); }
.v-compare tbody td:first-child { font-weight: 600; color: var(--v-text); }
.v-compare .v-yes { color: var(--v-green); font-weight: 600; }
.v-compare .v-no  { color: var(--v-muted); opacity: 0.6; }
.v-compare .v-hl  { background: rgba(139,92,246,.06); }

/* ── Download Cards ──────────────────────────────────────────── */
.v-dl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
    max-width: 900px;
    margin: 0 auto;
}
.v-dl-card {
    background: var(--v-card);
    border: 1px solid var(--v-border);
    border-radius: var(--v-radius);
    padding: 2.5rem 2rem;
    text-align: center;
    transition: border-color .25s, transform .25s;
}
.v-dl-card:hover { border-color: var(--v-accent); transform: translateY(-4px); }
.v-dl-icon { font-size: 3rem; margin-bottom: 1rem; }
.v-dl-card h3 { font-size: 1.15rem; margin-bottom: .5rem; }
.v-dl-card p { color: var(--v-muted); font-size: .88rem; margin-bottom: 1.5rem; line-height: 1.5; }
.v-dl-features { list-style: none; padding: 0; margin: 0 0 1.5rem; text-align: left; }
.v-dl-features li { padding: .35rem 0; font-size: .85rem; color: var(--v-muted); display: flex; align-items: center; gap: .5rem; }
.v-dl-features li i { color: var(--v-green); font-size: .75rem; }

/* ── FAQ Accordion ───────────────────────────────────────────── */
.v-accordion { max-width: 800px; margin: 0 auto; }
.v-acc-item {
    background: var(--v-card);
    border: 1px solid var(--v-border);
    border-radius: var(--v-radius);
    margin-bottom: 1rem;
    overflow: hidden;
}
.v-acc-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 1.5rem;
    cursor: pointer;
    font-weight: 600;
    font-size: 1.02rem;
    user-select: none;
}
.v-acc-header i.fa-chevron-down { transition: transform .3s; color: var(--v-muted); }
.v-acc-item.open .v-acc-header i.fa-chevron-down { transform: rotate(180deg); }
.v-acc-body { max-height: 0; overflow: hidden; transition: max-height .35s ease; }
.v-acc-body-inner { padding: 0 1.5rem 1.5rem; color: var(--v-muted); font-size: .95rem; line-height: 1.7; }

/* ── CTA ─────────────────────────────────────────────────────── */
.v-final-cta { text-align: center; padding: 5rem 2rem 6rem; position: relative; }
.v-final-cta::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 50% 100%, rgba(139,92,246,.1) 0%, transparent 50%);
    pointer-events: none;
}
.v-final-cta h2 { margin-bottom: .75rem; }
.v-final-cta p { color: var(--v-muted); font-size: 1.05rem; max-width: 600px; margin: 0 auto 2rem; }

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 768px) {
    .v-hero { padding: 5rem 1.25rem 3rem; }
    .v-section { padding: 3rem 1.25rem; }
    .v-showcase { grid-template-columns: 1fr; gap: 2rem; }
    .v-showcase.reverse { direction: ltr; }
    .v-stats { grid-template-columns: repeat(2, 1fr); }
    .v-dl-grid { grid-template-columns: 1fr; }
    .v-hero h1 { letter-spacing: 0; }
}
</style>

<main id="main">

<!-- ═══════════════════════════════════════════════════════════════
     HERO
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-hero">
    <div class="v-hero-brand">
        <!-- Replace with actual Veil logo once generated -->
        <i class="fas fa-shield-alt" style="font-size:1.4rem;color:var(--v-accent);"></i>
        GOSITEME
    </div>

    <h1><span class="v-name">Veil</span></h1>
    <p class="v-tagline">
        Private. Quantum-proof. Intelligent.<br>
        The messaging platform that protects your words, powers your work, and moves your money.
    </p>

    <div class="v-hero-pills">
        <span class="v-pill pq"><i class="fas fa-atom"></i> Post-Quantum Encrypted</span>
        <span class="v-pill e2e"><i class="fas fa-lock"></i> End-to-End E2E</span>
        <span class="v-pill ai"><i class="fas fa-robot"></i> 24 AI Agents</span>
        <span class="v-pill pay"><i class="fas fa-wallet"></i> P2P Payments</span>
        <span class="v-pill voice"><i class="fas fa-phone-alt"></i> Voice &amp; Video</span>
    </div>

    <div class="v-hero-cta">
        <a href="/veil/" class="v-btn v-btn-primary"><i class="fas fa-comments"></i> Open Veil</a>
        <a href="#download" class="v-btn v-btn-green"><i class="fab fa-android"></i> Get the App</a>
        <a href="#features" class="v-btn v-btn-secondary"><i class="fas fa-th"></i> See Features</a>
    </div>

    <!-- Hero image — replace src with generated image #1 -->
    <div class="v-hero-img">
        <div class="v-hero-placeholder">
            <span><i class="fas fa-image" style="font-size:2rem;display:block;margin-bottom:.75rem;"></i>Hero image — app mockup on devices</span>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     STATS BAR
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-divider">
    <div class="v-stats">
        <div class="v-stat">
            <div class="v-stat-val">256-bit</div>
            <div class="v-stat-label">AES-GCM Encryption</div>
        </div>
        <div class="v-stat">
            <div class="v-stat-val">Kyber-1024</div>
            <div class="v-stat-label">Post-Quantum KEM</div>
        </div>
        <div class="v-stat">
            <div class="v-stat-val">24</div>
            <div class="v-stat-label">AI Agents</div>
        </div>
        <div class="v-stat">
            <div class="v-stat-val">13,000+</div>
            <div class="v-stat-label">AI Tools</div>
        </div>
        <div class="v-stat">
            <div class="v-stat-val">0</div>
            <div class="v-stat-label">External Dependencies</div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     SHOWCASE 1 — POST-QUANTUM ENCRYPTION
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section v-divider" id="features">
    <div class="v-showcase">
        <div class="v-showcase-visual">
            <!-- Replace with generated image #3 (lattice crystal) -->
            <div class="v-placeholder"><i class="fas fa-atom" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--v-accent);"></i>Post-quantum lattice visual</div>
        </div>
        <div class="v-showcase-text">
            <h3><span>Quantum-Proof</span> Encryption</h3>
            <p>
                Veil is the first SMB communications platform with <strong>Kyber-1024 hybrid post-quantum encryption</strong>.
                Every message, call, and file uses a dual key-exchange — ECDH P-256 + Kyber-1024 — combined via HKDF.
                Even if quantum computers break one, the other still protects your data.
            </p>
            <ul class="v-showcase-list">
                <li><i class="fas fa-check"></i> NIST FIPS 203 standardized (ML-KEM)</li>
                <li><i class="fas fa-check"></i> 192-bit security level</li>
                <li><i class="fas fa-check"></i> &lt; 2 ms handshake — zero perceptible latency</li>
                <li><i class="fas fa-check"></i> 654 lines of pure JS — fully auditable</li>
                <li><i class="fas fa-check"></i> Zero npm dependencies, zero WASM</li>
                <li><i class="fas fa-check"></i> Automatic fallback for non-PQ contacts</li>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     SHOWCASE 2 — MESSAGING
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section">
    <div class="v-showcase reverse">
        <div class="v-showcase-visual">
            <!-- Replace with generated image #9 (self-destructing messages) -->
            <div class="v-placeholder"><i class="fas fa-comments" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--v-blue);"></i>Encrypted messaging visual</div>
        </div>
        <div class="v-showcase-text">
            <h3>Messaging That <span>Disappears</span></h3>
            <p>
                Text, voice messages, files, photos — all end-to-end encrypted by default.
                Set messages to self-destruct. React with emoji. Reply in threads. Edit after sending.
                Everything Signal does, Veil does — and more.
            </p>
            <ul class="v-showcase-list">
                <li><i class="fas fa-check"></i> E2E encrypted text, voice messages &amp; files</li>
                <li><i class="fas fa-check"></i> Self-destructing messages (custom timers)</li>
                <li><i class="fas fa-check"></i> Reactions, threads &amp; replies</li>
                <li><i class="fas fa-check"></i> Message editing &amp; deletion</li>
                <li><i class="fas fa-check"></i> Typing indicators &amp; read receipts</li>
                <li><i class="fas fa-check"></i> Offline message queue (syncs when back online)</li>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     SHOWCASE 3 — VOICE & VIDEO
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section v-divider">
    <div class="v-showcase">
        <div class="v-showcase-visual">
            <!-- Replace with generated image #4 (voice/video) -->
            <div class="v-placeholder"><i class="fas fa-video" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--v-pink);"></i>Voice &amp; video calling visual</div>
        </div>
        <div class="v-showcase-text">
            <h3><span>Crystal-Clear</span> Calls</h3>
            <p>
                Voice and video calls powered by WebRTC with DTLS-SRTP encryption.
                Screen sharing. Group calls. PQ-ready signaling. No third-party servers touching your media stream.
            </p>
            <ul class="v-showcase-list">
                <li><i class="fas fa-check"></i> 1:1 and group voice/video calls</li>
                <li><i class="fas fa-check"></i> Screen sharing</li>
                <li><i class="fas fa-check"></i> DTLS-SRTP encrypted media</li>
                <li><i class="fas fa-check"></i> PQ-ready signaling layer</li>
                <li><i class="fas fa-check"></i> Voice messages with waveform preview</li>
                <li><i class="fas fa-check"></i> In-call reactions</li>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     SHOWCASE 4 — AI AGENTS
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section">
    <div class="v-showcase reverse">
        <div class="v-showcase-visual">
            <!-- Replace with generated image #5 (AI agents) -->
            <div class="v-placeholder"><i class="fas fa-robot" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--v-cyan);"></i>AI agents hub visual</div>
        </div>
        <div class="v-showcase-text">
            <h3><span>24 AI Agents.</span> 13,000+ Tools.</h3>
            <p>
                Veil isn't just a chat app — it's an AI-native operations platform.
                Summon agents for legal, medical, finance, DevOps, marketing, customer support, and more.
                Each agent has specialized tools, knowledge, and workflows.
            </p>
            <ul class="v-showcase-list">
                <li><i class="fas fa-check"></i> Alfred — general AI assistant built in</li>
                <li><i class="fas fa-check"></i> Industry-specific agents (legal, medical, finance…)</li>
                <li><i class="fas fa-check"></i> Fleet orchestration — agents collaborate</li>
                <li><i class="fas fa-check"></i> Voice AI command center</li>
                <li><i class="fas fa-check"></i> Agent marketplace (build &amp; monetize)</li>
                <li><i class="fas fa-check"></i> 17+ intelligence engines</li>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     SHOWCASE 5 — P2P PAYMENTS
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section v-divider">
    <div class="v-showcase">
        <div class="v-showcase-visual">
            <!-- Replace with generated image #6 (P2P payments) -->
            <div class="v-placeholder"><i class="fas fa-wallet" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--v-orange);"></i>P2P payments visual</div>
        </div>
        <div class="v-showcase-text">
            <h3>The <span>Banking App</span> of the Future</h3>
            <p>
                Send money as easily as sending a message. Veil brings peer-to-peer payments into
                your encrypted conversations — no separate app, no third-party exposure, no friction.
                Split bills, pay invoices, tip creators, send allowances — all quantum-encrypted.
            </p>
            <ul class="v-showcase-list">
                <li><i class="fas fa-check"></i> Instant P2P money transfers</li>
                <li><i class="fas fa-check"></i> Send payments inside any conversation</li>
                <li><i class="fas fa-check"></i> Encrypted transaction history</li>
                <li><i class="fas fa-check"></i> Split bills with group members</li>
                <li><i class="fas fa-check"></i> Business invoicing built in</li>
                <li><i class="fas fa-check"></i> No financial data leaves the encrypted tunnel</li>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     SHOWCASE 6 — GROUPS
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section">
    <div class="v-showcase reverse">
        <div class="v-showcase-visual">
            <!-- Replace with generated image #7 (group mesh) -->
            <div class="v-placeholder"><i class="fas fa-users" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--v-green);"></i>Group encrypted chat visual</div>
        </div>
        <div class="v-showcase-text">
            <h3><span>Group Rooms</span> — Encrypted for Teams</h3>
            <p>
                Sender Key protocol gives every group its own encryption layer.
                Create rooms for teams, projects, clients, or communities.
                Admin controls, member management, and encrypted file sharing — all built in.
            </p>
            <ul class="v-showcase-list">
                <li><i class="fas fa-check"></i> Sender Key group encryption</li>
                <li><i class="fas fa-check"></i> Admin roles &amp; member management</li>
                <li><i class="fas fa-check"></i> Encrypted file sharing in groups</li>
                <li><i class="fas fa-check"></i> Group voice &amp; video calls</li>
                <li><i class="fas fa-check"></i> Invite links</li>
                <li><i class="fas fa-check"></i> Alfred AI available in every room</li>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     FULL FEATURE GRID
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section v-divider">
    <h2>Everything You Need. <span>Nothing You Don't.</span></h2>
    <p class="v-sub">80+ features across messaging, security, voice, AI, and payments — in one app.</p>

    <div class="v-features">
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(139,92,246,.15);color:var(--v-accent);"><i class="fas fa-atom"></i></div>
            <h3>Post-Quantum Encryption</h3>
            <p>Kyber-1024 + ECDH hybrid. NIST FIPS 203. Every message, call, and file. Zero external dependencies.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(52,211,153,.15);color:var(--v-green);"><i class="fas fa-lock"></i></div>
            <h3>E2E by Default</h3>
            <p>X3DH session establishment, AES-256-GCM. The server never sees plaintext. Period.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(96,165,250,.15);color:var(--v-blue);"><i class="fas fa-key"></i></div>
            <h3>Key Backup &amp; Recovery</h3>
            <p>PBKDF2-encrypted key backup (600K iterations). Recover your identity on a new device without losing contacts.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(244,114,182,.15);color:var(--v-pink);"><i class="fas fa-phone-alt"></i></div>
            <h3>Voice &amp; Video Calls</h3>
            <p>WebRTC with DTLS-SRTP. 1:1 and group calls. Screen sharing. Crystal-clear, encrypted.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(251,191,36,.15);color:var(--v-orange);"><i class="fas fa-wallet"></i></div>
            <h3>P2P Payments</h3>
            <p>Send money inside conversations. Split bills. Business invoicing. All quantum-encrypted.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(34,211,238,.15);color:var(--v-cyan);"><i class="fas fa-robot"></i></div>
            <h3>AI Agents</h3>
            <p>24 specialized agents with 13,000+ tools. Legal, medical, finance, DevOps — summon in any chat.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(248,113,113,.15);color:var(--v-red);"><i class="fas fa-fire-alt"></i></div>
            <h3>Self-Destructing Messages</h3>
            <p>Set messages to auto-delete after a custom timer. When they're gone, they're gone — from both devices.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(139,92,246,.15);color:var(--v-accent);"><i class="fas fa-smile"></i></div>
            <h3>Reactions &amp; Threads</h3>
            <p>React with emoji. Reply in threads. Keep conversations organized without clutter.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(52,211,153,.15);color:var(--v-green);"><i class="fas fa-users"></i></div>
            <h3>Group Rooms</h3>
            <p>Sender Key encryption for groups. Admin controls, file sharing, voice calls, and AI — all in one room.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(96,165,250,.15);color:var(--v-blue);"><i class="fas fa-microphone"></i></div>
            <h3>Voice Messages</h3>
            <p>Record, preview with waveform, send. Encrypted end-to-end like everything else.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(244,114,182,.15);color:var(--v-pink);"><i class="fas fa-desktop"></i></div>
            <h3>Multi-Device</h3>
            <p>Phone, tablet, laptop, desktop — your Veil identity lives everywhere. Sync encrypted keys across devices.</p>
        </div>
        <div class="v-feat">
            <div class="v-feat-icon" style="background:rgba(251,191,36,.15);color:var(--v-orange);"><i class="fas fa-wifi-slash"></i></div>
            <h3>Offline Ready (PWA)</h3>
            <p>Messages queue offline and send when you reconnect. Installed as a native app from your browser.</p>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     COMPETITOR COMPARISON
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section v-divider">
    <h2>Veil vs. <span>Everyone Else</span></h2>
    <p class="v-sub">We don't just match the competition — we leapfrog them.</p>

    <div class="v-compare-wrap">
        <table class="v-compare">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th style="color:var(--v-accent);"><i class="fas fa-shield-alt" style="margin-right:4px;"></i> Veil</th>
                    <th>Signal</th>
                    <th>WhatsApp</th>
                    <th>Telegram</th>
                    <th>Discord</th>
                    <th>Slack</th>
                </tr>
            </thead>
            <tbody>
                <tr class="v-hl">
                    <td><i class="fas fa-atom" style="color:var(--v-accent);margin-right:6px;"></i> Post-Quantum KEM</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i> Kyber-1024</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i> PQXDH</td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                </tr>
                <tr>
                    <td>E2E Encrypted by Default</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-no">Opt-in</td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                </tr>
                <tr>
                    <td><i class="fas fa-robot" style="color:var(--v-cyan);margin-right:6px;"></i> AI Agents</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i> 50M+ agents</td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no">Bots (ext)</td>
                    <td class="v-no">Bots (ext)</td>
                </tr>
                <tr class="v-hl">
                    <td><i class="fas fa-wallet" style="color:var(--v-orange);margin-right:6px;"></i> P2P Payments</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i> Built-in</td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no">Meta Pay</td>
                    <td class="v-no">TON (separate)</td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                </tr>
                <tr>
                    <td>Voice &amp; Video Calls</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                </tr>
                <tr>
                    <td>Self-Destruct Messages</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                </tr>
                <tr>
                    <td>Offline / PWA</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-no">Native only</td>
                    <td class="v-no">Native only</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i></td>
                </tr>
                <tr>
                    <td>Zero Dependencies Crypto</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i> Pure JS</td>
                    <td class="v-no">libsignal</td>
                    <td class="v-no">libsignal</td>
                    <td class="v-no">MTProto</td>
                    <td class="v-no">Proprietary</td>
                    <td class="v-no">Proprietary</td>
                </tr>
                <tr>
                    <td>SMB Platform Integration</td>
                    <td class="v-yes"><i class="fas fa-check-circle"></i> Full</td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no"><i class="fas fa-times-circle"></i></td>
                    <td class="v-no">Partial</td>
                </tr>
                <tr>
                    <td>Price</td>
                    <td class="v-yes"><strong>$3.99/mo</strong></td>
                    <td>Free</td>
                    <td>Free</td>
                    <td>Free / $4.99</td>
                    <td>Free / $9.99</td>
                    <td>$8.75/mo+</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     MULTI-DEVICE & SECURITY SHOWCASE
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section">
    <div class="v-showcase">
        <div class="v-showcase-visual">
            <!-- Replace with generated image #8 (multi-device) -->
            <div class="v-placeholder"><i class="fas fa-laptop" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--v-accent);"></i>Multi-device sync visual</div>
        </div>
        <div class="v-showcase-text">
            <h3>One Identity. <span>Every Device.</span></h3>
            <p>
                Your Veil identity syncs across all your devices. Encrypted key backup means you
                never lose access — and no one else can gain it. PBKDF2 with 600,000 iterations protects your keys at rest.
            </p>
            <ul class="v-showcase-list">
                <li><i class="fas fa-check"></i> Phone, tablet, laptop, desktop</li>
                <li><i class="fas fa-check"></i> Encrypted key backup &amp; restore</li>
                <li><i class="fas fa-check"></i> Device management dashboard</li>
                <li><i class="fas fa-check"></i> Remote device revocation</li>
                <li><i class="fas fa-check"></i> PWA installs like a native app</li>
                <li><i class="fas fa-check"></i> Push notifications on all platforms</li>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     DOWNLOAD
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section v-divider" id="download" style="text-align:center;">
    <h2 style="font-size:2.2rem;font-weight:800;">Get <span style="color:var(--v-accent);">Veil</span></h2>
    <p class="v-sub">Quantum-proof messaging on every device. Free for 14 days.</p>

    <div class="v-dl-grid">
        <div class="v-dl-card">
            <div class="v-dl-icon" style="color:var(--v-green);"><i class="fab fa-android"></i></div>
            <h3>Android</h3>
            <p>Native Android app (TWA). Full-screen, no browser chrome.</p>
            <ul class="v-dl-features">
                <li><i class="fas fa-check"></i> Post-quantum encrypted</li>
                <li><i class="fas fa-check"></i> Push notifications</li>
                <li><i class="fas fa-check"></i> Offline queue</li>
                <li><i class="fas fa-check"></i> Auto-updates via web</li>
            </ul>
            <a href="/downloads/Alfred-Browser.apk.torrent" class="v-btn v-btn-green" id="android-dl-btn">
                <i class="fab fa-android"></i> Download APK
            </a>
            <p style="font-size:.75rem;color:var(--v-muted);margin-top:.75rem;">Android 8.0+ · 4 MB</p>
        </div>

        <div class="v-dl-card">
            <div class="v-dl-icon" style="color:var(--v-blue);"><i class="fas fa-globe"></i></div>
            <h3>Web App (PWA)</h3>
            <p>Install from any browser. Works everywhere — desktop, tablet, mobile.</p>
            <ul class="v-dl-features">
                <li><i class="fas fa-check"></i> Same encryption as native</li>
                <li><i class="fas fa-check"></i> Windows, Mac, Linux, iOS</li>
                <li><i class="fas fa-check"></i> Offline capable</li>
                <li><i class="fas fa-check"></i> No app store needed</li>
            </ul>
            <a href="/veil/" class="v-btn v-btn-primary">
                <i class="fas fa-external-link-alt"></i> Open Veil
            </a>
            <p style="font-size:.75rem;color:var(--v-muted);margin-top:.75rem;">Chrome, Firefox, Safari, Edge</p>
        </div>

        <div class="v-dl-card">
            <div class="v-dl-icon" style="color:var(--v-accent);"><i class="fas fa-desktop"></i></div>
            <h3>Desktop</h3>
            <p>Integrated with Alfred IDE. All platforms.</p>
            <ul class="v-dl-features">
                <li><i class="fas fa-check"></i> Windows, macOS, Linux</li>
                <li><i class="fas fa-check"></i> Alfred IDE integration</li>
                <li><i class="fas fa-check"></i> Alfred AI built-in</li>
                <li><i class="fas fa-check"></i> Same PQ encryption</li>
            </ul>
            <a href="/gocodeme.php" class="v-btn v-btn-secondary">
                <i class="fas fa-download"></i> Download Desktop
            </a>
            <p style="font-size:.75rem;color:var(--v-muted);margin-top:.75rem;">Win 10+, macOS 12+, Ubuntu 20.04+</p>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     FAQ
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-section v-divider">
    <h2>Questions &amp; <span>Answers</span></h2>
    <p class="v-sub">Everything you need to know about Veil.</p>

    <div class="v-accordion">
        <div class="v-acc-item">
            <div class="v-acc-header">
                <span>What is Veil?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="v-acc-body">
                <div class="v-acc-body-inner">
                    Veil is GoSiteMe's end-to-end encrypted communications platform. It combines messaging, voice/video calls,
                    AI agents, P2P payments, and group collaboration — all protected by Kyber-1024 hybrid post-quantum encryption.
                    Think of it as Signal + Discord + Venmo, wrapped in quantum-proof security.
                </div>
            </div>
        </div>

        <div class="v-acc-item">
            <div class="v-acc-header">
                <span>Why post-quantum encryption?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="v-acc-body">
                <div class="v-acc-body-inner">
                    Quantum computers will break RSA and elliptic curve cryptography. "Harvest Now, Decrypt Later" attacks
                    mean encrypted data captured today can be cracked once quantum computers mature. Veil uses Kyber-1024
                    (NIST FIPS 203) combined with classical ECDH — so even if one is broken, the other still protects you.
                    NIST, NSA, and CISA all recommend beginning PQ migration now.
                </div>
            </div>
        </div>

        <div class="v-acc-item">
            <div class="v-acc-header">
                <span>How is this different from Signal?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="v-acc-body">
                <div class="v-acc-body-inner">
                    Signal is excellent at private messaging. Veil does that <em>and</em> adds 24 AI agents, P2P payments,
                    fleet orchestration, voice AI command center, an agent marketplace, multi-device key sync, and full
                    SMB platform integration (hosting, domains, AI). Signal is a messaging app. Veil is an encrypted
                    operations platform.
                </div>
            </div>
        </div>

        <div class="v-acc-item">
            <div class="v-acc-header">
                <span>Does PQ encryption slow things down?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="v-acc-body">
                <div class="v-acc-body-inner">
                    No. Kyber operations complete in under 2 ms on modern hardware. The only overhead is ~2.3 KB per key
                    exchange. For comparison, a phone photo is 3–5 MB. The PQ handshake is invisible.
                </div>
            </div>
        </div>

        <div class="v-acc-item">
            <div class="v-acc-header">
                <span>Can I audit the encryption code?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="v-acc-body">
                <div class="v-acc-body-inner">
                    Yes. The entire Kyber-1024 implementation (<code>comms-pqc.js</code>) is 654 lines of readable, unminified
                    JavaScript with zero external dependencies. The hybrid combiner and AES-GCM encryption are in
                    <code>comms-crypto.js</code>. Both files are served unminified and inspectable in DevTools.
                </div>
            </div>
        </div>

        <div class="v-acc-item">
            <div class="v-acc-header">
                <span>How do P2P payments work?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="v-acc-body">
                <div class="v-acc-body-inner">
                    Veil payments let you send money directly inside any conversation. The payment request and confirmation
                    are encrypted end-to-end like any other message. No financial data is ever visible to the server.
                    You can split bills with groups, send instant payments to contacts, or create invoices for clients.
                </div>
            </div>
        </div>

        <div class="v-acc-item">
            <div class="v-acc-header">
                <span>What if I message someone without PQ keys?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="v-acc-body">
                <div class="v-acc-body-inner">
                    The system falls back to classical ECDH P-256 encryption. Your message is still E2E encrypted — just
                    without quantum resistance. When both sides have PQ keys, the banner shows "Post-Quantum" with a
                    shield icon. The upgrade is seamless and automatic.
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════
     FINAL CTA
     ═══════════════════════════════════════════════════════════════ -->
<section class="v-final-cta">
    <h2 style="font-size:2.2rem;font-weight:800;"><span style="color:var(--v-accent);">Veil</span> Your Conversations.</h2>
    <p>Private messaging. Quantum-proof security. AI that works for you. Money that moves instantly.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="/veil/" class="v-btn v-btn-primary"><i class="fas fa-comments"></i> Open Veil</a>
        <a href="/pulse.php" class="v-btn v-btn-secondary" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;"><i class="fas fa-bolt"></i> Explore Pulse</a>
        <a href="/security.php" class="v-btn v-btn-secondary"><i class="fas fa-shield-alt"></i> Security Deep Dive</a>
    </div>
    <p style="color:var(--v-muted);font-size:.88rem;margin-top:1.25rem;">
        Included in all GoSiteMe plans · Starting at $3.99/mo · 14-day free trial
    </p>
</section>

</main>

<script src="/assets/js/post-quantum-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
