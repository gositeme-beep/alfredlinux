<?php
/**
 * NEW MEMBER ONBOARDING MANUAL
 * ════════════════════════════
 * GoSiteMe Ecosystem — Member Operations Guide
 * For all new team members, clients, and ecosystem participants
 * Version: 1.0 — March 2026
 */
require_once __DIR__ . '/../includes/auth-gate.inc.php';
$pageTitle = "GoSiteMe Ecosystem Guide";
$clientId = $_SESSION['client_id'] ?? 0;
$clientName = $_SESSION['firstname'] ?? 'Member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ecosystem Guide — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--bg:#0f0f1a;--surface:#171728;--border:#252542;--text:#e2e8f0;--dim:#8b8fa3;--primary:#8b5cf6;--accent:#22d3ee;--green:#34d399;--gold:#f5c542;--warn:#f97316;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:'Inter',-apple-system,sans-serif;line-height:1.7;}
.guide{max-width:860px;margin:0 auto;padding:20px;}
.hero{text-align:center;padding:50px 20px 30px;background:linear-gradient(135deg,rgba(139,92,246,.12),rgba(34,211,238,.08));border-radius:16px;border:1px solid var(--border);margin-bottom:30px;}
.hero h1{font-size:1.8rem;background:linear-gradient(135deg,var(--primary),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.hero p{color:var(--dim);margin-top:10px;max-width:560px;margin-left:auto;margin-right:auto;}
.hero .welcome{font-size:1.1rem;color:var(--accent);margin-bottom:8px;}
.section{margin:28px 0;padding:24px;background:var(--surface);border:1px solid var(--border);border-radius:12px;}
.section h2{color:var(--primary);font-size:1.2rem;padding-bottom:10px;border-bottom:1px solid var(--border);margin-bottom:14px;}
.section h3{color:var(--accent);margin:16px 0 6px;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px;margin:12px 0;}
.card{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:16px;}
.card h4{color:var(--gold);margin-bottom:6px;font-size:.95rem;}
.card p{font-size:.85rem;color:var(--dim);}
.step{display:flex;gap:14px;margin:12px 0;align-items:flex-start;}
.step-num{width:32px;height:32px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;}
.step-text h4{margin-bottom:2px;}
.step-text p{font-size:.85rem;color:var(--dim);}
.tip{background:rgba(34,211,238,.06);border-left:3px solid var(--accent);padding:12px 16px;border-radius:0 8px 8px 0;margin:10px 0;font-size:.9rem;}
.warn{background:rgba(249,115,22,.06);border-left:3px solid var(--warn);padding:12px 16px;border-radius:0 8px 8px 0;margin:10px 0;font-size:.9rem;}
.cmd{background:#1a1a2e;border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-family:monospace;font-size:.85rem;color:var(--green);margin:8px 0;}
table{width:100%;border-collapse:collapse;margin:10px 0;}
th{background:#1a1a2e;color:var(--gold);text-align:left;padding:8px 12px;font-size:.8rem;}
td{padding:8px 12px;border-bottom:1px solid var(--border);font-size:.85rem;}
.principle{display:flex;gap:12px;padding:14px;background:rgba(139,92,246,.05);border:1px solid rgba(139,92,246,.15);border-radius:10px;margin:8px 0;}
.principle i{color:var(--primary);font-size:1.2rem;margin-top:2px;}
.back-btn{position:fixed;top:20px;left:20px;background:var(--surface);border:1px solid var(--border);color:var(--text);padding:8px 16px;border-radius:8px;cursor:pointer;text-decoration:none;z-index:100;}
.back-btn:hover{border-color:var(--primary);color:var(--primary);}
@media(max-width:600px){.guide{padding:10px;}.section{padding:16px;}.hero h1{font-size:1.3rem;}}
</style>
</head>
<body>
<a href="/dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>

<div class="guide">
<div class="hero">
<div class="welcome">Welcome, <?= htmlspecialchars($clientName) ?></div>
<h1>GoSiteMe Ecosystem Guide</h1>
<p>Everything you need to know about working with Alfred, the AI agent fleet, and the tools at your disposal.</p>
</div>

<!-- What is GoSiteMe -->
<div class="section">
<h2><i class="fas fa-rocket" style="margin-right:8px;"></i>What is GoSiteMe?</h2>
<p>GoSiteMe is a next-generation web hosting and AI services platform. Every account comes with Alfred — a personal AI assistant backed by 101 specialized agents that can build websites, analyze data, write content, handle support, and automate your business operations.</p>
<p style="margin-top:10px;">Think of it like having an entire tech company at your fingertips, available 24/7 across 8 communication channels.</p>
</div>

<!-- Getting Started -->
<div class="section">
<h2><i class="fas fa-play-circle" style="margin-right:8px;"></i>Getting Started</h2>
<div class="step"><div class="step-num">1</div><div class="step-text"><h4>Dashboard</h4><p>Your dashboard at <strong>/dashboard.php</strong> is mission control. View your services, billing, support tickets, and access all tools.</p></div></div>
<div class="step"><div class="step-num">2</div><div class="step-text"><h4>Talk to Alfred</h4><p>Click the chat icon on any page to talk to Alfred. Ask anything: "build me a website", "check my hosting status", "explain my invoice".</p></div></div>
<div class="step"><div class="step-num">3</div><div class="step-text"><h4>Voice Commands</h4><p>Visit <strong>/voice.php</strong> to speak directly to Alfred. Tap to record or enable Live Mode for hands-free conversation.</p></div></div>
<div class="step"><div class="step-num">4</div><div class="step-text"><h4>Phone Support</h4><p>Call <strong>1-833-GOSITEME</strong> (1-833-467-4836) anytime. Alfred answers and can look up your account, submit tickets, or answer questions.</p></div></div>
</div>

<!-- Key Features -->
<div class="section">
<h2><i class="fas fa-star" style="margin-right:8px;"></i>Key Features Available to You</h2>
<div class="grid">
<div class="card"><h4><i class="fas fa-robot"></i> AI Assistant</h4><p>Alfred can answer questions, write code, create content, manage your account, and much more — in English and French.</p></div>
<div class="card"><h4><i class="fas fa-code"></i> GoCodeMe IDE</h4><p>A browser-based code editor with AI assistance. Edit your website files, preview changes, and deploy — no software needed.</p></div>
<div class="card"><h4><i class="fas fa-microphone"></i> Voice Control</h4><p>Speak commands instead of typing. 24 AI voices available. Works even when voice server is under maintenance.</p></div>
<div class="card"><h4><i class="fas fa-lock"></i> Account Lock</h4><p>Lock your account with a voice passphrase. Only your voice can unlock it. Available at your security settings.</p></div>
<div class="card"><h4><i class="fas fa-chart-line"></i> Analytics</h4><p>Real-time visitor tracking, performance metrics, and AI-powered insights about your website traffic.</p></div>
<div class="card"><h4><i class="fas fa-headset"></i> Multi-Channel Support</h4><p>Reach us via web chat, SMS, Telegram, Discord, phone, or email. Alfred responds on all channels.</p></div>
</div>
</div>

<!-- Talking to Alfred -->
<div class="section">
<h2><i class="fas fa-comments" style="margin-right:8px;"></i>How to Talk to Alfred</h2>
<p>Alfred understands natural language — just speak or type normally. Here are some things you can say:</p>
<table>
<tr><th>Category</th><th>Example</th></tr>
<tr><td>Account</td><td>"What's my account status?" / "Show my invoices"</td></tr>
<tr><td>Hosting</td><td>"Is my website up?" / "How much storage am I using?"</td></tr>
<tr><td>Billing</td><td>"When is my next payment?" / "Apply promo code ABC123"</td></tr>
<tr><td>Support</td><td>"I can't access my email" / "My website is slow"</td></tr>
<tr><td>Building</td><td>"Help me build a landing page" / "Add a contact form to my site"</td></tr>
<tr><td>Content</td><td>"Write a blog post about AI in business" / "Translate this to French"</td></tr>
</table>
<div class="tip">Alfred supports both English and French. You can switch languages anytime by saying "Parle en français" or "Switch to English".</div>
</div>

<!-- Communication Channels -->
<div class="section">
<h2><i class="fas fa-satellite-dish" style="margin-right:8px;"></i>Communication Channels</h2>
<div class="grid">
<div class="card"><h4>💬 Web Chat</h4><p>Click the chat bubble on any page. Instant AI responses, file sharing, code help.</p></div>
<div class="card"><h4>📱 SMS</h4><p>Text +1-807-798-2850. Get support, check status, ask questions via text message.</p></div>
<div class="card"><h4>✈️ Telegram</h4><p>Find us at @GoSiteMeBot. Full AI assistant in Telegram.</p></div>
<div class="card"><h4>🎮 Discord</h4><p>Join the GoSiteMe Discord community for real-time support and features.</p></div>
<div class="card"><h4>📞 Phone</h4><p>Call 1-833-GOSITEME. AI-powered voice support with call recording and transcripts.</p></div>
<div class="card"><h4>🎤 Voice</h4><p>Visit /voice.php for browser-based voice conversation with 24 AI voices.</p></div>
</div>
</div>

<!-- Security Features -->
<div class="section">
<h2><i class="fas fa-shield-alt" style="margin-right:8px;"></i>Your Security</h2>
<h3>Two-Factor Authentication (2FA)</h3>
<p>Enable 2FA in your security settings for an extra layer of protection. Uses standard authenticator apps (Google Authenticator, Authy, etc.).</p>

<h3>Account Lock</h3>
<p>You can self-lock your account anytime from your security settings. Once locked:</p>
<ul style="padding-left:20px;margin:8px 0;">
<li>All sessions are terminated immediately</li>
<li>No one can log in — not even with your password</li>
<li>Unlock via your voice passphrase or a code sent to your email</li>
<li>Maximum 5 unlock attempts before permanent lockout</li>
</ul>

<h3>Support PIN</h3>
<p>Your support PIN verifies your identity when contacting us. Never share it publicly.</p>

<div class="warn"><strong>Never share your password, 2FA codes, or support PIN</strong> with anyone. GoSiteMe staff will never ask for these.</div>
</div>

<!-- Ecosystem Principles -->
<div class="section">
<h2><i class="fas fa-balance-scale" style="margin-right:8px;"></i>Ecosystem Principles</h2>
<p>By participating in the GoSiteMe ecosystem, all members agree to uphold:</p>

<div class="principle"><i class="fas fa-handshake"></i><div><strong>Respect & Integrity</strong><br>Treat all members, AI agents, and systems with respect. No abuse, harassment, or exploitation.</div></div>
<div class="principle"><i class="fas fa-lock"></i><div><strong>Security First</strong><br>Protect your credentials. Report vulnerabilities responsibly. Never attempt unauthorized access.</div></div>
<div class="principle"><i class="fas fa-balance-scale"></i><div><strong>Fair Use</strong><br>Use AI resources responsibly. Don't generate harmful, illegal, or deceptive content.</div></div>
<div class="principle"><i class="fas fa-users"></i><div><strong>Community Building</strong><br>Share knowledge, help other members, and contribute to the ecosystem's growth.</div></div>
<div class="principle"><i class="fas fa-eye"></i><div><strong>Transparency</strong><br>Be honest in your interactions. Disclose AI-generated content where required.</div></div>
<div class="principle"><i class="fas fa-gavel"></i><div><strong>Legal Compliance</strong><br>Follow all applicable laws. Respect intellectual property. Honor service agreements.</div></div>
</div>

<!-- FAQ -->
<div class="section">
<h2><i class="fas fa-question-circle" style="margin-right:8px;"></i>Frequently Asked Questions</h2>
<h3>Is my data safe?</h3>
<p>Yes. We use SSL encryption, secure servers, and follow industry best practices. Your data is never sold or shared with third parties.</p>

<h3>What happens when the AI is down?</h3>
<p>It shouldn't be — we have 6 AI providers in a cascade failback chain, plus a local AI server as last resort. If one provider fails, the next automatically takes over.</p>

<h3>Can I use Alfred for my business?</h3>
<p>Absolutely. Alfred can help with websites, content, customer support, analytics, and more. Enterprise plans available for larger workloads.</p>

<h3>How do I get help?</h3>
<p>Chat with Alfred (he's always there), call 1-833-GOSITEME, text +1-807-798-2850, or submit a support ticket in your dashboard.</p>

<h3>What languages are supported?</h3>
<p>English and French are fully supported. Just speak or type in either language.</p>
</div>

<!-- Quick Links -->
<div class="section">
<h2><i class="fas fa-link" style="margin-right:8px;"></i>Quick Links</h2>
<div class="grid">
<div class="card"><h4>📊 Dashboard</h4><p><a href="/dashboard.php" style="color:var(--accent);">/dashboard.php</a></p></div>
<div class="card"><h4>🎤 Voice</h4><p><a href="/voice.php" style="color:var(--accent);">/voice.php</a></p></div>
<div class="card"><h4>💻 IDE</h4><p><a href="/gocodeme.php" style="color:var(--accent);">/gocodeme.php</a></p></div>
<div class="card"><h4>📞 Phone</h4><p>1-833-GOSITEME</p></div>
<div class="card"><h4>📄 Terms</h4><p><a href="/terms-of-service.php" style="color:var(--accent);">/terms-of-service.php</a></p></div>
<div class="card"><h4>🔒 Privacy</h4><p><a href="/privacy-policy.php" style="color:var(--accent);">/privacy-policy.php</a></p></div>
</div>
</div>

</div>
</body>
</html>
