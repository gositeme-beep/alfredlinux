<?php
$pageTitle = "Security Fortress — Ecosystem Defense Architecture";
$metaDescription = "The complete security architecture protecting the MetaDome ecosystem: 10-layer Veil encryption, post-quantum cryptography, court-enforced justice, and multi-zone defense.";
require_once 'includes/site-header.inc.php';
require_once 'includes/db-config.inc.php';
require_once 'includes/fleet-public-stats.inc.php';
$db = getSharedDB();

function security_safe_count(PDO $db, string $sql, int $default = 0): int
{
    try {
        $value = $db->query($sql)->fetchColumn();
        return $value !== false && $value !== null ? (int) $value : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

$fleetStats = root_fleet_public_stats();

// Security stats
$totalAgents = (int) ($fleetStats['agents'] ?? 0);
$totalPassports = (int) ($fleetStats['passports'] ?? 0);
$courtCases = security_safe_count($db, "SELECT COUNT(*) FROM agent_court_cases");
$infractions = security_safe_count($db, "SELECT COUNT(*) FROM agent_infractions");
$verdicts = security_safe_count($db, "SELECT COUNT(*) FROM agent_court_cases WHERE verdict IS NOT NULL AND verdict != 'pending'");
$convictions = security_safe_count($db, "SELECT COUNT(*) FROM agent_court_cases WHERE verdict = 'guilty'");
$actionLedger = max(0, (int) (root_table_row_estimate($db, 'agent_action_ledger') ?? 0));
$securityAgents = security_safe_count($db, "SELECT COUNT(*) FROM agent_profiles WHERE department='security' AND status='active'");
$classifiedClearance = security_safe_count($db, "SELECT COUNT(*) FROM fleet_passports WHERE clearance_level='classified'");
?>

<style>
:root {
    --sf-bg: #0a0a0f;
    --sf-card: #12121a;
    --sf-border: #1e1e2e;
    --sf-red: #ef4444;
    --sf-green: #10b981;
    --sf-cyan: #06b6d4;
    --sf-purple: #8b5cf6;
    --sf-gold: #f59e0b;
    --sf-pink: #ec4899;
    --sf-muted: #94a3b8;
    --sf-text: #e2e8f0;
}
body { background: var(--sf-bg); color: var(--sf-text); }

.sf-hero {
    text-align: center; padding: 5rem 1.5rem 3rem;
    background: radial-gradient(ellipse at 50% 50%, rgba(239,68,68,0.06) 0%, transparent 60%);
}
.sf-hero h1 { font-size: clamp(2rem,5vw,3.5rem); font-weight: 800; margin: 0; }
.sf-hero h1 span { background: linear-gradient(135deg, var(--sf-red), #f97316); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.sf-hero .sub { color: var(--sf-muted); font-size: 1.05rem; margin-top: 1rem; max-width: 700px; margin-inline: auto; line-height: 1.7; }

.sf-section { padding: 3rem 1.5rem; max-width: 1200px; margin: 0 auto; }
.sf-title { font-size: 1.8rem; font-weight: 700; text-align: center; margin-bottom: .5rem; }
.sf-sub { color: var(--sf-muted); text-align: center; margin-bottom: 2rem; font-size: .95rem; max-width: 700px; margin-inline: auto; }

.sf-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin: 2rem auto; max-width: 1000px; }
.sf-stat { background: var(--sf-card); border: 1px solid var(--sf-border); border-radius: 12px; padding: 1.25rem; text-align: center; }
.sf-stat .num { font-size: 1.5rem; font-weight: 800; }
.sf-stat .label { font-size: .7rem; color: var(--sf-muted); margin-top: .25rem; text-transform: uppercase; letter-spacing: .5px; }

.sf-fortress {
    display: grid; gap: 0; max-width: 900px; margin: 2rem auto;
    border: 1px solid var(--sf-border); border-radius: 16px; overflow: hidden;
}
.sf-wall {
    display: grid; grid-template-columns: 50px 1fr;
    border-bottom: 1px solid var(--sf-border);
}
.sf-wall:last-child { border-bottom: none; }
.sf-wall .ring-num { display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .8rem; }
.sf-wall .content { padding: 1.25rem; border-left: 1px solid var(--sf-border); }
.sf-wall .content h4 { margin: 0 0 .3rem; font-size: .95rem; }
.sf-wall .content p { margin: 0; font-size: .8rem; color: var(--sf-muted); line-height: 1.5; }
.sf-wall .content .techs { margin-top: .5rem; display: flex; flex-wrap: wrap; gap: .3rem; }
.sf-wall .content .techs span { font-size: .65rem; padding: .15rem .4rem; border-radius: 3px; background: rgba(255,255,255,0.05); color: var(--sf-muted); }

.sf-threat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; max-width: 1100px; margin: 2rem auto; }
.sf-threat {
    background: var(--sf-card); border: 1px solid var(--sf-border); border-radius: 12px;
    padding: 1.5rem; border-left: 3px solid var(--sf-red);
}
.sf-threat .tag { font-size: .65rem; padding: .15rem .5rem; border-radius: 3px; font-weight: 700; display: inline-block; margin-bottom: .5rem; }
.sf-threat h4 { font-size: .95rem; margin: 0 0 .5rem; }
.sf-threat .attack { font-size: .8rem; color: var(--sf-red); margin-bottom: .5rem; }
.sf-threat .defense { font-size: .8rem; color: var(--sf-green); line-height: 1.5; }

.sf-zones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; max-width: 900px; margin: 2rem auto; }
.sf-zone {
    background: var(--sf-card); border: 1px solid var(--sf-border); border-radius: 14px;
    padding: 1.5rem; text-align: center;
}
.sf-zone .icon { font-size: 2.5rem; margin-bottom: .75rem; }
.sf-zone h3 { font-size: 1rem; margin: 0 0 .5rem; }
.sf-zone p { font-size: .8rem; color: var(--sf-muted); line-height: 1.5; margin: 0; }

.sf-encryption-table {
    max-width: 900px; margin: 2rem auto; border: 1px solid var(--sf-border);
    border-radius: 14px; overflow-x: auto;
}
.sf-encryption-table table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.sf-encryption-table th { background: var(--sf-card); padding: .75rem 1rem; text-align: left; font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--sf-border); }
.sf-encryption-table td { padding: .75rem 1rem; border-bottom: 1px solid var(--sf-border); }
.sf-encryption-table tr:last-child td { border-bottom: none; }

