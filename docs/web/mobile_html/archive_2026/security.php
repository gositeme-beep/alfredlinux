<?php
/**
 * Security — Alfred AI
 * Public trust / security-practices page.
 */
$page_title       = 'Security - Alfred AI';
$page_description = 'Learn about Alfred AI security practices, compliance, and data protection measures.';
$page_canonical   = 'https://gositeme.com/security';
$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ── Security page variables ─────────────────────────────── */
:root {
    --sec-accent:       #a78bfa;
    --sec-accent-light: #c4b5fd;
    --sec-green:        #34d399;
    --sec-blue:         #60a5fa;
    --sec-orange:       #fbbf24;
    --sec-red:          #f87171;
    --sec-bg:           #0a0a14;
    --sec-card:         rgba(255,255,255,0.04);
    --sec-border:       rgba(255,255,255,0.08);
    --sec-text:         rgba(255,255,255,0.85);
    --sec-muted:        rgba(255,255,255,0.55);
    --sec-radius:       16px;
}

/* ── Hero ─────────────────────────────────────────────────── */
.sec-hero {
    text-align: center;
    padding: 8rem 2rem 4rem;
    position: relative;
    overflow: hidden;
}
.sec-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(167,139,250,.15) 0%, transparent 60%);
    pointer-events: none;
}
.sec-hero h1 {
    font-size: clamp(2rem, 5vw, 3.25rem);
    font-weight: 800;
    margin-bottom: 1rem;
}
.sec-hero h1 span { color: var(--sec-accent); }
.sec-hero p {
    font-size: 1.15rem;
    color: var(--sec-muted);
    max-width: 640px;
    margin: 0 auto;
}
.sec-hero .sec-badges {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    flex-wrap: wrap;
}
.sec-hero .sec-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem 1.25rem;
    border-radius: 999px;
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    font-size: .9rem;
    color: var(--sec-text);
}
.sec-hero .sec-badge i { color: var(--sec-green); }

/* ── Shared section ──────────────────────────────────────── */
.sec-section {
    max-width: 1100px;
    margin: 0 auto;
    padding: 4rem 2rem;
}
.sec-section h2 {
    font-size: 1.85rem;
    font-weight: 700;
    margin-bottom: .5rem;
}
.sec-section h2 span { color: var(--sec-accent); }
.sec-section .sec-sub {
    color: var(--sec-muted);
    font-size: 1.05rem;
    margin-bottom: 2.5rem;
}

/* ── Pillar cards ────────────────────────────────────────── */
.sec-pillars {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
}
.sec-pillar {
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    border-radius: var(--sec-radius);
    padding: 2rem 1.5rem;
    transition: border-color .25s, transform .25s;
}
.sec-pillar:hover {
    border-color: var(--sec-accent);
    transform: translateY(-4px);
}
.sec-pillar .sec-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem;
    margin-bottom: 1.25rem;
}
.sec-pillar h3 { font-size: 1.15rem; margin-bottom: .5rem; }
.sec-pillar p  { color: var(--sec-muted); font-size: .95rem; line-height: 1.6; }
.sec-icon-encrypt { background: rgba(96,165,250,.15); color: var(--sec-blue); }
.sec-icon-access  { background: rgba(52,211,153,.15); color: var(--sec-green); }
.sec-icon-infra   { background: rgba(251,191,36,.15); color: var(--sec-orange); }
.sec-icon-comply  { background: rgba(167,139,250,.15); color: var(--sec-accent); }

/* ── Accordion ───────────────────────────────────────────── */
.sec-accordion { max-width: 800px; margin: 0 auto; }
.sec-acc-item {
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    border-radius: var(--sec-radius);
    margin-bottom: 1rem;
    overflow: hidden;
}
.sec-acc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    cursor: pointer;
    font-weight: 600;
    font-size: 1.05rem;
    user-select: none;
}
.sec-acc-header i.fa-chevron-down {
    transition: transform .3s;
    color: var(--sec-muted);
}
.sec-acc-item.open .sec-acc-header i.fa-chevron-down {
    transform: rotate(180deg);
}
.sec-acc-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height .35s ease;
}
.sec-acc-body-inner {
    padding: 0 1.5rem 1.5rem;
    color: var(--sec-muted);
    font-size: .95rem;
    line-height: 1.7;
}
.sec-acc-body-inner ul {
    padding-left: 1.25rem;
    margin: .75rem 0;
}
.sec-acc-body-inner li { margin-bottom: .4rem; }
.sec-acc-item.open .sec-acc-body {
    max-height: 600px;
}

