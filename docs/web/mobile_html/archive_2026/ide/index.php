<?php
/**
 * Alfred IDE — AI-Powered Development Environment
 * Landing page for the Alfred IDE product (replaces GoCodeMe)
 * Actual IDE access at: /alfred-ide/
 */

// Pull live stats
$dbHost = '127.0.0.1';
$dbSocket = '/run/mysql/mysql.sock';
$dbUser = 'gositeme_whmcs';
$stats = ['clients' => 22, 'products' => 101, 'agents' => '11.3M+'];
try {
    $mycnf = parse_ini_file('/home/gositeme/.my.cnf');
    $dbPass = $mycnf['password'] ?? '';
    $pdo = new PDO("mysql:unix_socket=$dbSocket;dbname=gositeme_whmcs", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stats['clients'] = $pdo->query("SELECT COUNT(*) FROM tblclients WHERE status='Active'")->fetchColumn() ?: 22;
    $agentCount = $pdo->query("SELECT COUNT(*) FROM gositeme_whmcs.tblcustomfields")->fetchColumn() ?: 0;
} catch (Exception $e) { /* use defaults */ }

$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alfred IDE — AI-Powered Development Environment | GoSiteMe</title>
<meta name="description" content="Alfred IDE: VS Code in the browser with Claude AI, 25+ AI agents, voice commands, and full hosting. The world's first AI-native development environment.">
<meta property="og:title" content="Alfred IDE — AI-Powered Development Environment">
<meta property="og:description" content="VS Code in the browser with Claude AI, voice commands, and 25+ AI agents. Build faster than ever.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://gositeme.com/ide/">
<link rel="icon" href="/favicon.ico">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--gold:#e2b340;--blue:#3b82f6;--dark:#0a0e17;--darker:#060a12;--card:#0d1117;--border:#1a1f2e;--text:#c9d1d9;--dim:#6a737d;--green:#238636;--red:#f85149;--purple:#8b5cf6}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--dark);color:var(--text);line-height:1.6;overflow-x:hidden}
a{color:var(--gold);text-decoration:none;transition:opacity .2s}
a:hover{opacity:.85}

/* Header */
.top-bar{background:var(--darker);border-bottom:1px solid var(--border);padding:10px 0;font-size:13px;text-align:center;color:var(--dim)}
.top-bar strong{color:var(--gold)}
nav{display:flex;justify-content:space-between;align-items:center;max-width:1200px;margin:0 auto;padding:16px 24px}
.logo{font-size:22px;font-weight:800;color:#fff;letter-spacing:-0.5px}
.logo span{color:var(--gold)}
.nav-links{display:flex;gap:24px;align-items:center}
.nav-links a{color:var(--text);font-size:14px;font-weight:500}
.nav-cta{background:var(--gold);color:var(--dark);padding:8px 20px;border-radius:8px;font-weight:700;font-size:13px}

/* Hero */
.hero{text-align:center;padding:80px 24px 60px;max-width:900px;margin:0 auto}
.hero-badge{display:inline-block;background:rgba(226,179,64,0.1);border:1px solid rgba(226,179,64,0.3);color:var(--gold);padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:24px}
.hero h1{font-size:clamp(36px,5vw,56px);font-weight:800;color:#fff;line-height:1.1;margin-bottom:20px}
.hero h1 span{background:linear-gradient(135deg,var(--gold),#f59e0b);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:18px;color:var(--dim);max-width:650px;margin:0 auto 32px;line-height:1.7}
.hero-btns{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}
.btn-primary{background:var(--gold);color:var(--dark);padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px;display:inline-flex;align-items:center;gap:8px;transition:transform .2s,box-shadow .2s}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(226,179,64,0.3);opacity:1}
.btn-secondary{background:transparent;border:2px solid var(--border);color:#fff;padding:12px 28px;border-radius:10px;font-weight:600;font-size:15px;transition:border-color .2s}
.btn-secondary:hover{border-color:var(--gold);opacity:1}

/* Live Demo Terminal */
.demo-terminal{max-width:700px;margin:48px auto 0;background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;text-align:left;box-shadow:0 20px 60px rgba(0,0,0,0.4)}
.demo-bar{background:var(--darker);padding:10px 16px;display:flex;align-items:center;gap:8px}
.demo-dot{width:12px;height:12px;border-radius:50%}
.demo-dot.r{background:var(--red)}.demo-dot.y{background:var(--gold)}.demo-dot.g{background:var(--green)}
.demo-bar span{color:var(--dim);font-size:12px;margin-left:auto}
.demo-body{padding:20px;font-family:'Fira Code','Cascadia Code',monospace;font-size:13px;min-height:180px}
.demo-line{margin-bottom:6px;opacity:0;animation:fadeIn .5s forwards}
.demo-line .prompt{color:var(--green)}
.demo-line .cmd{color:#fff}
.demo-line .output{color:var(--blue)}
.demo-line .agent{color:var(--gold)}
@keyframes fadeIn{to{opacity:1}}

/* Stats Strip */
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:24px;max-width:1000px;margin:60px auto;padding:0 24px}
.stat{text-align:center;padding:24px;background:var(--card);border:1px solid var(--border);border-radius:12px}
.stat .num{font-size:32px;font-weight:800;color:var(--gold)}
.stat .label{font-size:13px;color:var(--dim);margin-top:4px}

/* Features */
.section{max-width:1200px;margin:0 auto;padding:80px 24px}
.section-title{text-align:center;margin-bottom:48px}
.section-title h2{font-size:clamp(28px,3.5vw,40px);font-weight:800;color:#fff;margin-bottom:12px}
.section-title p{color:var(--dim);font-size:16px;max-width:600px;margin:0 auto}
.section-label{color:var(--gold);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px}

.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
.feature-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:28px;transition:border-color .3s,transform .3s}
.feature-card:hover{border-color:var(--gold);transform:translateY(-4px)}
.feature-icon{font-size:32px;margin-bottom:12px}
.feature-card h3{color:#fff;font-size:17px;margin-bottom:8px}
.feature-card p{color:var(--dim);font-size:13.5px;line-height:1.6}

/* Voice Section */
.voice-section{background:linear-gradient(180deg,var(--darker) 0%,var(--dark) 100%);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.voice-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center}
.voice-visual{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:32px;text-align:center}
.voice-wave{display:flex;align-items:center;justify-content:center;gap:4px;height:60px;margin-bottom:20px}
.voice-wave .bar{width:4px;background:var(--gold);border-radius:2px;animation:wave 1.2s ease-in-out infinite}
.voice-wave .bar:nth-child(2){animation-delay:.1s}.voice-wave .bar:nth-child(3){animation-delay:.2s}
.voice-wave .bar:nth-child(4){animation-delay:.3s}.voice-wave .bar:nth-child(5){animation-delay:.4s}
.voice-wave .bar:nth-child(6){animation-delay:.3s}.voice-wave .bar:nth-child(7){animation-delay:.2s}
@keyframes wave{0%,100%{height:12px}50%{height:50px}}
.voice-transcript{font-family:'Fira Code',monospace;font-size:14px;color:#fff;margin-top:12px}
.voice-info h3{font-size:28px;color:#fff;margin-bottom:16px}
.voice-info p{color:var(--dim);font-size:15px;margin-bottom:16px;line-height:1.7}
.voice-cmds{list-style:none;display:grid;gap:8px}
.voice-cmds li{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text)}
.voice-cmds li::before{content:'▸';color:var(--gold);font-weight:bold}

/* Comparison */
.comparison{overflow-x:auto}
.compare-table{width:100%;border-collapse:collapse;min-width:600px}
.compare-table th,.compare-table td{padding:12px 16px;text-align:center;border-bottom:1px solid var(--border);font-size:13px}
.compare-table th{color:var(--dim);font-weight:600;text-transform:uppercase;font-size:11px;letter-spacing:1px}
.compare-table th:first-child,.compare-table td:first-child{text-align:left;color:#fff;font-weight:600}
.compare-table .highlight{background:rgba(226,179,64,0.05)}
.compare-table .highlight th{color:var(--gold)}
.check{color:var(--green);font-weight:bold}.cross{color:var(--red)}

/* Pricing */
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px}
.price-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:28px;text-align:center;transition:border-color .3s,transform .3s;position:relative}
.price-card:hover{border-color:var(--gold);transform:translateY(-4px)}
.price-card.popular{border-color:var(--gold);box-shadow:0 0 40px rgba(226,179,64,0.1)}
.price-card.popular::before{content:'MOST POPULAR';position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--gold);color:var(--dark);font-size:10px;font-weight:800;padding:4px 14px;border-radius:6px;letter-spacing:1px}
.price-card h3{color:#fff;font-size:20px;margin-bottom:4px}
.price-card .tier-desc{color:var(--dim);font-size:12px;margin-bottom:16px}
.price-card .price{font-size:36px;font-weight:800;color:var(--gold);margin-bottom:4px}
.price-card .price span{font-size:14px;color:var(--dim);font-weight:400}
.price-card .tokens{color:var(--blue);font-size:13px;font-weight:600;margin-bottom:16px}
.price-card ul{list-style:none;text-align:left;margin-bottom:20px}
.price-card li{font-size:12.5px;color:var(--text);padding:5px 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.price-card li::before{content:'✓ ';color:var(--green)}
.price-card .buy-btn{display:block;background:var(--gold);color:var(--dark);padding:12px;border-radius:8px;font-weight:700;font-size:14px;transition:transform .2s}
.price-card .buy-btn:hover{transform:scale(1.03);opacity:1}
.price-card.free .buy-btn{background:transparent;border:2px solid var(--gold);color:var(--gold)}

/* Security */
.security-badges{display:flex;gap:24px;justify-content:center;flex-wrap:wrap;margin-top:32px}
.badge{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px 24px;text-align:center;min-width:140px}
.badge .icon{font-size:28px;margin-bottom:8px}
.badge .label{color:#fff;font-size:13px;font-weight:600}
.badge .desc{color:var(--dim);font-size:11px;margin-top:2px}

/* CTA */
.cta-section{text-align:center;padding:80px 24px;background:linear-gradient(180deg,var(--dark),var(--darker))}
.cta-section h2{font-size:clamp(28px,3.5vw,42px);font-weight:800;color:#fff;margin-bottom:16px}
.cta-section p{color:var(--dim);font-size:16px;max-width:550px;margin:0 auto 32px}

/* Footer */
footer{background:var(--darker);border-top:1px solid var(--border);padding:40px 24px;text-align:center}
.footer-links{display:flex;gap:24px;justify-content:center;margin-bottom:16px;flex-wrap:wrap}
.footer-links a{color:var(--dim);font-size:13px}
.footer-copy{color:var(--dim);font-size:12px}

@media(max-width:768px){
  .voice-grid{grid-template-columns:1fr}
  .nav-links{gap:12px}
  .hero{padding:40px 16px 30px}
  .stats{grid-template-columns:repeat(2,1fr);gap:12px}
  .features-grid{grid-template-columns:1fr}
  .pricing-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
  <strong>Alfred IDE</strong> — The world's first AI-native development environment. Now in your browser.
</div>

<!-- Nav -->
<nav>
  <div class="logo">Alfred <span>IDE</span></div>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#voice">Voice</a>
    <a href="#pricing">Pricing</a>
    <a href="/gohostme/">Hosting</a>
    <a href="/alfred-ide/" class="nav-cta">Launch IDE →</a>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-badge">✦ Powered by Claude Sonnet 4 + 25 AI Agents</div>
  <h1>Code With Your <span>Voice</span>.<br>Build With <span>AI</span>.</h1>
  <p>Alfred IDE is VS Code in your browser — with Claude AI, 25+ specialized agents, voice commands, and full hosting built in. No install. No limits. Just build.</p>
  <div class="hero-btns">
    <a href="/alfred-ide/" class="btn-primary">🚀 Launch Alfred IDE</a>
    <a href="#pricing" class="btn-secondary">View Plans</a>
  </div>

  <!-- Demo Terminal -->
  <div class="demo-terminal">
    <div class="demo-bar">
      <div class="demo-dot r"></div><div class="demo-dot y"></div><div class="demo-dot g"></div>
      <span>Alfred IDE — Commander Session</span>
    </div>
    <div class="demo-body" id="demoBody"></div>
  </div>
</section>

<!-- Stats -->
<div class="stats">
  <div class="stat"><div class="num">25+</div><div class="label">AI Agents</div></div>
  <div class="stat"><div class="num">34+</div><div class="label">AI Models</div></div>
  <div class="stat"><div class="num">18</div><div class="label">Voice Commands</div></div>
  <div class="stat"><div class="num">940+</div><div class="label">Dev Tools</div></div>
  <div class="stat"><div class="num"><?= number_format($stats['clients']) ?>+</div><div class="label">Active Users</div></div>
</div>

<!-- Features -->
<section class="section" id="features">
  <div class="section-title">
    <div class="section-label">Features</div>
    <h2>Everything You Need. Nothing You Don't.</h2>
    <p>A complete development environment that thinks, speaks, and builds alongside you.</p>
  </div>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon">🧠</div>
      <h3>Claude Sonnet 4 — Built In</h3>
      <p>Not a toy model. Full Claude Sonnet 4 with code generation, multi-file refactoring, debugging, and context-aware suggestions. Plus 34+ models including GPT-4.1, Gemini, and local Ollama.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🎙️</div>
      <h3>Voice-Controlled Development</h3>
      <p>Talk to Alfred. Say "open terminal," "save file," "run tests," or ask complex coding questions — all by voice. The mic is always one keystroke away (Ctrl+Shift+A).</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">👥</div>
      <h3>25+ Specialized AI Agents</h3>
      <p>Alfred for general coding. Cipher for security audits. Architect for system design. Oracle for analytics. Scout for research. Pick the right expert for every task.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🌐</div>
      <h3>Browser-Native — Zero Install</h3>
      <p>Full VS Code experience in your browser. Extensions, terminal, Git, debugging — everything works. Access from any device, anywhere. Your workspace is always waiting.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🖥️</div>
      <h3>Full Hosting Included</h3>
      <p>Deploy directly from the IDE. Domains, SSL, databases, email — all managed from one place. Powered by enterprise infrastructure with 99.9% uptime guarantee.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🔒</div>
      <h3>Enterprise Security</h3>
      <p>Isolated workspaces per user. No shared filesystems. Post-quantum encryption ready (Veil Protocol). Your code stays yours — always.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🔧</div>
      <h3>940+ Integrated Tools</h3>
      <p>PHP, Python, Node.js, Go, Rust — every runtime pre-configured. Docker, Redis, databases, package managers, linters, formatters. Everything just works.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">📱</div>
      <h3>Mobile Responsive</h3>
      <p>Code from your tablet or phone. The editor adapts to any screen. Combined with voice commands, you can build from anywhere — literally.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">⚡</div>
      <h3>Instant Collaboration</h3>
      <p>Share workspaces, review code together, and pair program in real-time. Built for teams from 1 to 100+ developers working across time zones.</p>
    </div>
  </div>
</section>

<!-- Voice Section -->
<section class="section voice-section" id="voice">
  <div class="section-title">
    <div class="section-label">Voice AI</div>
    <h2>Your IDE Listens. And Understands.</h2>
    <p>The first IDE where you can code, navigate, and deploy — all by voice.</p>
  </div>
  <div class="voice-grid">
    <div class="voice-visual">
      <div class="voice-wave">
        <div class="bar" style="height:20px"></div>
        <div class="bar" style="height:35px"></div>
        <div class="bar" style="height:50px"></div>
        <div class="bar" style="height:40px"></div>
        <div class="bar" style="height:25px"></div>
        <div class="bar" style="height:45px"></div>
        <div class="bar" style="height:30px"></div>
      </div>
      <div class="voice-transcript">"Deploy the app to production"</div>
      <div style="color:var(--dim);font-size:12px;margin-top:8px">Alfred processes voice → executes action → confirms</div>
    </div>
    <div class="voice-info">
      <h3>18 Voice Commands + Unlimited AI</h3>
      <p>Built-in commands for every IDE action — plus the ability to ask any coding question, request code generation, or have Alfred explain complex logic line by line.</p>
      <p>Voice recognition works in real-time with interim results showing as you speak. When you're done, Alfred acts instantly.</p>
      <ul class="voice-cmds">
        <li>"Open terminal" — instant terminal access</li>
        <li>"Save file" / "Save all" — quick saves</li>
        <li>"Format this" — auto-format current document</li>
        <li>"Search for useState" — workspace-wide search</li>
        <li>"Run npm test" — execute any terminal command</li>
        <li>"Insert function hello" — type code by voice</li>
        <li>"Switch to Cipher" — change AI agent on the fly</li>
      </ul>
    </div>
  </div>
</section>

<!-- Comparison -->
<section class="section">
  <div class="section-title">
    <div class="section-label">Comparison</div>
    <h2>Alfred IDE vs. The Rest</h2>
  </div>
  <div class="comparison">
    <table class="compare-table">
      <thead>
        <tr>
          <th>Feature</th>
          <th class="highlight">Alfred IDE</th>
          <th>VS Code</th>
          <th>GitHub Codespaces</th>
          <th>Replit</th>
          <th>Cursor</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>Browser-based</td><td class="highlight check">✓</td><td class="cross">✗</td><td class="check">✓</td><td class="check">✓</td><td class="cross">✗</td></tr>
        <tr><td>AI Built-in</td><td class="highlight check">✓ 25+ agents</td><td class="cross">✗ Extension</td><td class="check">✓ Copilot</td><td class="check">✓ Basic</td><td class="check">✓ Single</td></tr>
        <tr><td>Voice Commands</td><td class="highlight check">✓ 18 commands</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
        <tr><td>Multiple AI Models</td><td class="highlight check">✓ 34+ models</td><td class="cross">✗</td><td class="cross">✗ GPT only</td><td class="cross">✗ Limited</td><td class="check">✓ Some</td></tr>
        <tr><td>Hosting Included</td><td class="highlight check">✓ Full stack</td><td class="cross">✗</td><td class="cross">✗ Container</td><td class="check">✓ Basic</td><td class="cross">✗</td></tr>
        <tr><td>Domain + SSL</td><td class="highlight check">✓ Free</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
        <tr><td>Email Hosting</td><td class="highlight check">✓</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
        <tr><td>Database Management</td><td class="highlight check">✓ MySQL/Redis</td><td class="cross">✗ Manual</td><td class="cross">✗</td><td class="check">✓ Basic</td><td class="cross">✗</td></tr>
        <tr><td>Extension Support</td><td class="highlight check">✓ Open VSX</td><td class="check">✓ Full</td><td class="check">✓ Full</td><td class="cross">✗</td><td class="check">✓ Full</td></tr>
        <tr><td>Workspace Isolation</td><td class="highlight check">✓ Per-user</td><td class="check">✓ Local</td><td class="check">✓ Container</td><td class="cross">✗ Shared</td><td class="check">✓ Local</td></tr>
        <tr><td>Free Tier</td><td class="highlight check">✓ 50K tokens</td><td class="check">✓</td><td class="cross">60hr/mo</td><td class="check">✓ Limited</td><td class="cross">✗</td></tr>
        <tr><td>Starting Price</td><td class="highlight" style="color:var(--gold);font-weight:700">$0/mo</td><td>Free</td><td>$13/mo</td><td>$25/mo</td><td>$20/mo</td></tr>
      </tbody>
    </table>
  </div>
</section>

<!-- Pricing -->
<section class="section" id="pricing">
  <div class="section-title">
    <div class="section-label">Pricing</div>
    <h2>Start Free. Scale As You Grow.</h2>
    <p>Every plan includes the full IDE, voice commands, and AI agents. The difference is power.</p>
  </div>
  <div class="pricing-grid">

    <div class="price-card free">
      <h3>Free</h3>
      <div class="tier-desc">Try everything, no credit card</div>
      <div class="price">$0<span>/mo</span></div>
      <div class="tokens">50,000 AI tokens/month</div>
      <ul>
        <li>Full Alfred IDE (VS Code)</li>
        <li>All 25+ AI agents</li>
        <li>Voice commands</li>
        <li>1 Website</li>
        <li>5GB Storage</li>
        <li>Community support</li>
      </ul>
      <a href="https://gositeme.com/cart.php?a=add&pid=39" class="buy-btn">Start Free →</a>
    </div>

    <div class="price-card">
      <h3>Builder</h3>
      <div class="tier-desc">Personal projects & learning</div>
      <div class="price">$15<span>/mo</span></div>
      <div class="tokens">300,000 AI tokens/month</div>
      <ul>
        <li>Everything in Free</li>
        <li>Alfred AI code generation</li>
        <li>180+ dev tools & 9 AI engines</li>
        <li>Domain management + free SSL</li>
        <li>One-click deployment</li>
        <li>Usage dashboard</li>
      </ul>
      <a href="https://gositeme.com/cart.php?a=add&pid=18" class="buy-btn">Get Builder →</a>
    </div>

    <div class="price-card">
      <h3>Creator</h3>
      <div class="tier-desc">Creators & small businesses</div>
      <div class="price">$22<span>/mo</span></div>
      <div class="tokens">450,000 AI tokens/month</div>
      <ul>
        <li>Everything in Builder</li>
        <li>3 Websites</li>
        <li>30GB NVMe Storage</li>
        <li>5 Email Accounts</li>
        <li>Priority email support</li>
      </ul>
      <a href="https://gositeme.com/cart.php?a=add&pid=32" class="buy-btn">Get Creator →</a>
    </div>

    <div class="price-card popular">
      <h3>Professional</h3>
      <div class="tier-desc">Freelancers & pros shipping real projects</div>
      <div class="price">$29<span>/mo</span></div>
      <div class="tokens">600,000 AI tokens/month</div>
      <ul>
        <li>Everything in Creator</li>
        <li>Priority AI processing</li>
        <li>Git workflows (branches, PRs)</li>
        <li>Database tools (MySQL, Redis)</li>
        <li>Staging environments</li>
        <li>SSH/SFTP access</li>
      </ul>
      <a href="https://gositeme.com/cart.php?a=add&pid=19" class="buy-btn">Get Professional →</a>
    </div>

    <div class="price-card">
      <h3>Studio</h3>
      <div class="tier-desc">Dev studios & startups at scale</div>
      <div class="price">$59<span>/mo</span></div>
      <div class="tokens">1,500,000 AI tokens/month</div>
      <ul>
        <li>Everything in Professional</li>
        <li>Premium model access</li>
        <li>3 concurrent AI sessions</li>
        <li>Docker orchestration</li>
        <li>5 team collaborators</li>
        <li>Webhook integrations</li>
      </ul>
      <a href="https://gositeme.com/cart.php?a=add&pid=20" class="buy-btn">Get Studio →</a>
    </div>

    <div class="price-card">
      <h3>Business</h3>
      <div class="tier-desc">Agencies & enterprise teams</div>
      <div class="price">$99<span>/mo</span></div>
      <div class="tokens">3,000,000 AI tokens/month</div>
      <ul>
        <li>Everything in Studio</li>
        <li>Unlimited premium models</li>
        <li>10 concurrent AI sessions</li>
        <li>25 collaborators</li>
        <li>SSO/SAML authentication</li>
        <li>99.9% SLA uptime</li>
        <li>Dedicated account manager</li>
      </ul>
      <a href="https://gositeme.com/cart.php?a=add&pid=21" class="buy-btn">Get Business →</a>
    </div>

    <div class="price-card">
      <h3>Enterprise</h3>
      <div class="tier-desc">Custom solutions for large orgs</div>
      <div class="price">Custom</div>
      <div class="tokens">Unlimited AI tokens</div>
      <ul>
        <li>Everything in Business</li>
        <li>Dedicated infrastructure</li>
        <li>On-premises deployment</li>
        <li>Unlimited collaborators</li>
        <li>HIPAA/SOC2 compliance</li>
        <li>24/7 premium support</li>
        <li>Custom SLA & invoicing</li>
      </ul>
      <a href="https://gositeme.com/cart.php?a=add&pid=22" class="buy-btn">Contact Sales →</a>
    </div>

  </div>
</section>

<!-- Security -->
<section class="section">
  <div class="section-title">
    <div class="section-label">Security</div>
    <h2>Built for Trust. Engineered for Safety.</h2>
  </div>
  <div class="security-badges">
    <div class="badge"><div class="icon">🔐</div><div class="label">Isolated Workspaces</div><div class="desc">Per-user containers</div></div>
    <div class="badge"><div class="icon">🛡️</div><div class="label">E2E Encryption</div><div class="desc">Veil Protocol ready</div></div>
    <div class="badge"><div class="icon">🔑</div><div class="label">Zero-Knowledge</div><div class="desc">Your code, your keys</div></div>
    <div class="badge"><div class="icon">🌐</div><div class="label">Free SSL</div><div class="desc">Every domain</div></div>
    <div class="badge"><div class="icon">📋</div><div class="label">SOC2 Ready</div><div class="desc">Enterprise compliance</div></div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <h2>Stop Configuring. Start Building.</h2>
  <p>Alfred IDE is ready. Your workspace is waiting. One click and you're coding — with the most powerful AI assistant ever built into an editor.</p>
  <div class="hero-btns">
    <a href="/alfred-ide/" class="btn-primary">🚀 Launch Alfred IDE Now</a>
    <a href="https://gositeme.com/cart.php?a=add&pid=39" class="btn-secondary">Start Free — No Card Required</a>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="footer-links">
    <a href="/">GoSiteMe</a>
    <a href="/gohostme/">GoHostMe</a>
    <a href="/alfred-ide/">Launch IDE</a>
    <a href="/alfred-voice-live/">Alfred Voice</a>
    <a href="https://meta-dome.com">MetaDome</a>
  </div>
  <div class="footer-copy">© <?= $year ?> GoSiteMe — Built by Commander Danny William Perez. All rights reserved.</div>
</footer>

<!-- Demo Terminal Animation -->
<script>
const lines = [
  {type:'prompt',text:'commander@alfred-ide:~$ ',cmd:'alfred "create a REST API for user management"'},
  {type:'output',text:'🧠 Alfred: Generating Express.js REST API with CRUD endpoints...'},
  {type:'output',text:'   ✓ Created routes/users.js (GET, POST, PUT, DELETE)'},
  {type:'output',text:'   ✓ Created models/User.js (Mongoose schema)'},
  {type:'output',text:'   ✓ Added JWT authentication middleware'},
  {type:'output',text:'   ✓ Generated 12 test cases'},
  {type:'agent',text:'🎙️ Alfred: "API created, Commander. 4 files generated with full auth. Shall I deploy?"'},
  {type:'prompt',text:'commander@alfred-ide:~$ ',cmd:'voice: "deploy to production"'},
  {type:'output',text:'🚀 Deploying to gositeme.com... SSL configured... Done in 4.2s'},
];
const body=document.getElementById('demoBody');
let i=0;
function addLine(){
  if(i>=lines.length)return;
  const l=lines[i];
  const div=document.createElement('div');
  div.className='demo-line';
  div.style.animationDelay=(i*0.6)+'s';
  if(l.type==='prompt'){
    div.innerHTML='<span class="prompt">'+l.text+'</span><span class="cmd">'+l.cmd+'</span>';
  }else if(l.type==='agent'){
    div.innerHTML='<span class="agent">'+l.text+'</span>';
  }else{
    div.innerHTML='<span class="output">'+l.text+'</span>';
  }
  body.appendChild(div);
  i++;
  setTimeout(addLine,600);
}
setTimeout(addLine,800);
</script>

</body>
</html>