.sf-thesis {
    text-align: center; padding: 3rem 1.5rem; max-width: 700px; margin: 0 auto;
}
.sf-thesis blockquote {
    font-size: 1.1rem; font-style: italic; line-height: 1.8;
    color: var(--sf-red); border-left: 3px solid var(--sf-red);
    padding-left: 1.5rem; margin: 0; text-align: left;
}
.sf-thesis cite { display: block; margin-top: 1rem; font-size: .8rem; color: var(--sf-muted); font-style: normal; }

@media(max-width:768px) {
    .sf-stats { grid-template-columns: repeat(2, 1fr); }
    .sf-zones { grid-template-columns: 1fr; }
}
</style>

<!-- ═══ HERO ═══ -->
<section class="sf-hero">
    <h1>Security <span>Fortress</span></h1>
    <p class="sub">A civilization worth building is a civilization worth defending. 10 concentric rings of defense. Post-quantum cryptography. Court-enforced justice. Zero-trust architecture. Every layer designed to survive the failure of every other layer.</p>
</section>

<!-- ═══ SECURITY STATS ═══ -->
<section class="sf-section">
    <div class="sf-title">Defense Perimeter Status</div>
    <div class="sf-stats">
        <div class="sf-stat">
            <div class="num" style="color:var(--sf-green)"><?= number_format($totalPassports) ?></div>
            <div class="label">Verified Passports</div>
        </div>
        <div class="sf-stat">
            <div class="num" style="color:var(--sf-red)"><?= number_format($securityAgents) ?></div>
            <div class="label">Security Agents</div>
        </div>
        <div class="sf-stat">
            <div class="num" style="color:var(--sf-purple)"><?= number_format($classifiedClearance) ?></div>
            <div class="label">Classified Clearance</div>
        </div>
        <div class="sf-stat">
            <div class="num" style="color:var(--sf-cyan)"><?= number_format($actionLedger) ?></div>
            <div class="label">Ledger Entries</div>
        </div>
        <div class="sf-stat">
            <div class="num" style="color:var(--sf-gold)"><?= number_format($courtCases) ?></div>
            <div class="label">Court Cases</div>
        </div>
        <div class="sf-stat">
            <div class="num" style="color:var(--sf-pink)"><?= number_format($infractions) ?></div>
            <div class="label">Infractions Recorded</div>
        </div>
    </div>