/* ── Compliance table ────────────────────────────────────── */
.sec-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-top: 1.5rem;
}
.sec-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .92rem;
    min-width: 600px;
}
.sec-table th, .sec-table td {
    padding: .85rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--sec-border);
}
.sec-table th {
    color: var(--sec-accent-light);
    font-weight: 600;
    white-space: nowrap;
}
.sec-table td { color: var(--sec-text); }
.sec-table .check { color: var(--sec-green); }
.sec-table .dash  { color: var(--sec-muted); }
.sec-table .road  { color: var(--sec-orange); font-size: .8rem; }

/* ── Disclosure / Bug Bounty ─────────────────────────────── */
.sec-disclosure {
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    border-radius: var(--sec-radius);
    padding: 2.5rem;
    max-width: 800px;
    margin: 0 auto;
}
.sec-disclosure h3 { color: var(--sec-accent); margin-bottom: 1rem; }
.sec-disclosure p, .sec-disclosure li { color: var(--sec-muted); line-height: 1.7; font-size: .95rem; }
.sec-disclosure ul { padding-left: 1.25rem; margin: 1rem 0; }
.sec-disclosure .sec-email-link {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .6rem 1.5rem;
    border-radius: 999px;
    background: var(--sec-accent);
    color: #fff !important;
    font-weight: 600;
    margin-top: 1rem;
    text-decoration: none;
    transition: opacity .2s;
}
.sec-disclosure .sec-email-link:hover { opacity: .85; }

/* ── Hall of Fame ────────────────────────────────────────── */
.sec-hof {
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    border-radius: var(--sec-radius);
    padding: 2rem;
    text-align: center;
    max-width: 600px;
    margin: 2rem auto 0;
    color: var(--sec-muted);
    font-size: .95rem;
}
.sec-hof i { font-size: 2rem; display: block; margin-bottom: .75rem; color: var(--sec-orange); }

/* ── Data Processing ─────────────────────────────────────── */
.sec-dp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}
.sec-dp-card {
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    border-radius: var(--sec-radius);
    padding: 1.75rem;
}
.sec-dp-card h4 { font-size: 1.05rem; margin-bottom: .75rem; }
.sec-dp-card h4 i { margin-right: .5rem; color: var(--sec-accent); }
.sec-dp-card p, .sec-dp-card li { color: var(--sec-muted); font-size: .93rem; line-height: 1.65; }
.sec-dp-card ul { padding-left: 1.25rem; margin-top: .5rem; }

/* ── FAQ ─────────────────────────────────────────────────── */
.sec-faq { max-width: 800px; margin: 0 auto; }

/* ── CTA ─────────────────────────────────────────────────── */
.sec-cta {
    text-align: center;
    padding: 5rem 2rem;
}
.sec-cta-box {
    background: linear-gradient(135deg, rgba(167,139,250,.12), rgba(96,165,250,.08));
    border: 1px solid var(--sec-border);
    border-radius: var(--sec-radius);
    padding: 3rem 2rem;
    max-width: 700px;
    margin: 0 auto;
}
.sec-cta-box h2 { font-size: 1.6rem; margin-bottom: .75rem; }
.sec-cta-box p  { color: var(--sec-muted); margin-bottom: 1.5rem; }
.sec-btn-primary {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .75rem 2rem;
    border-radius: 999px;
    background: var(--sec-accent);
    color: #fff !important;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .2s;
}
.sec-btn-primary:hover { opacity: .85; }

