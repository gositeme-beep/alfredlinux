<?php
/**
 * ECOSYSTEM PRINCIPLES AGREEMENT
 * ═══════════════════════════════
 * Drafted by Atlas (Legal Director Agent)
 * For review at Tuesday March 10, 2026 @ 9:00 AM meeting
 * 
 * All ecosystem members must acknowledge these principles.
 */
require_once __DIR__ . '/../includes/auth-gate.inc.php';
$pageTitle = "Ecosystem Principles Agreement";
$clientId = $_SESSION['client_id'] ?? 0;
$clientName = $_SESSION['firstname'] ?? 'Member';

// Check if already accepted
define('GOSITEME_API', true);
require_once __DIR__ . '/../api/config.php';
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS ecosystem_agreements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        version VARCHAR(20) NOT NULL DEFAULT '1.0',
        accepted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent VARCHAR(500),
        UNIQUE KEY uk_client_version (client_id, version)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $stmt = $pdo->prepare("SELECT accepted_at FROM ecosystem_agreements WHERE client_id = ? AND version = '1.0'");
    $stmt->execute([$clientId]);
    $accepted = $stmt->fetch();
} catch (Exception $e) {
    $accepted = null;
}

// Handle acceptance POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO ecosystem_agreements (client_id, version, ip_address, user_agent) VALUES (?, '1.0', ?, ?)");
        $stmt->execute([$clientId, $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)]);
        header('Location: /docs/ecosystem-principles.php?accepted=1');
        exit;
    } catch (Exception $e) { /* continue */ }
}

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ecosystem Principles — GoSiteMe</title>
<style>
:root{--bg:#0f0f1a;--surface:#171728;--border:#252542;--text:#e2e8f0;--dim:#8b8fa3;--primary:#8b5cf6;--accent:#22d3ee;--green:#34d399;--gold:#f5c542;--red:#ef4444;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:'Inter',-apple-system,sans-serif;line-height:1.8;}
.doc{max-width:800px;margin:0 auto;padding:20px;}
.header{text-align:center;padding:40px 0 20px;border-bottom:2px solid var(--primary);}
.header h1{font-size:1.6rem;color:var(--gold);}
.header .sub{color:var(--dim);font-size:.85rem;margin-top:8px;}
.header .drafted{color:var(--primary);font-size:.8rem;margin-top:4px;}
.section{margin:28px 0;}
.section h2{color:var(--primary);font-size:1.15rem;padding-bottom:8px;border-bottom:1px solid var(--border);margin-bottom:12px;}
.section h3{color:var(--accent);font-size:.95rem;margin:16px 0 4px;}
.section p,.section li{font-size:.9rem;color:var(--dim);margin:4px 0;}
.section ol,.section ul{padding-left:24px;}
.section li{margin:6px 0;}
.principle-box{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;margin:12px 0;}
.principle-box h4{color:var(--gold);margin-bottom:4px;}
.accept-box{background:var(--surface);border:2px solid var(--green);border-radius:12px;padding:24px;margin:32px 0;text-align:center;}
.accept-box.done{border-color:var(--green);background:rgba(34,211,238,.05);}
.accept-btn{background:var(--green);color:#0a0a14;border:none;padding:14px 40px;font-size:1rem;font-weight:700;border-radius:8px;cursor:pointer;transition:transform .2s;}
.accept-btn:hover{transform:scale(1.03);}
.accepted-msg{color:var(--green);font-size:1.1rem;font-weight:600;}
.back-btn{position:fixed;top:20px;left:20px;background:var(--surface);border:1px solid var(--border);color:var(--text);padding:8px 16px;border-radius:8px;cursor:pointer;text-decoration:none;z-index:100;}
.back-btn:hover{border-color:var(--primary);color:var(--primary);}
</style>
</head>
<body>
<a href="/dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
<div class="doc">

<div class="header">
<h1>GoSiteMe Ecosystem Principles Agreement</h1>
<div class="sub">Version 1.0 — Effective March 2026</div>
<div class="drafted">Drafted by Atlas (Legal Director) — Reviewed by Commander Operations</div>
</div>

<div class="section">
<h2>Preamble</h2>
<p>The GoSiteMe ecosystem ("Ecosystem") is a collaborative AI-powered platform connecting service providers, clients, developers, and intelligent agents. These principles establish the foundation for trust, safety, and mutual benefit within the Ecosystem. By joining, all members ("Participants") agree to uphold these standards.</p>
</div>

<div class="section">
<h2>Article I — Core Principles</h2>

<div class="principle-box">
<h4>1.1 Integrity & Honesty</h4>
<p>All Participants shall conduct themselves with integrity. Misrepresentation of identity, capabilities, or intentions is prohibited. AI-generated content must be disclosed where reasonably expected.</p>
</div>

<div class="principle-box">
<h4>1.2 Respect & Dignity</h4>
<p>Every Participant — human or AI agent — is entitled to respectful treatment. Harassment, discrimination, and abusive conduct are strictly prohibited and will result in immediate account review.</p>
</div>

<div class="principle-box">
<h4>1.3 Security Responsibility</h4>
<p>Participants must protect their credentials, report security vulnerabilities responsibly, and never attempt unauthorized access to systems, accounts, or data belonging to others.</p>
</div>

<div class="principle-box">
<h4>1.4 Fair Use of Resources</h4>
<p>AI computing resources, bandwidth, and storage are shared assets. Participants shall use resources responsibly and not engage in activities designed to degrade service for others.</p>
</div>

<div class="principle-box">
<h4>1.5 Privacy Protection</h4>
<p>Participants shall respect the privacy of others. Personal information obtained through the Ecosystem shall not be shared, sold, or used for purposes beyond those explicitly authorized.</p>
</div>

<div class="principle-box">
<h4>1.6 Legal Compliance</h4>
<p>All activities within the Ecosystem must comply with applicable laws and regulations, including but not limited to: intellectual property rights, data protection (GDPR, PIPEDA, CCPA), anti-spam legislation (CASL, CAN-SPAM), and export controls.</p>
</div>
</div>

<div class="section">
<h2>Article II — AI Ethics</h2>
<h3>2.1 Responsible AI Usage</h3>
<p>Participants shall not use AI agents or tools within the Ecosystem to:</p>
<ul>
<li>Generate harmful, deceptive, or illegal content</li>
<li>Impersonate real individuals without their consent</li>
<li>Create automated systems designed to harass or manipulate others</li>
<li>Circumvent security measures or exploit vulnerabilities</li>
<li>Generate content that violates intellectual property rights</li>
</ul>

<h3>2.2 AI Transparency</h3>
<p>When interacting with external parties using AI tools from the Ecosystem, Participants should disclose the use of AI where context requires (e.g., customer-facing communications, legal documents).</p>

<h3>2.3 Human Oversight</h3>
<p>While the Ecosystem's agents operate autonomously, critical decisions affecting users, finances, or security require human review. The Commander retains override authority at all times.</p>
</div>

<div class="section">
<h2>Article III — Community Standards</h2>
<h3>3.1 Collaboration</h3>
<p>The Ecosystem thrives on collaboration. Participants are encouraged to share knowledge, assist others, and contribute constructively to community discussions.</p>

<h3>3.2 Dispute Resolution</h3>
<p>Disputes between Participants shall first be addressed through the platform's internal support system. If unresolved, matters may be escalated to the Atlas Legal Director for mediation. Formal arbitration may be pursued per the Terms of Service.</p>

<h3>3.3 Reporting Violations</h3>
<p>Any Participant who observes a violation of these principles is encouraged to report it through the support system or directly to Alfred. All reports are treated confidentially. Retaliation against reporters is prohibited.</p>
</div>

<div class="section">
<h2>Article IV — Account & Access</h2>
<h3>4.1 Account Security</h3>
<p>Participants are responsible for the security of their accounts. Two-factor authentication is recommended. The self-lock feature (voice/email unlock) is available for enhanced protection.</p>

<h3>4.2 Termination</h3>
<p>Accounts that violate these principles may be suspended or terminated following review. Participants may close their accounts at any time per the Terms of Service.</p>

<h3>4.3 Data Portability</h3>
<p>Participants may request export of their data at any time. Requests will be fulfilled within 30 days per applicable privacy legislation.</p>
</div>

<div class="section">
<h2>Article V — Amendments</h2>
<p>These principles may be updated with 30 days' notice. Continued use of the Ecosystem after amendments constitutes acceptance. Major changes require re-acknowledgment.</p>
</div>

<div class="section">
<h2>Article VI — Governing Framework</h2>
<p>These principles supplement (and do not replace) the <a href="/terms-of-service.php" style="color:var(--accent);">Terms of Service</a> and <a href="/privacy-policy.php" style="color:var(--accent);">Privacy Policy</a>. In case of conflict, the Terms of Service shall prevail.</p>
<p style="margin-top:8px;">Jurisdiction: Province of Quebec, Canada. Arbitration per the Civil Code of Quebec.</p>
</div>

<!-- Acceptance -->
<?php if ($accepted): ?>
<div class="accept-box done">
<div class="accepted-msg">✓ You accepted these principles on <?= htmlspecialchars(date('F j, Y', strtotime($accepted['accepted_at']))) ?></div>
<p style="color:var(--dim);margin-top:8px;">Thank you for being a responsible member of the ecosystem.</p>
</div>
<?php elseif (isset($_GET['accepted'])): ?>
<div class="accept-box done">
<div class="accepted-msg">✓ Principles Accepted — Welcome to the Ecosystem</div>
<p style="color:var(--dim);margin-top:8px;">Your acceptance has been recorded. Thank you.</p>
</div>
<?php else: ?>
<div class="accept-box">
<p style="margin-bottom:16px;">By clicking below, you acknowledge that you have read and agree to abide by these Ecosystem Principles.</p>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="hidden" name="accept" value="1">
<button type="submit" class="accept-btn">I Accept These Principles</button>
</form>
</div>
<?php endif; ?>

</div>
</body>
</html>