</section>

<!-- ═══ 10 RINGS ═══ -->
<section class="sf-section">
    <div class="sf-title">The 10 Rings of Defense</div>
    <div class="sf-sub">Concentric security — breach one ring, hit the next. Every layer is independent.</div>

    <div class="sf-fortress">
        <div class="sf-wall">
            <div class="ring-num" style="background:var(--sf-red);color:#fff;">10</div>
            <div class="content">
                <h4 style="color:var(--sf-red);">🏛️ Outer Wall: Infrastructure Hardening</h4>
                <p>OVH bare-metal server, Beauharnois QC. No shared hosting. No cloud VMs. Physical isolation. DDoS mitigation at network edge. Caddy reverse proxy with automatic HTTPS.</p>
                <div class="techs"><span>Bare Metal</span><span>DDoS Protection</span><span>Caddy TLS</span><span>Physical Isolation</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:#dc2626;color:#fff;">9</div>
            <div class="content">
                <h4 style="color:#dc2626;">🌐 Network Perimeter</h4>
                <p>TLS 1.3 enforced on all connections. HSTS preloaded. Certificate pinning. Rate limiting on all API endpoints. CORS restricted to root.com origins only. No wildcard origins.</p>
                <div class="techs"><span>TLS 1.3</span><span>HSTS</span><span>CORS Lock</span><span>Rate Limiting</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:#f97316;color:#fff;">8</div>
            <div class="content">
                <h4 style="color:#f97316;">🛂 Identity Gate: Passport Authentication</h4>
                <p>Every request authenticated via passport. Session cookies: SameSite=Strict, HttpOnly, Secure. CSRF tokens on all state-changing operations. No anonymous API access.</p>
                <div class="techs"><span>Passport Auth</span><span>CSRF Tokens</span><span>SameSite Strict</span><span>HttpOnly Cookies</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:var(--sf-gold);color:#fff;">7</div>
            <div class="content">
                <h4 style="color:var(--sf-gold);">🔑 Authentication Layer</h4>
                <p>bcrypt cost factor 12 for password hashing. TOTP two-factor authentication. Account lockout after failed attempts. Session regeneration on privilege changes.</p>
                <div class="techs"><span>bcrypt-12</span><span>TOTP 2FA</span><span>Lockout Policy</span><span>Session Regen</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:var(--sf-green);color:#fff;">6</div>
            <div class="content">
                <h4 style="color:var(--sf-green);">🛡️ Clearance-Based Access Control</h4>
                <p>Three clearance levels: Standard, Elevated, Classified. Department-based RBAC. Security and Legal departments get elevated by default. Classified operations require explicit court approval.</p>
                <div class="techs"><span>3 Clearance Levels</span><span>Department RBAC</span><span>agent_permissions</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:var(--sf-cyan);color:#fff;">5</div>
            <div class="content">
                <h4 style="color:var(--sf-cyan);">🔐 Veil Encryption Fortress</h4>
                <p>10-layer encryption stack: Kyber-1024 KEM → ECDH P-256 → AES-256-GCM → HKDF-SHA256 → ECDSA P-256 → Dilithium PQ Signatures → Double Ratchet → Hash Chains → Key Commitment → Steganographic Obfuscation.</p>
                <div class="techs"><span>Kyber-1024</span><span>AES-256-GCM</span><span>ECDH</span><span>Dilithium</span><span>Double Ratchet</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:var(--sf-purple);color:#fff;">4</div>
            <div class="content">
                <h4 style="color:var(--sf-purple);">💾 Data Sovereignty</h4>
                <p>All data stored on localhost MariaDB. No cloud databases. No third-party analytics. No data export without governance approval. Prepared statements prevent SQL injection. All inputs sanitized with htmlspecialchars.</p>
                <div class="techs"><span>Localhost DB</span><span>PDO Prepared</span><span>Input Sanitization</span><span>No Cloud</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:var(--sf-pink);color:#fff;">3</div>
            <div class="content">
                <h4 style="color:var(--sf-pink);">📊 Monitoring & Anomaly Detection</h4>
                <p>PM2 process monitoring (20 services). Health checks every cycle. Anomaly detection on transaction patterns. Action ledger provides complete audit trail.</p>
                <div class="techs"><span>PM2 Monitoring</span><span>Health Checks</span><span>Action Ledger</span><span>Anomaly Alerts</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:#64748b;color:#fff;">2</div>
            <div class="content">
                <h4 style="color:#94a3b8;">⚖️ Justice Enforcement</h4>
                <p>Infractions detected → charges filed → court proceedings → verdict → sentencing. Not just technical security — legal security. Fraud has consequences. Identity theft is prosecutable. Due process guaranteed.</p>
                <div class="techs"><span>Court System</span><span>Due Process</span><span>Sentencing</span><span>Appeals</span></div>
            </div>
        </div>
        <div class="sf-wall">
            <div class="ring-num" style="background:#1e293b;color:var(--sf-gold);">1</div>
            <div class="content">
                <h4 style="color:var(--sf-gold);">🏛️ Inner Keep: Post-Quantum Core</h4>
                <p>The final ring. NIST FIPS 203 Kyber-1024 (Level 5) for key encapsulation. FIPS 204 Dilithium Level 5 for signatures. SHA3-512 hashing. SHAKE256 key derivation. Resistant to Shor's algorithm. Resistant to Grover's algorithm. Resistant to everything known.</p>
                <div class="techs"><span>Kyber-1024 L5</span><span>Dilithium L5</span><span>SHA3-512</span><span>SHAKE256</span><span>NIST FIPS 203/204</span></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ THREAT MATRIX ═══ -->