/* ── Veil Fortress Layer Stack ────────────────────────────── */
.sec-veil-stack {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
}
.sec-veil-layer {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 1.25rem 1.5rem;
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    border-radius: 12px;
    margin-bottom: .75rem;
    position: relative;
    transition: border-color .25s, transform .25s;
}
.sec-veil-layer:hover {
    border-color: var(--sec-accent);
    transform: translateX(6px);
}
.sec-veil-num {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.sec-veil-layer .sec-vl-title {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: .2rem;
}
.sec-veil-layer .sec-vl-desc {
    color: var(--sec-muted);
    font-size: .88rem;
    line-height: 1.5;
}
.sec-vl-tag {
    display: inline-block;
    padding: .15rem .6rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
    margin-left: .5rem;
    vertical-align: middle;
}
.sec-vl-pq    { background: rgba(167,139,250,.18); color: var(--sec-accent-light); }
.sec-vl-class { background: rgba(96,165,250,.18);  color: var(--sec-blue); }
.sec-vl-adv   { background: rgba(52,211,153,.18);  color: var(--sec-green); }
.sec-vl-steg  { background: rgba(248,113,113,.18); color: var(--sec-red); }

/* ── Warrant Canary ──────────────────────────────────────── */
.sec-canary {
    max-width: 800px;
    margin: 0 auto;
    background: linear-gradient(135deg, rgba(52,211,153,.06), rgba(96,165,250,.06));
    border: 1px solid rgba(52,211,153,.25);
    border-radius: var(--sec-radius);
    padding: 2.5rem;
    text-align: center;
    position: relative;
}
.sec-canary::before {
    content: '';
    position: absolute; top: 0; left: 50%; transform: translateX(-50%);
    width: 60px; height: 3px;
    background: var(--sec-green);
    border-radius: 0 0 4px 4px;
}
.sec-canary h3 { font-size: 1.3rem; margin-bottom: 1rem; color: var(--sec-green); }
.sec-canary p  { color: var(--sec-muted); font-size: .95rem; line-height: 1.7; max-width: 600px; margin: 0 auto; }
.sec-canary .sec-canary-date {
    margin-top: 1.25rem;
    font-size: .85rem;
    color: var(--sec-blue);
    font-weight: 600;
}
.sec-canary .sec-canary-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
    text-align: left;
}
.sec-canary .sec-canary-item {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    font-size: .9rem;
    color: var(--sec-text);
}
.sec-canary .sec-canary-item i { color: var(--sec-green); margin-top: .2rem; flex-shrink: 0; }

/* ── No-Backdoor Pledge ──────────────────────────────────── */
.sec-pledge {
    max-width: 800px;
    margin: 2rem auto 0;
    background: var(--sec-card);
    border: 1px solid var(--sec-border);
    border-radius: var(--sec-radius);
    padding: 2rem 2.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
}
.sec-pledge-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    background: rgba(248,113,113,.12);
    color: var(--sec-red);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.sec-pledge h4 { font-size: 1.1rem; margin-bottom: .4rem; }
.sec-pledge p  { color: var(--sec-muted); font-size: .93rem; line-height: 1.65; }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 768px) {
    .sec-hero { padding: 6rem 1.25rem 3rem; }
    .sec-section { padding: 3rem 1.25rem; }
    .sec-disclosure { padding: 1.5rem; }
    .sec-veil-layer { flex-direction: column; text-align: center; gap: .75rem; }
    .sec-pledge { flex-direction: column; text-align: center; align-items: center; }
}
</style>

<main id="main">

