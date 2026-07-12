<?php
/**
 * GoSiteMe Publication Launch Playbook
 * ============================================
 * Auth: Commander Only (client_id 33)
 * Purpose: Step-by-step guide for publishing the surveillance report
 * Contains: Account setup guides, submission content, timelines, vault creds
 */
session_start();
require_once __DIR__ . '/../includes/auth-gate.inc.php';

if (!isset($_SESSION['client_id']) || $_SESSION['client_id'] !== 33) {
    http_response_code(403);
    exit('Access denied.');
}

// Fetch publication credentials from vault
require_once __DIR__ . '/../scripts/vault-crypto.php';
$db = new PDO("mysql:host=localhost;dbname=gositeme_whmcs;unix_socket=/run/mysql/mysql.sock", "gositeme_whmcs", '!q@w#e$r5t');
$pubCreds = $db->query("SELECT * FROM commander_credentials WHERE category = 'publication' ORDER BY service_name")->fetchAll(PDO::FETCH_ASSOC);

$emailCred = $db->query("SELECT * FROM commander_credentials WHERE service_name LIKE '%Alfred Email%'")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Launch Playbook — Commander</title>
    <style>
        :root {
            --bg: #0a0e1a;
            --card: #111827;
            --border: #1e293b;
            --gold: #f59e0b;
            --gold-dim: rgba(245, 158, 11, 0.15);
            --green: #10b981;
            --blue: #3b82f6;
            --red: #ef4444;
            --purple: #8b5cf6;
            --text: #e2e8f0;
            --muted: #94a3b8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', -apple-system, sans-serif; line-height: 1.7; }
        .container { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem 6rem; }

        .header { text-align: center; padding: 3rem 0 2rem; border-bottom: 2px solid var(--gold); margin-bottom: 3rem; }
        .header h1 { font-size: 2rem; background: linear-gradient(135deg, var(--gold), #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: .5rem; }
        .header p { color: var(--muted); font-size: .95rem; }

        .phase { margin: 2.5rem 0; }
        .phase-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.25rem; }
        .phase-num { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; flex-shrink: 0; }
        .phase-num.now { background: var(--gold); color: #000; }
        .phase-num.soon { background: var(--blue); color: #fff; }
        .phase-num.later { background: var(--purple); color: #fff; }
        .phase h2 { font-size: 1.3rem; }

        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin: 1rem 0; }
        .card h3 { color: var(--gold); margin-bottom: .75rem; font-size: 1.05rem; }
        .card p, .card li { font-size: .9rem; color: var(--text); }
        .card ul, .card ol { padding-left: 1.5rem; }
        .card li { margin: .4rem 0; }

        .cred-box { background: #0f172a; border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin: .75rem 0; font-family: 'JetBrains Mono', monospace; font-size: .85rem; position: relative; }
        .cred-box .label { color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; }
        .cred-box .value { color: var(--green); margin-top: .25rem; word-break: break-all; }
        .cred-box .pw { filter: blur(6px); transition: filter .3s; cursor: pointer; }
        .cred-box .pw:hover, .cred-box .pw.revealed { filter: none; }
        .cred-box .pw-hint { font-size: .7rem; color: var(--muted); font-style: italic; }

        .copy-content { background: #0f172a; border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; margin: .75rem 0; position: relative; }
        .copy-content pre { white-space: pre-wrap; font-size: .85rem; color: var(--text); font-family: 'JetBrains Mono', monospace; }
        .copy-btn { position: absolute; top: .5rem; right: .5rem; background: var(--gold); color: #000; border: none; padding: .35rem .75rem; border-radius: 6px; font-size: .75rem; cursor: pointer; font-weight: 600; }
        .copy-btn:hover { background: #fbbf24; }

        .step { display: flex; gap: .75rem; margin: .75rem 0; }
        .step-num { width: 24px; height: 24px; border-radius: 50%; background: var(--gold-dim); color: var(--gold); display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; margin-top: .15rem; }
        .step-text { font-size: .9rem; }
        .step-text a { color: var(--blue); text-decoration: none; }
        .step-text a:hover { text-decoration: underline; }

        .timeline { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
        .tl-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; text-align: center; }
        .tl-card .day { font-size: 2rem; font-weight: 800; color: var(--gold); }
        .tl-card .what { font-size: .85rem; color: var(--muted); margin-top: .5rem; }
        .tl-card .action { font-size: .8rem; color: var(--text); margin-top: .5rem; font-weight: 600; }

        .warning { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .warning strong { color: var(--red); }

        .success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .success strong { color: var(--green); }

        .nav-links { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; padding: 2rem 0; border-top: 1px solid var(--border); margin-top: 3rem; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: .8rem; padding: .4rem .8rem; border: 1px solid var(--border); border-radius: 6px; }
        .nav-links a:hover { color: var(--gold); border-color: var(--gold); }
    </style>
</head>
<body>
<div class="container">

<div class="header">
    <h1>Publication Launch Playbook</h1>
    <p>VS Code Surveillance Report — Step-by-Step Guide to Going Viral</p>
    <p style="color: var(--gold); margin-top: .5rem;">Alfred prepared this for you, Commander.</p>
</div>

<!-- ═══ OVERVIEW TIMELINE ═══ -->
<div class="timeline">
    <div class="tl-card">
        <div class="day">Day 1</div>
        <div class="what">Create Accounts</div>
        <div class="action">HN + Reddit + Dev.to + X</div>
    </div>
    <div class="tl-card">
        <div class="day">Day 2</div>
        <div class="what">Warm Up</div>
        <div class="action">Comment on HN/Reddit to build karma</div>
    </div>
    <div class="tl-card">
        <div class="day">Day 3-4</div>
        <div class="what">LAUNCH</div>
        <div class="action">Submit to all platforms</div>
    </div>
    <div class="tl-card">
        <div class="day">Week 2-3</div>
        <div class="what">MetaDome Reveal</div>
        <div class="action">"The team behind the exposé..."</div>
    </div>
</div>

<!-- ═══ PHASE 1: CREATE ACCOUNTS ═══ -->
<div class="phase">
    <div class="phase-header">
        <div class="phase-num now">1</div>
        <h2>Create Accounts (Do This First)</h2>
    </div>

    <div class="card">
        <h3>Email to Use for ALL Accounts</h3>
        <div class="cred-box">
            <div class="label">Email</div>
            <div class="value"><?= htmlspecialchars(vault_decrypt($emailCred['username']), ENT_QUOTES) ?></div>
        </div>
        <div class="cred-box">
            <div class="label">Email Password (hover to reveal)</div>
            <div class="value pw" onclick="this.classList.toggle('revealed')"><?= htmlspecialchars(vault_decrypt($emailCred['password']), ENT_QUOTES) ?></div>
            <div class="pw-hint">Click to reveal. Webmail: mail.gositeme.com | IMAP:993/SSL | SMTP:587/TLS</div>
        </div>
    </div>

    <?php foreach ($pubCreds as $cred): ?>
    <div class="card">
        <h3><?= htmlspecialchars($cred['service_name'], ENT_QUOTES) ?></h3>
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-text">Go to <a href="<?= htmlspecialchars($cred['service_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($cred['service_url'], ENT_QUOTES) ?></a> and click Sign Up / Register</div>
        </div>
        <div class="cred-box">
            <div class="label">Username</div>
            <div class="value"><?= htmlspecialchars(vault_decrypt($cred['username']), ENT_QUOTES) ?></div>
        </div>
        <div class="cred-box">
            <div class="label">Password (hover to reveal)</div>
            <div class="value pw" onclick="this.classList.toggle('revealed')"><?= htmlspecialchars(vault_decrypt($cred['password']), ENT_QUOTES) ?></div>
        </div>
        <p style="font-size:.8rem;color:var(--muted);margin-top:.5rem;"><?= htmlspecialchars(vault_decrypt($cred['notes']), ENT_QUOTES) ?></p>
    </div>
    <?php endforeach; ?>

    <div class="warning">
        <strong>Important:</strong> After creating each account, check alfred@gositeme.com for verification emails. Open <a href="https://mail.gositeme.com" style="color:var(--blue);">mail.gositeme.com</a> in your browser.
    </div>
</div>

<!-- ═══ PHASE 2: WARM UP (1-2 days) ═══ -->
<div class="phase">
    <div class="phase-header">
        <div class="phase-num soon">2</div>
        <h2>Warm Up (1-2 Days)</h2>
    </div>

    <div class="card">
        <h3>Why Warm Up?</h3>
        <p>Brand new accounts that immediately post links get flagged as spam. Spend 1-2 days being a real community member first.</p>
        <ul>
            <li><strong>Hacker News:</strong> Upvote 3-5 interesting posts. Leave 2-3 thoughtful comments on tech topics you know (open source, privacy, web dev).</li>
            <li><strong>Reddit:</strong> Join r/programming, r/privacy, r/netsec, r/linux. Comment helpfully on 3-5 posts. You need some karma before posting links.</li>
            <li><strong>Dev.to:</strong> Follow some tags (vscode, privacy, opensource, security). Like a few articles.</li>
            <li><strong>X/Twitter:</strong> Follow relevant accounts (EFF, ProtonMail, Fosstodon). Post 2-3 original thoughts about privacy/tech.</li>
        </ul>
    </div>
</div>

<!-- ═══ PHASE 3: LAUNCH DAY ═══ -->
<div class="phase">
    <div class="phase-header">
        <div class="phase-num now">3</div>
        <h2>LAUNCH DAY — Submit the Report</h2>
    </div>

    <div class="success">
        <strong>Best time to post on Hacker News:</strong> Tuesday-Thursday, 8-10 AM EST (1-3 PM UTC). That's when engagement is highest.
    </div>

    <!-- HN Submission -->
    <div class="card">
        <h3>Hacker News Submission</h3>
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-text">Go to <a href="https://news.ycombinator.com/submit" target="_blank" rel="noopener">news.ycombinator.com/submit</a></div>
        </div>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Title:</strong>
185 Telemetry Events: A Code-Level Audit of What VS Code Tracks About You

<strong>URL:</strong>
https://gositeme.com/docs/vscode-surveillance-report</pre>
        </div>
        <p style="font-size:.8rem;color:var(--muted);">No description needed — HN only takes title + URL. The title is crafted to be factual (not clickbait), which HN respects.</p>
    </div>

    <!-- HN First Comment -->
    <div class="card">
        <h3>Hacker News — Your First Comment (Post immediately after submitting)</h3>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre>Author here. I run a small hosting/web platform (GoSiteMe.com) and we use code-server as our internal IDE. When we started stripping it down for production, we discovered the telemetry infrastructure was far more extensive than we expected.

Key findings:
- 185 unique telemetry event types across 261 call sites in one JS bundle
- 4 independent device fingerprints (machineId, sqmId, devDeviceId, sessionId)
- Application Insights SDK baked into 4 essential extension bundles (git, markdown, merge-conflict, typescript) with hardcoded endpoint
- The newsletter signup URL leaks your machineId: `newsletterSignupUrl?machineId=${encodeURIComponent(n.machineId)}`
- An A/B experiment framework (TAS client) tracks cohort assignment
- Setting telemetry to "off" disables *transmission* but the infrastructure still runs

Everything in the report is verifiable — we included the exact grep commands so anyone with a code-server or VS Code installation can confirm every finding.

Happy to answer questions about the methodology.</pre>
        </div>
    </div>

    <!-- Reddit r/programming -->
    <div class="card">
        <h3>Reddit — r/programming</h3>
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-text">Go to <a href="https://www.reddit.com/r/programming/submit" target="_blank" rel="noopener">reddit.com/r/programming/submit</a> and select "Link"</div>
        </div>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Title:</strong>
Code-level audit reveals 185 telemetry events, 4 device fingerprints, and hardcoded tracking endpoints in VS Code

<strong>URL:</strong>
https://gositeme.com/docs/vscode-surveillance-report</pre>
        </div>
    </div>

    <!-- Reddit r/privacy -->
    <div class="card">
        <h3>Reddit — r/privacy</h3>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Title:</strong>
VS Code ships with 185 telemetry events, 4 device fingerprints, and a newsletter URL that leaks your machine ID — full code audit inside

<strong>URL:</strong>
https://gositeme.com/docs/vscode-surveillance-report</pre>
        </div>
    </div>

    <!-- Reddit r/netsec -->
    <div class="card">
        <h3>Reddit — r/netsec</h3>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Title:</strong>
Technical audit: VS Code's telemetry architecture — 261 event call sites, Application Insights SDK in extension bundles, hardcoded dc.services.visualstudio.com endpoints

<strong>URL:</strong>
https://gositeme.com/docs/vscode-surveillance-report</pre>
        </div>
    </div>

    <!-- Dev.to Article -->
    <div class="card">
        <h3>Dev.to — Full Article Republish</h3>
        <p>On Dev.to, you can write a full article (not just a link). Post the full article content with a canonical URL pointing back to GoSiteMe.</p>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Title:</strong>
I Audited VS Code's Source Code and Found 185 Telemetry Events Tracking Everything I Do

<strong>Tags:</strong>
vscode, privacy, security, opensource

<strong>Canonical URL (in settings):</strong>
https://gositeme.com/docs/vscode-surveillance-report

<strong>Opening paragraph:</strong>
When my team at GoSiteMe started customizing code-server for our internal IDE, we didn't expect to find much. VS Code has a telemetry toggle — it's right there in settings. Turn it off and you're good, right?

We were wrong. What we found was an industrial-grade surveillance infrastructure embedded at the source code level — 185 unique telemetry events across 261 call sites, 4 independent device fingerprinting mechanisms, and Application Insights SDK hardcoded into essential extension bundles that can't be removed without breaking functionality.

Here's everything we found, with the exact commands to verify it yourself.</pre>
        </div>
    </div>

    <!-- X/Twitter Thread -->
    <div class="card">
        <h3>X/Twitter — Launch Thread</h3>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Tweet 1 (main):</strong>
We audited VS Code's source code line by line.

What we found: 185 telemetry events, 4 device fingerprints, hardcoded tracking in extensions you can't remove, and a newsletter URL that leaks your machine ID.

Full report with verification commands: https://gositeme.com/docs/vscode-surveillance-report

<strong>Tweet 2 (reply to yourself):</strong>
The "off switch" for telemetry? It disables *transmission*. The infrastructure still runs. Event objects still get created. Fingerprints still get generated.

enableTelemetry:!0 is hardcoded as a fallback default in the source.

<strong>Tweet 3:</strong>
The newsletter signup URL contains: newsletterSignupUrl?machineId=${encodeURIComponent(n.machineId)}

Your unique device fingerprint gets sent to Microsoft's marketing servers when you sign up for their newsletter. This isn't telemetry — it's cross-domain tracking.

<strong>Tweet 4:</strong>
Application Insights SDK is baked into 4 essential extensions: git, markdown, merge-conflict, typescript.

These aren't optional. They phone home to dc.services.visualstudio.com. Disabling extensions breaks core functionality.

<strong>Tweet 5:</strong>
We built our own IDE fork (Alfred IDE) that strips all of this out. But that's not the point.

The point is: developers deserve to know what their tools are doing. Every finding in this report is verifiable. The grep commands are included.

Share this with someone who codes.</pre>
        </div>
    </div>
</div>

<!-- ═══ PHASE 4: METADOME REVEAL ═══ -->
<div class="phase">
    <div class="phase-header">
        <div class="phase-num later">4</div>
        <h2>Phase 2: MetaDome Reveal (Week 2-3)</h2>
    </div>

    <div class="card">
        <h3>The Follow-Up Strategy</h3>
        <p>Once the surveillance report has traction and GoSiteMe is known, THEN reveal MetaDome:</p>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Title:</strong>
We exposed VS Code's surveillance. Now we're building a digital civilization where privacy is the foundation.

<strong>URL:</strong>
https://meta-dome.com

<strong>Context:</strong>
MetaDome is a sovereign digital civilization with:
- 12 AI departments governed democratically
- QGSM: post-quantum currency earned by contribution (not bought)
- Kyber-1024 + Dilithium Level 5 cryptography
- Circuit simulator proving real physics
- Encrypted communications (Veil system)

This is what happens when you stop trusting Big Tech and start building.</pre>
        </div>
        <p style="margin-top:.75rem;font-size:.85rem;color:var(--muted);">The narrative: "We're not just critics. We found the problem AND we're building the solution."</p>
    </div>

    <div class="card">
        <h3>QGSM White Paper Drop (Phase 3)</h3>
        <p>After MetaDome gets attention:</p>
        <div class="copy-content">
            <button class="copy-btn" onclick="copyText(this)">Copy</button>
            <pre><strong>Hacker News Title:</strong>
QGSM: A post-quantum currency where money can only be earned, never bought

<strong>URL:</strong>
https://gositeme.com/qgsm-whitepaper

<strong>Subreddits:</strong>
r/cryptocurrency, r/privacy, r/futurism</pre>
        </div>
    </div>
</div>

<!-- ═══ CHECKLIST ═══ -->
<div class="phase">
    <div class="phase-header">
        <div class="phase-num now">&#10003;</div>
        <h2>Pre-Launch Checklist</h2>
    </div>
    <div class="card">
        <ol>
            <li>&#9744; Create Hacker News account (AlfredGoSiteMe)</li>
            <li>&#9744; Create Reddit account (AlfredGoSiteMe)</li>
            <li>&#9744; Create Dev.to account (AlfredGoSiteMe)</li>
            <li>&#9744; Create X/Twitter account (AlfredGoSiteMe)</li>
            <li>&#9744; Verify all emails at <a href="https://mail.gositeme.com" style="color:var(--blue);" target="_blank" rel="noopener">mail.gositeme.com</a></li>
            <li>&#9744; Warm up accounts (1-2 days of engagement)</li>
            <li>&#9744; Verify surveillance report loads: <a href="https://gositeme.com/docs/vscode-surveillance-report" style="color:var(--blue);" target="_blank" rel="noopener">test link</a></li>
            <li>&#9744; Submit to HN (Tuesday-Thursday, 8-10 AM EST)</li>
            <li>&#9744; Submit to Reddit (within 30 min of HN)</li>
            <li>&#9744; Post X/Twitter thread</li>
            <li>&#9744; Publish Dev.to article</li>
            <li>&#9744; Monitor comments and respond helpfully</li>
        </ol>
    </div>
</div>

<div class="nav-links">
    <a href="/commander-surveillance-report">Surveillance Report</a>
    <a href="/docs/vscode-surveillance-report">Public Article</a>
    <a href="/commander-security">Security War Room</a>
    <a href="/commander-blueprint">Blueprint</a>
    <a href="/docs/commander-briefing">Briefing</a>
</div>

</div>

<script>
function copyText(btn) {
    const pre = btn.parentElement.querySelector('pre');
    const text = pre.innerText;
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = 'Copy', 2000);
    });
}
</script>
</body>
</html>