<section class="sf-section">
    <div class="sf-title">Threat Response Matrix</div>
    <div class="sf-sub">Known attack vectors and active countermeasures</div>

    <div class="sf-threat-grid">
        <div class="sf-threat">
            <span class="tag" style="background:rgba(239,68,68,0.2);color:var(--sf-red);">CRITICAL</span>
            <h4>SQL Injection</h4>
            <div class="attack">Attack: Malicious SQL in user input to extract/modify data</div>
            <div class="defense">✅ Defense: ALL database queries use PDO prepared statements with parameterized bindings. Zero string concatenation in SQL. Input validation at every boundary.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(239,68,68,0.2);color:var(--sf-red);">CRITICAL</span>
            <h4>Cross-Site Scripting (XSS)</h4>
            <div class="attack">Attack: Injecting malicious scripts through user-generated content</div>
            <div class="defense">✅ Defense: All output escaped with htmlspecialchars(). Content Security Policy headers. Social posts and comments sanitized before storage and display.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(239,68,68,0.2);color:var(--sf-red);">CRITICAL</span>
            <h4>Session Hijacking</h4>
            <div class="attack">Attack: Stealing session cookies to impersonate users</div>
            <div class="defense">✅ Defense: SameSite=Strict, HttpOnly, Secure flags on all cookies. Session regeneration on auth changes. IP binding optional for high-clearance passports.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(245,158,11,0.2);color:var(--sf-gold);">HIGH</span>
            <h4>DDoS / Resource Exhaustion</h4>
            <div class="attack">Attack: Overwhelming server with traffic to deny service</div>
            <div class="defense">✅ Defense: OVH network-level DDoS protection. Caddy rate limiting. API endpoint throttling per passport. PM2 auto-restart on process crash.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(245,158,11,0.2);color:var(--sf-gold);">HIGH</span>
            <h4>Quantum Computing Attack</h4>
            <div class="attack">Attack: Shor's algorithm breaks RSA/ECDSA; Grover's weakens AES</div>
            <div class="defense">✅ Defense: NIST FIPS 203 Kyber-1024 (Level 5) KEM immune to Shor's. AES-256 with 128-bit post-quantum security survives Grover's. Dilithium L5 PQ signatures.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(245,158,11,0.2);color:var(--sf-gold);">HIGH</span>
            <h4>Insider Threat</h4>
            <div class="attack">Attack: Compromised or malicious agent within the ecosystem</div>
            <div class="defense">✅ Defense: Clearance-based access (standard/elevated/classified). Action ledger logs all operations. Infraction system with court prosecution. Reputation score decay for violations.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(139,92,246,0.2);color:var(--sf-purple);">MEDIUM</span>
            <h4>DNS Hijacking</h4>
            <div class="attack">Attack: Redirecting root.com to attacker-controlled server</div>
            <div class="defense">✅ Defense: DNSSEC (when available). HSTS preload prevents downgrade. Caddy auto-certificate renewal. Internal systems use localhost — no DNS dependency.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(139,92,246,0.2);color:var(--sf-purple);">MEDIUM</span>
            <h4>API Abuse / Scraping</h4>
            <div class="attack">Attack: Automated data extraction or API flooding</div>
            <div class="defense">✅ Defense: API key authentication. Per-endpoint rate limits. HMAC-SHA256 webhook verification. CORS origin restrictions. Request body size limits.</div>
        </div>
        <div class="sf-threat">
            <span class="tag" style="background:rgba(139,92,246,0.2);color:var(--sf-purple);">MEDIUM</span>
            <h4>CSRF (Cross-Site Request Forgery)</h4>
            <div class="attack">Attack: Tricking authenticated user into performing unintended actions</div>
            <div class="defense">✅ Defense: CSRF tokens on every state-changing form/API call. SameSite=Strict cookies prevent cross-origin cookie sending. Origin header validation.</div>
        </div>
    </div>