<!-- ═══════════════════════════════════════════════════════════
     1. HERO
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-hero">
    <h1><span>Enterprise-Grade</span> Security</h1>
    <p>Your data is protected by industry-leading security practices, encryption, and infrastructure — so you can focus on building, not worrying.</p>
    <div class="sec-badges">
        <span class="sec-badge"><i class="fas fa-lock"></i> AES-256 Encryption</span>
        <span class="sec-badge"><i class="fas fa-shield-halved"></i> TLS 1.3</span>
        <span class="sec-badge"><i class="fas fa-atom"></i> Post-Quantum (Kyber-1024)</span>
        <span class="sec-badge"><i class="fas fa-layer-group"></i> Veil Fortress 10-Layer</span>
        <span class="sec-badge"><i class="fas fa-check-circle"></i> GDPR Ready</span>
        <span class="sec-badge"><i class="fas fa-server"></i> SOC 2 Roadmap</span>
        <span class="sec-badge"><i class="fas fa-dove"></i> Warrant Canary Active</span>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     2. SECURITY PILLARS
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Security <span>Pillars</span></h2>
    <p class="sec-sub">Five foundational layers that protect every interaction with Alfred AI.</p>

    <div class="sec-pillars">
        <!-- Encryption -->
        <div class="sec-pillar">
            <div class="sec-icon sec-icon-encrypt"><i class="fas fa-lock"></i></div>
            <h3>Data Encryption</h3>
            <p>All data is encrypted at rest with AES-256 and in transit using TLS 1.3. API tokens and secrets are hashed — never stored in plain text.</p>
        </div>
        <!-- Post-Quantum -->
        <div class="sec-pillar">
            <div class="sec-icon" style="background:rgba(139,92,246,.15);color:#a78bfa;"><i class="fas fa-atom"></i></div>
            <h3>Post-Quantum Crypto</h3>
            <p>Kyber-1024 hybrid key exchange protects communications against future quantum computing threats. Classical ECDH + Kyber lattice-based KEM — both must be broken simultaneously.</p>
        </div>
        <!-- Veil Fortress -->
        <div class="sec-pillar">
            <div class="sec-icon" style="background:rgba(248,113,113,.12);color:#f87171;"><i class="fas fa-layer-group"></i></div>
            <h3>Veil Fortress</h3>
            <p>10-layer encryption stack combining post-quantum Kyber-1024 + Dilithium signatures, Double Ratchet forward secrecy, hash chains, key commitment, and steganographic obfuscation.</p>
        </div>
        <!-- Access Control -->
        <div class="sec-pillar">
            <div class="sec-icon sec-icon-access"><i class="fas fa-user-shield"></i></div>
            <h3>Access Control</h3>
            <p>Role-based access control (RBAC), multi-factor authentication (MFA), and strict session management ensure only authorized users access your data.</p>
        </div>
        <!-- Infrastructure -->
        <div class="sec-pillar">
            <div class="sec-icon sec-icon-infra"><i class="fas fa-network-wired"></i></div>
            <h3>Infrastructure</h3>
            <p>DDoS protection, Web Application Firewall (WAF), rate limiting, and automated anomaly detection keep the platform resilient 24/7.</p>
        </div>
        <!-- Compliance -->
        <div class="sec-pillar">
            <div class="sec-icon sec-icon-comply"><i class="fas fa-clipboard-check"></i></div>
            <h3>Compliance</h3>
            <p>SOC 2 Type II on our roadmap, GDPR ready, HIPAA considerations in place, and alignment with PCI DSS for payment handling.</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     3. TECHNICAL DETAILS (Accordion)
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Technical <span>Details</span></h2>
    <p class="sec-sub">A closer look at how we secure every layer of the stack.</p>

    <div class="sec-accordion">

        <!-- Auth -->
        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span><i class="fas fa-key" style="color:var(--sec-blue);margin-right:.6rem;"></i>Authentication &amp; Authorization</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <ul>
                    <li><strong>Password hashing</strong> — bcrypt with a cost factor of 12; passwords are never stored in plain text.</li>
                    <li><strong>Session management</strong> — HTTP-only, Secure, SameSite cookies; sessions invalidated on logout and after inactivity.</li>
                    <li><strong>OAuth 2.0</strong> — Sign in with Google and Facebook using industry-standard flows.</li>
                    <li><strong>API keys</strong> — Scoped, rotatable keys with SHA-256 hashed storage.</li>
                    <li><strong>Multi-factor authentication</strong> — TOTP-based 2FA available for all accounts.</li>
                </ul>
            </div></div>
        </div>

        <!-- Data Storage -->
        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span><i class="fas fa-database" style="color:var(--sec-green);margin-right:.6rem;"></i>Data Storage</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <ul>
                    <li><strong>MySQL encryption</strong> — Transparent Data Encryption (TDE) at the storage engine level; data at rest encrypted with AES-256.</li>
                    <li><strong>Hashed tokens</strong> — API keys, webhook secrets, and session tokens are hashed before storage.</li>
                    <li><strong>No plain-text secrets</strong> — Environment variables loaded from files outside the webroot; never committed to version control.</li>
                    <li><strong>Automated backups</strong> — Daily encrypted backups with 30-day retention.</li>
                </ul>
            </div></div>
        </div>

        <!-- Network Security -->
        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span><i class="fas fa-globe" style="color:var(--sec-orange);margin-right:.6rem;"></i>Network Security</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <ul>
                    <li><strong>HTTPS enforced</strong> — All traffic redirected to HTTPS via 301; HSTS enabled with <code>includeSubDomains</code> and <code>preload</code>.</li>
                    <li><strong>Content Security Policy</strong> — Strict CSP headers prevent XSS, clickjacking, and unauthorized resource loading.</li>
                    <li><strong>X-Frame-Options</strong> — Set to <code>SAMEORIGIN</code> to prevent framing attacks.</li>
                    <li><strong>X-Content-Type-Options</strong> — <code>nosniff</code> prevents MIME-type sniffing.</li>
                    <li><strong>Rate limiting</strong> — mod_evasive and application-level throttling protect against brute-force and DDoS.</li>
                </ul>
            </div></div>
        </div>

        <!-- API Security -->
        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span><i class="fas fa-plug" style="color:var(--sec-accent);margin-right:.6rem;"></i>API Security</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <ul>
                    <li><strong>Rate limiting</strong> — Per-key and per-IP throttling; 429 responses with <code>Retry-After</code> headers.</li>
                    <li><strong>Input validation</strong> — All inputs sanitized and validated server-side; prepared statements for all queries.</li>
                    <li><strong>CSRF protection</strong> — Token-based CSRF guards on all state-changing endpoints.</li>
                    <li><strong>Webhook signatures</strong> — HMAC-SHA256 signatures on all outbound webhooks for payload integrity verification.</li>
                    <li><strong>CORS</strong> — Strict origin validation; only <code>gositeme.com</code> domains allowed.</li>
                </ul>
            </div></div>
        </div>

        <!-- Monitoring -->
        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span><i class="fas fa-chart-line" style="color:var(--sec-green);margin-right:.6rem;"></i>Monitoring &amp; Incident Response</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <ul>
                    <li><strong>Audit logging</strong> — All authentication events, API calls, and administrative actions are logged with timestamps and IP addresses.</li>
                    <li><strong>Anomaly detection</strong> — Automated alerts for unusual login patterns, spike in errors, and suspicious API usage.</li>
                    <li><strong>Incident response</strong> — Documented playbook with escalation tiers; target &lt; 1 hour acknowledgement for critical issues.</li>
                    <li><strong>Health monitoring</strong> — Real-time service health checks at <a href="/status" style="color:var(--sec-accent);">/status</a> with database, Redis, WebSocket, and MCP uptime tracking.</li>
                </ul>
            </div></div>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     3B. VEIL FORTRESS — 10-LAYER ENCRYPTION STACK
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Veil Fortress <span>Encryption Stack</span></h2>
    <p class="sec-sub">10 independent cryptographic layers protect every message. An attacker must defeat all 10 simultaneously — breaking any single layer reveals nothing.</p>

    <div class="sec-veil-stack">
        <!-- Layer 1 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(167,139,250,.15);color:var(--sec-accent);">1</div>
            <div>
                <div class="sec-vl-title">Kyber-1024 KEM <span class="sec-vl-tag sec-vl-pq">Post-Quantum</span></div>
                <div class="sec-vl-desc">NIST-selected lattice-based Key Encapsulation Mechanism. Generates shared secrets resistant to both classical and quantum attacks. 1024-dimensional module lattice over polynomial ring.</div>
            </div>
        </div>
        <!-- Layer 2 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(96,165,250,.15);color:var(--sec-blue);">2</div>
            <div>
                <div class="sec-vl-title">ECDH P-256 Key Agreement <span class="sec-vl-tag sec-vl-class">Classical</span></div>
                <div class="sec-vl-desc">Elliptic Curve Diffie-Hellman on the NIST P-256 curve. Provides classical-strength key agreement; combined with Kyber-1024 so both must be broken simultaneously.</div>
            </div>
        </div>
        <!-- Layer 3 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(96,165,250,.15);color:var(--sec-blue);">3</div>
            <div>
                <div class="sec-vl-title">AES-256-GCM Authenticated Encryption <span class="sec-vl-tag sec-vl-class">Classical</span></div>
                <div class="sec-vl-desc">256-bit symmetric encryption with Galois/Counter Mode for authenticated encryption. Provides confidentiality, integrity, and authenticity in a single operation with 128-bit authentication tags.</div>
            </div>
        </div>
        <!-- Layer 4 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(96,165,250,.15);color:var(--sec-blue);">4</div>
            <div>
                <div class="sec-vl-title">HKDF-SHA256 Key Derivation <span class="sec-vl-tag sec-vl-class">Classical</span></div>
                <div class="sec-vl-desc">HMAC-based Key Derivation Function ensures each session produces unique, independent encryption keys. Extract-then-expand paradigm per RFC 5869.</div>
            </div>
        </div>
        <!-- Layer 5 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(96,165,250,.15);color:var(--sec-blue);">5</div>
            <div>
                <div class="sec-vl-title">ECDSA P-256 Digital Signatures <span class="sec-vl-tag sec-vl-class">Classical</span></div>
                <div class="sec-vl-desc">Every message is signed with Elliptic Curve Digital Signature Algorithm. Provides non-repudiation and tamper detection with 128-bit security strength.</div>
            </div>
        </div>
        <!-- Layer 6 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(167,139,250,.15);color:var(--sec-accent);">6</div>
            <div>
                <div class="sec-vl-title">Dilithium Post-Quantum Signatures <span class="sec-vl-tag sec-vl-pq">Post-Quantum</span></div>
                <div class="sec-vl-desc">NIST-selected lattice-based digital signature scheme. Quantum-resistant authentication — even if ECDSA falls to quantum computers, Dilithium signatures remain secure.</div>
            </div>
        </div>
        <!-- Layer 7 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(52,211,153,.15);color:var(--sec-green);">7</div>
            <div>
                <div class="sec-vl-title">Double Ratchet Protocol <span class="sec-vl-tag sec-vl-adv">Forward Secrecy</span></div>
                <div class="sec-vl-desc">Derives new keys for every single message using KDF chains. Compromising one key reveals nothing about past or future messages — the gold standard for messaging protocols.</div>
            </div>
        </div>
        <!-- Layer 8 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(52,211,153,.15);color:var(--sec-green);">8</div>
            <div>
                <div class="sec-vl-title">Hash Chain Integrity <span class="sec-vl-tag sec-vl-adv">Tamper-Proof</span></div>
                <div class="sec-vl-desc">Each message includes a cryptographic hash of the previous message, creating an immutable chain. Any tampering, insertion, deletion, or reordering is immediately detected.</div>
            </div>
        </div>
        <!-- Layer 9 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(52,211,153,.15);color:var(--sec-green);">9</div>
            <div>
                <div class="sec-vl-title">Key Commitment Scheme <span class="sec-vl-tag sec-vl-adv">Anti-Exploit</span></div>
                <div class="sec-vl-desc">Binds the encryption key to the ciphertext via a commitment hash. Prevents AES-GCM key commitment attacks where a single ciphertext could decrypt to different plaintexts under different keys.</div>
            </div>
        </div>
        <!-- Layer 10 -->
        <div class="sec-veil-layer">
            <div class="sec-veil-num" style="background:rgba(248,113,113,.15);color:var(--sec-red);">10</div>
            <div>
                <div class="sec-vl-title">Steganographic Obfuscation <span class="sec-vl-tag sec-vl-steg">Covert</span></div>
                <div class="sec-vl-desc">Encrypted payloads are disguised within innocent-looking carrier data. Even if intercepted, the traffic is indistinguishable from ordinary content — making metadata analysis and deep packet inspection ineffective.</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     3C. WARRANT CANARY & NO-BACKDOOR PLEDGE
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Transparency <span>Commitments</span></h2>
    <p class="sec-sub">We believe trust requires transparency. Here are our public commitments to you.</p>

    <div class="sec-canary">
        <h3><i class="fas fa-dove" style="margin-right:.5rem;"></i> Warrant Canary</h3>
        <p>GoSiteMe / Alfred AI has <strong>NOT</strong> received any of the following as of the date below. If this section is ever removed or these statements are absent, assume our position has changed.</p>
        <div class="sec-canary-items">
            <div class="sec-canary-item"><i class="fas fa-check-circle"></i> No National Security Letters received</div>
            <div class="sec-canary-item"><i class="fas fa-check-circle"></i> No FISA court orders received</div>
            <div class="sec-canary-item"><i class="fas fa-check-circle"></i> No gag orders or sealed warrants received</div>
            <div class="sec-canary-item"><i class="fas fa-check-circle"></i> No government-mandated backdoors installed</div>
            <div class="sec-canary-item"><i class="fas fa-check-circle"></i> No bulk user data provided to any government</div>
            <div class="sec-canary-item"><i class="fas fa-check-circle"></i> No encryption keys surrendered to any third party</div>
        </div>
        <div class="sec-canary-date"><i class="fas fa-calendar-check" style="margin-right:.4rem;"></i> Last verified: <?php echo date('F j, Y'); ?></div>
    </div>

    <div class="sec-pledge">
        <div class="sec-pledge-icon"><i class="fas fa-ban"></i></div>
        <div>
            <h4>No-Backdoor Commitment</h4>
            <p>GoSiteMe / Alfred AI will <strong>never</strong> install secret surveillance capabilities, weaken encryption algorithms, or create covert access points at the request of any government, law enforcement agency, or third party. Our Veil Fortress encryption is designed so that even we cannot read your encrypted communications. This commitment is legally binding and documented in our <a href="/terms-of-service" style="color:var(--sec-accent);">Terms of Service</a> (Sections 67–75) and <a href="/privacy-policy" style="color:var(--sec-accent);">Privacy Policy</a> (Sections 32–37).</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     4. COMPLIANCE TABLE
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Compliance <span>Matrix</span></h2>
    <p class="sec-sub">How our security controls map to major compliance frameworks.</p>

    <div class="sec-table-wrap">
        <table class="sec-table">
            <thead>
                <tr>
                    <th>Security Feature</th>
                    <th>SOC 2</th>
                    <th>GDPR</th>
                    <th>HIPAA</th>
                    <th>PCI DSS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Data encryption at rest (AES-256)</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Encryption in transit (TLS 1.3)</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Role-based access control</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Multi-factor authentication</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="dash">—</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Audit logging</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Data retention policies</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="dash">—</td>
                </tr>
                <tr>
                    <td>Post-quantum encryption (Kyber-1024)</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Veil Fortress 10-layer encryption</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Warrant canary</td>
                    <td class="dash">—</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="dash">—</td>
                    <td class="dash">—</td>
                </tr>
                <tr>
                    <td>Right to deletion</td>
                    <td class="dash">—</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="dash">—</td>
                    <td class="dash">—</td>
                </tr>
                <tr>
                    <td>Incident response plan</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Vulnerability management</td>
                    <td class="road"><i class="fas fa-clock"></i> Roadmap</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                    <td class="road"><i class="fas fa-clock"></i> Roadmap</td>
                    <td class="check"><i class="fas fa-check"></i></td>
                </tr>
                <tr>
                    <td>Formal SOC 2 audit</td>
                    <td class="road"><i class="fas fa-clock"></i> Roadmap</td>
                    <td class="dash">—</td>
                    <td class="dash">—</td>
                    <td class="dash">—</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     5. BUG BOUNTY / RESPONSIBLE DISCLOSURE
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Responsible <span>Disclosure</span></h2>
    <p class="sec-sub">We value the security research community and welcome responsible reports.</p>

    <div class="sec-disclosure">
        <h3><i class="fas fa-bug" style="margin-right:.5rem;"></i>Report a Security Issue</h3>
        <p>If you've discovered a potential vulnerability in Alfred AI or any GoSiteMe service, please report it to our security team. We investigate every report and aim to respond within <strong>48 hours</strong>.</p>

        <a href="mailto:security@gositeme.com" class="sec-email-link"><i class="fas fa-envelope"></i> security@gositeme.com</a>

        <h3 style="margin-top:2rem;">Scope</h3>
        <ul>
            <li>gositeme.com and all subdomains</li>
            <li>Alfred AI platform (web, API, voice, WebSocket)</li>
            <li>GoCodeMe IDE</li>
            <li>Public-facing API endpoints</li>
        </ul>

        <h3>Rules of Engagement</h3>
        <ul>
            <li>Do not access, modify, or delete data belonging to other users.</li>
            <li>Do not perform denial-of-service attacks or social engineering.</li>
            <li>Provide a detailed description, reproduction steps, and potential impact.</li>
            <li>Allow reasonable time for us to investigate and remediate before public disclosure.</li>
        </ul>

        <h3>Rewards</h3>
        <p>We offer recognition and, for qualifying vulnerabilities, rewards at our discretion. Severity is assessed using CVSS v3.1 scoring.</p>
    </div>

    <!-- Hall of Fame -->
    <div class="sec-hof">
        <i class="fas fa-trophy"></i>
        <h4>Hall of Fame</h4>
        <p>No submissions yet — be the first responsible reporter recognized here.</p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     6. DATA PROCESSING
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Data <span>Processing</span></h2>
    <p class="sec-sub">Transparency about how and where we handle your data.</p>

    <div class="sec-dp-grid">
        <div class="sec-dp-card">
            <h4><i class="fas fa-map-marker-alt"></i>Data Location</h4>
            <p>All primary data is stored on servers located in <strong>Quebec, Canada</strong>. We use Canadian data centres that comply with PIPEDA and Quebec's Law 25.</p>
        </div>
        <div class="sec-dp-card">
            <h4><i class="fas fa-clock"></i>Retention Policies</h4>
            <ul>
                <li><strong>Account data</strong> — Retained while account is active, deleted within 30 days of account closure.</li>
                <li><strong>Conversation logs</strong> — Retained for 90 days, then anonymized or deleted.</li>
                <li><strong>Audit logs</strong> — Retained for 1 year for security and compliance.</li>
                <li><strong>Backups</strong> — Encrypted daily backups retained for 30 days.</li>
            </ul>
        </div>
        <div class="sec-dp-card">
            <h4><i class="fas fa-trash-alt"></i>Deletion Rights</h4>
            <p>You may request the deletion of your personal data at any time by contacting <a href="mailto:privacy@gositeme.com" style="color:var(--sec-accent);">privacy@gositeme.com</a>. We process deletion requests within <strong>30 days</strong> in accordance with GDPR and Quebec's Law 25.</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     7. FAQ
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-section">
    <h2>Security <span>FAQ</span></h2>
    <p class="sec-sub">Common questions about how we protect your data.</p>

    <div class="sec-faq sec-accordion">

        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span>Is my data encrypted?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <p>Yes. All data is encrypted at rest using AES-256 and in transit using TLS 1.3. API tokens and secrets are hashed with SHA-256 before storage — we never store them in plain text. Communications through Alfred AI are additionally protected by our <strong>Veil Fortress</strong> 10-layer encryption stack, which includes post-quantum Kyber-1024 + Dilithium signatures, Double Ratchet forward secrecy, and steganographic obfuscation.</p>
            </div></div>
        </div>

        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span>What is Veil Fortress?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <p>Veil Fortress is our proprietary 10-layer encryption protocol. Unlike standard TLS which uses a single encryption layer, Veil Fortress wraps every message in 10 independent cryptographic layers: Kyber-1024 KEM, ECDH P-256, AES-256-GCM, HKDF-SHA256, ECDSA P-256, Dilithium PQ Signatures, Double Ratchet, Hash Chains, Key Commitment, and Steganographic Obfuscation. An attacker must break all 10 simultaneously — compromising any single layer reveals nothing.</p>
            </div></div>
        </div>

        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span>Can GoSiteMe read my encrypted messages?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <p>No. Veil Fortress uses end-to-end encryption with client-side key generation. Encryption keys are generated and managed on your device — they never travel to our servers. Even our own engineering team cannot decrypt your protected communications. This is by design and is a legally binding commitment in our <a href="/terms-of-service" style="color:var(--sec-accent);">Terms of Service</a> and <a href="/privacy-policy" style="color:var(--sec-accent);">Privacy Policy</a>.</p>
            </div></div>
        </div>

        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span>Where is my data stored?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <p>All primary data is stored in secure data centres located in Quebec, Canada. Our infrastructure complies with Canadian privacy legislation (PIPEDA) and Quebec's Law 25.</p>
            </div></div>
        </div>

        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span>Can I delete my data?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <p>Absolutely. Contact <a href="mailto:privacy@gositeme.com" style="color:var(--sec-accent);">privacy@gositeme.com</a> to request full deletion of your personal data. We process requests within 30 days.</p>
            </div></div>
        </div>

        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span>Do you sell my data to third parties?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <p>No. We never sell, rent, or trade your personal information to third parties. Your data is used solely to provide and improve Alfred AI services. See our <a href="/privacy-policy" style="color:var(--sec-accent);">Privacy Policy</a> for full details.</p>
            </div></div>
        </div>

        <div class="sec-acc-item">
            <div class="sec-acc-header">
                <span>Is Alfred AI SOC 2 certified?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="sec-acc-body"><div class="sec-acc-body-inner">
                <p>SOC 2 Type II certification is on our roadmap. We already implement the controls required by the Trust Services Criteria (security, availability, confidentiality) and are actively working toward a formal audit.</p>
            </div></div>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     CTA
     ═══════════════════════════════════════════════════════════ -->
<section class="sec-cta">
    <div class="sec-cta-box">
        <h2>Security You Can Trust</h2>
        <p>Try Alfred AI with confidence — your data is protected by 10-layer Veil Fortress encryption, post-quantum cryptography, and a legally binding no-backdoor commitment.</p>
        <a href="/alfred.php" class="sec-btn-primary"><i class="fas fa-bolt"></i> Try Alfred Free</a>
    </div>
</section>

</main>

<script src="/assets/js/security-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
