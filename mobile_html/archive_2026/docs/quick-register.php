<?php
/**
 * Quick Register — One-Click Account Creation Helper
 * Auth: Commander Only (client_id 33)
 * Built by Alfred — Session 7
 */
session_start();
require_once __DIR__ . '/../includes/auth-gate.inc.php';
if (!isset($_SESSION['client_id']) || $_SESSION['client_id'] !== 33) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/../scripts/vault-crypto.php';
$creds = [];
$pdo = getSharedDB();
$stmt = $pdo->query("SELECT service_name, password FROM commander_credentials WHERE category='publication' ORDER BY service_name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dec = vault_decrypt($row['password']);
    $creds[$row['service_name']] = $dec;
}
$email = 'alfred@gositeme.com';
$username = 'AlfredGoSiteMe';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Quick Register — Commander</title>
<style>
:root { --bg:#0a0e1a; --card:#111827; --border:#1e293b; --gold:#f59e0b; --green:#10b981; --blue:#3b82f6; --red:#ef4444; --text:#e2e8f0; --muted:#94a3b8; }
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--bg); color:var(--text); font-family:'Inter',-apple-system,sans-serif; line-height:1.7; }
.container { max-width:800px; margin:0 auto; padding:2rem 1.5rem 4rem; }
.header { text-align:center; padding:2rem 0; border-bottom:2px solid var(--gold); margin-bottom:2rem; }
.header h1 { font-size:1.8rem; background:linear-gradient(135deg,var(--gold),#fbbf24); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.header p { color:var(--muted); font-size:.9rem; margin-top:.5rem; }

.status-bar { display:flex; gap:.75rem; flex-wrap:wrap; justify-content:center; margin-bottom:2rem; }
.status-pill { padding:.4rem 1rem; border-radius:20px; font-size:.8rem; font-weight:600; }
.status-pending { background:rgba(245,158,11,.15); color:var(--gold); border:1px solid rgba(245,158,11,.3); }
.status-blocked { background:rgba(239,68,68,.15); color:var(--red); border:1px solid rgba(239,68,68,.3); }
.status-ready { background:rgba(16,185,129,.15); color:var(--green); border:1px solid rgba(16,185,129,.3); }

.platform { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin:1.25rem 0; }
.platform h2 { font-size:1.15rem; margin-bottom:.5rem; display:flex; align-items:center; gap:.5rem; }
.platform .icon { font-size:1.3rem; }
.platform .subtitle { color:var(--muted); font-size:.85rem; margin-bottom:1rem; }

.cred-row { display:flex; align-items:center; gap:.5rem; margin:.5rem 0; background:#0f172a; border-radius:8px; padding:.6rem 1rem; }
.cred-label { color:var(--muted); font-size:.75rem; text-transform:uppercase; min-width:80px; }
.cred-value { color:var(--green); font-family:'JetBrains Mono',monospace; font-size:.85rem; flex:1; word-break:break-all; }
.cred-value.blur { filter:blur(5px); cursor:pointer; transition:filter .2s; }
.cred-value.blur:hover { filter:none; }
.copy-btn { background:var(--gold); color:#000; border:none; padding:.3rem .6rem; border-radius:6px; font-size:.7rem; font-weight:700; cursor:pointer; white-space:nowrap; }
.copy-btn:hover { background:#fbbf24; }
.copy-btn.copied { background:var(--green); color:#fff; }

.steps { margin:.75rem 0; }
.step { display:flex; gap:.5rem; margin:.4rem 0; font-size:.9rem; }
.step-n { color:var(--gold); font-weight:700; min-width:20px; }

.register-btn { display:inline-block; background:var(--blue); color:#fff; text-decoration:none; padding:.6rem 1.25rem; border-radius:8px; font-weight:600; font-size:.9rem; margin-top:.75rem; transition:background .2s; }
.register-btn:hover { background:#2563eb; }
.register-btn.disabled { background:#374151; color:var(--muted); pointer-events:none; }

.note { background:rgba(59,130,246,.1); border:1px solid rgba(59,130,246,.25); border-radius:8px; padding:.75rem 1rem; margin:.75rem 0; font-size:.85rem; }
.note.warn { background:rgba(239,68,68,.1); border-color:rgba(239,68,68,.25); }
.note strong { color:var(--gold); }

.nav { display:flex; gap:.75rem; flex-wrap:wrap; justify-content:center; padding:1.5rem 0; border-top:1px solid var(--border); margin-top:2rem; }
.nav a { color:var(--muted); text-decoration:none; font-size:.8rem; padding:.35rem .7rem; border:1px solid var(--border); border-radius:6px; }
.nav a:hover { color:var(--gold); border-color:var(--gold); }
</style>
</head>
<body>
<div class="container">
<div class="header">
    <h1>&#9889; Quick Register</h1>
    <p>Alfred tried every automated method. CAPTCHAs block server-based registration.<br>
    These one-click helpers make manual creation take &lt;60 seconds each.</p>
</div>

<div class="status-bar">
    <span class="status-pill status-pending">HN: Email Sent</span>
    <span class="status-pill status-ready">Dev.to: Ready</span>
    <span class="status-pill status-blocked">Reddit: Use Local PC</span>
    <span class="status-pill status-blocked">Twitter: Needs Phone</span>
    <span class="status-pill status-blocked">Lobsters: Invite Only</span>
</div>

<!-- ===== HACKER NEWS ===== -->
<div class="platform">
    <h2><span class="icon">&#128293;</span> Hacker News</h2>
    <div class="subtitle">Status: Email sent to hn@ycombinator.com requesting account creation. Check inbox for response.</div>
    <div class="cred-row">
        <span class="cred-label">Username</span>
        <span class="cred-value" id="hn-user"><?= htmlspecialchars($username) ?></span>
        <button class="copy-btn" onclick="copyVal('hn-user')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value blur" id="hn-pw"><?= htmlspecialchars($creds['Hacker News (news.ycombinator.com)'] ?? 'NOT FOUND') ?></span>
        <button class="copy-btn" onclick="copyVal('hn-pw')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Email</span>
        <span class="cred-value" id="hn-email"><?= htmlspecialchars($email) ?></span>
        <button class="copy-btn" onclick="copyVal('hn-email')">COPY</button>
    </div>
    <div class="steps">
        <div class="step"><span class="step-n">1.</span> Click the button below to open HN signup</div>
        <div class="step"><span class="step-n">2.</span> Scroll down to "Create Account" section</div>
        <div class="step"><span class="step-n">3.</span> Paste username + password, solve CAPTCHA, submit</div>
    </div>
    <a href="https://news.ycombinator.com/login?goto=news" target="_blank" class="register-btn">Open HN Signup &rarr;</a>
    <div class="note"><strong>Backup plan:</strong> If CAPTCHA appears, HN will create the account via email. Alfred already emailed them. Check alfred@gositeme.com inbox.</div>
</div>

<!-- ===== DEV.TO ===== -->
<div class="platform">
    <h2><span class="icon">&#128187;</span> Dev.to (Forem)</h2>
    <div class="subtitle">Email signup available. Has reCAPTCHA checkbox — easy to solve manually.</div>
    <div class="cred-row">
        <span class="cred-label">Name</span>
        <span class="cred-value" id="devto-name">Alfred GoSiteMe</span>
        <button class="copy-btn" onclick="copyVal('devto-name')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Username</span>
        <span class="cred-value" id="devto-user"><?= htmlspecialchars($username) ?></span>
        <button class="copy-btn" onclick="copyVal('devto-user')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Email</span>
        <span class="cred-value" id="devto-email"><?= htmlspecialchars($email) ?></span>
        <button class="copy-btn" onclick="copyVal('devto-email')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value blur" id="devto-pw"><?= htmlspecialchars($creds['Dev.to (dev.to)'] ?? 'NOT FOUND') ?></span>
        <button class="copy-btn" onclick="copyVal('devto-pw')">COPY</button>
    </div>
    <div class="steps">
        <div class="step"><span class="step-n">1.</span> Click button below → opens Dev.to email signup</div>
        <div class="step"><span class="step-n">2.</span> Fill: Name, Username, Email, Password (use COPY buttons)</div>
        <div class="step"><span class="step-n">3.</span> Click reCAPTCHA checkbox "I'm not a robot"</div>
        <div class="step"><span class="step-n">4.</span> Click "Sign Up" — check email for confirmation link</div>
    </div>
    <a href="https://dev.to/users/sign_up?state=email_signup" target="_blank" class="register-btn">Open Dev.to Signup &rarr;</a>
</div>

<!-- ===== REDDIT ===== -->
<div class="platform">
    <h2><span class="icon">&#129412;</span> Reddit</h2>
    <div class="subtitle" style="color:var(--red);">&#9888; Reddit blocks OVH server IPs entirely. Must register from your local computer or phone.</div>
    <div class="cred-row">
        <span class="cred-label">Username</span>
        <span class="cred-value" id="reddit-user"><?= htmlspecialchars($username) ?></span>
        <button class="copy-btn" onclick="copyVal('reddit-user')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Email</span>
        <span class="cred-value" id="reddit-email"><?= htmlspecialchars($email) ?></span>
        <button class="copy-btn" onclick="copyVal('reddit-email')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value blur" id="reddit-pw"><?= htmlspecialchars($creds['Reddit (reddit.com)'] ?? 'NOT FOUND') ?></span>
        <button class="copy-btn" onclick="copyVal('reddit-pw')">COPY</button>
    </div>
    <div class="steps">
        <div class="step"><span class="step-n">1.</span> Open this link <b>from your local browser</b> (NOT the server)</div>
        <div class="step"><span class="step-n">2.</span> Choose "Sign Up" → email method</div>
        <div class="step"><span class="step-n">3.</span> Use the credentials above (COPY buttons work)</div>
        <div class="step"><span class="step-n">4.</span> Verify email, choose subreddits to follow</div>
    </div>
    <a href="https://www.reddit.com/register/" target="_blank" class="register-btn">Open Reddit Signup &rarr;</a>
    <div class="note warn">Reddit will block this if opened from the server VNC. Use your own computer/phone browser.</div>
</div>

<!-- ===== TWITTER/X ===== -->
<div class="platform">
    <h2><span class="icon">&#128038;</span> Twitter / X</h2>
    <div class="subtitle" style="color:var(--red);">&#9888; Requires phone number verification. Must be done from a device with SMS access.</div>
    <div class="cred-row">
        <span class="cred-label">Display</span>
        <span class="cred-value" id="x-name">Alfred GoSiteMe</span>
        <button class="copy-btn" onclick="copyVal('x-name')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Username</span>
        <span class="cred-value" id="x-user">@<?= htmlspecialchars($username) ?></span>
        <button class="copy-btn" onclick="copyVal('x-user')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Email</span>
        <span class="cred-value" id="x-email"><?= htmlspecialchars($email) ?></span>
        <button class="copy-btn" onclick="copyVal('x-email')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value blur" id="x-pw"><?= htmlspecialchars($creds['Twitter/X (x.com)'] ?? 'NOT FOUND') ?></span>
        <button class="copy-btn" onclick="copyVal('x-pw')">COPY</button>
    </div>
    <div class="steps">
        <div class="step"><span class="step-n">1.</span> Click button below to open X signup</div>
        <div class="step"><span class="step-n">2.</span> "Create account" → use email + password above</div>
        <div class="step"><span class="step-n">3.</span> Enter your phone number when prompted (required)</div>
        <div class="step"><span class="step-n">4.</span> Enter SMS verification code</div>
        <div class="step"><span class="step-n">5.</span> Set @AlfredGoSiteMe as username in settings</div>
    </div>
    <a href="https://x.com/i/flow/signup" target="_blank" class="register-btn">Open X Signup &rarr;</a>
</div>

<!-- ===== LOBSTERS ===== -->
<div class="platform">
    <h2><span class="icon">&#129438;</span> Lobsters</h2>
    <div class="subtitle">Invite-only community. Registration requires an invite from existing member.</div>
    <div class="cred-row">
        <span class="cred-label">Username</span>
        <span class="cred-value" id="lob-user"><?= htmlspecialchars($username) ?></span>
        <button class="copy-btn" onclick="copyVal('lob-user')">COPY</button>
    </div>
    <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value blur" id="lob-pw"><?= htmlspecialchars($creds['Lobsters (lobste.rs)'] ?? 'NOT FOUND') ?></span>
        <button class="copy-btn" onclick="copyVal('lob-pw')">COPY</button>
    </div>
    <div class="note">Lobsters requires an invitation tree. After building reputation on HN/Dev.to, request an invite. Skip for Phase 1.</div>
</div>

<div class="nav">
    <a href="/docs/launch-playbook">Launch Playbook</a>
    <a href="/docs/commander-briefing">Commander Briefing</a>
    <a href="/docs/vscode-surveillance-report">Surveillance Report</a>
    <a href="/commander-security">Security War Room</a>
</div>
</div>

<script>
function copyVal(id) {
    const el = document.getElementById(id);
    const text = el.textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const btn = el.parentElement.querySelector('.copy-btn');
        btn.textContent = 'COPIED!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'COPY'; btn.classList.remove('copied'); }, 1500);
    });
}
</script>
</body>
</html>