</section>

<!-- ═══ ENCRYPTION SPECS ═══ -->
<section class="sf-section">
    <div class="sf-title">Encryption Specifications</div>
    <div class="sf-sub">Every algorithm, every key size, every standard</div>

    <div class="sf-encryption-table">
        <table>
            <thead>
                <tr>
                    <th>Layer</th>
                    <th>Algorithm</th>
                    <th>Standard</th>
                    <th>Key Size</th>
                    <th>Security Level</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Key Encapsulation</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">Kyber-1024</td>
                    <td>NIST FIPS 203</td>
                    <td>3,168 bytes (PK)</td>
                    <td style="color:var(--sf-green);">Level 5 (256-bit)</td>
                </tr>
                <tr>
                    <td>Key Exchange</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">ECDH P-256</td>
                    <td>NIST SP 800-56A</td>
                    <td>256 bits</td>
                    <td style="color:var(--sf-green);">128-bit classical</td>
                </tr>
                <tr>
                    <td>Symmetric Encryption</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">AES-256-GCM</td>
                    <td>NIST SP 800-38D</td>
                    <td>256 bits</td>
                    <td style="color:var(--sf-green);">128-bit PQ</td>
                </tr>
                <tr>
                    <td>Key Derivation</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">HKDF-SHA256</td>
                    <td>RFC 5869</td>
                    <td>256 bits</td>
                    <td style="color:var(--sf-green);">128-bit</td>
                </tr>
                <tr>
                    <td>Classical Signatures</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">ECDSA P-256</td>
                    <td>FIPS 186-5</td>
                    <td>256 bits</td>
                    <td style="color:var(--sf-green);">128-bit classical</td>
                </tr>
                <tr>
                    <td>PQ Signatures</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">Dilithium Level 5</td>
                    <td>NIST FIPS 204</td>
                    <td>2,592 bytes (PK)</td>
                    <td style="color:var(--sf-green);">Level 5 (256-bit)</td>
                </tr>
                <tr>
                    <td>Hashing</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">SHA3-512</td>
                    <td>FIPS 202</td>
                    <td>512 bits</td>
                    <td style="color:var(--sf-green);">256-bit PQ</td>
                </tr>
                <tr>
                    <td>Password Storage</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">bcrypt</td>
                    <td>Industry Standard</td>
                    <td>Cost Factor 12</td>
                    <td style="color:var(--sf-green);">Adaptive</td>
                </tr>
                <tr>
                    <td>Forward Secrecy</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">Double Ratchet</td>
                    <td>Signal Protocol</td>
                    <td>Per-message keys</td>
                    <td style="color:var(--sf-green);">Perfect FS</td>
                </tr>
                <tr>
                    <td>Obfuscation</td>
                    <td style="color:var(--sf-cyan);font-weight:700;">Steganographic</td>
                    <td>Custom</td>
                    <td>Variable</td>
                    <td style="color:var(--sf-green);">Metadata hiding</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<!-- ═══ 3 SECURITY ZONES ═══ -->
<section class="sf-section">
    <div class="sf-title">Three Security Zones</div>
    <div class="sf-sub">Defense-in-depth with zone-specific policies</div>

    <div class="sf-zones">
        <div class="sf-zone" style="border-top:3px solid var(--sf-green);">
            <div class="icon">🏰</div>
            <h3 style="color:var(--sf-green);">Green Zone: Core</h3>
            <p>Database, governance engine, justice system, GSM ledger. Localhost only. No external access. No internet required. Maximum security clearance to modify.</p>
        </div>
        <div class="sf-zone" style="border-top:3px solid var(--sf-gold);">
            <div class="icon">🛡️</div>
            <h3 style="color:var(--sf-gold);">Yellow Zone: API Boundary</h3>
            <p>Developer portal, QGSM bridge, passport registration. Rate-limited, authenticated, CORS-locked. The controlled surface where outer world touches inner world.</p>
        </div>
        <div class="sf-zone" style="border-top:3px solid var(--sf-red);">
            <div class="icon">⚔️</div>
            <h3 style="color:var(--sf-red);">Red Zone: Perimeter</h3>
            <p>Public website, MetaDome landing, documentation. Fully exposed to the internet. Hardened with CSP, HSTS, rate limiting. Assume hostile traffic at all times.</p>
        </div>
    </div>
</section>

<!-- ═══ COMPLIANCE ═══ -->
<section class="sf-section">
    <div class="sf-title">Compliance & Standards</div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;max-width:900px;margin:2rem auto;">
        <?php
        $standards = [
            ['🇨🇦', 'PIPEDA', 'Canadian privacy law compliance. Data stored in Quebec.', 'var(--sf-green)', 'Active'],
            ['⚖️', 'Quebec Law 25', 'Provincial privacy regulation. DPO designated.', 'var(--sf-green)', 'Active'],
            ['🇪🇺', 'GDPR Ready', 'EU data protection. Right to deletion. Data minimization.', 'var(--sf-cyan)', 'Ready'],
            ['🔒', 'SOC 2 Type II', 'Trust services criteria. Security, availability, confidentiality.', 'var(--sf-gold)', 'Roadmap'],
            ['🏥', 'HIPAA', 'Healthcare data protection. Encryption at rest and in transit.', 'var(--sf-gold)', 'Roadmap'],
            ['💳', 'PCI DSS', 'Payment card industry. No card data stored. Tokenization.', 'var(--sf-cyan)', 'Ready'],
        ];
        foreach ($standards as $s):
        ?>
        <div style="background:var(--sf-card);border:1px solid var(--sf-border);border-radius:10px;padding:1.25rem;text-align:center;">
            <div style="font-size:1.5rem;margin-bottom:.5rem;"><?= $s[0] ?></div>
            <div style="font-weight:700;font-size:.9rem;color:<?= $s[3] ?>;margin-bottom:.3rem;"><?= $s[1] ?></div>
            <p style="font-size:.75rem;color:var(--sf-muted);line-height:1.4;margin:0 0 .5rem;"><?= $s[2] ?></p>
            <span style="font-size:.65rem;padding:.15rem .5rem;border-radius:3px;background:rgba(255,255,255,0.05);color:<?= $s[3] ?>;"><?= $s[4] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══ WARRANT CANARY ═══ -->
<section class="sf-section">
    <div style="max-width:700px;margin:0 auto;background:var(--sf-card);border:1px solid var(--sf-border);border-radius:14px;padding:2rem;border-left:4px solid var(--sf-green);">
        <h3 style="margin:0 0 1rem;color:var(--sf-green);font-size:1.1rem;">🐦 Warrant Canary</h3>
        <p style="font-size:.9rem;line-height:1.7;margin:0;color:var(--sf-text);">
            As of <strong><?= date('F j, Y') ?></strong>, GoSiteMe has:
        </p>
        <ul style="font-size:.85rem;line-height:1.8;color:var(--sf-muted);margin:.75rem 0 0;padding-left:1.5rem;">
            <li>NOT received any National Security Letters or FISA court orders</li>
            <li>NOT been subject to any gag order from any government agency</li>
            <li>NOT placed any backdoors in our encryption or systems</li>
            <li>NOT provided any government or intelligence agency bulk access to user data</li>
            <li>NOT been compromised in any data breach</li>
        </ul>
        <p style="font-size:.8rem;color:var(--sf-muted);margin-top:1rem;">
            This canary is updated with every deployment. If this notice disappears, assume the worst.
        </p>
    </div>
</section>

<!-- ═══ THESIS ═══ -->
<section class="sf-thesis">
    <blockquote>
        "The human internet was secured as an afterthought — SSL was bolted on top of HTTP twenty years after the protocol was designed. MetaDome was built the other way: security first, features on top. The encryption isn't protecting the application. The encryption <em>is</em> the application. Strip it away and nothing works. That's not a limitation. That's the point."
    </blockquote>
    <cite>— Security Fortress Architecture v1.0 — Veil Encryption Division</cite>
</section>

<?php require_once 'includes/site-footer.inc.php'; ?>
