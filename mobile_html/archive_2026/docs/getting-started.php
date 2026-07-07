<?php
$page_title = 'Getting Started — Alfred AI Documentation | GoSiteMe';
$page_description = 'Get up and running with Alfred AI in under 5 minutes. Create an account, make your first API call, try voice, build an agent, and deploy a fleet.';
$page_canonical = 'https://gositeme.com/docs/getting-started';
$page_og_title = 'Getting Started with Alfred AI — 5-Minute Setup Guide';
$page_og_description = 'Step-by-step guide to get started with Alfred AI. From account creation to deploying your first AI fleet in minutes.';
include __DIR__ . '/../includes/lang.php';
include __DIR__ . '/../includes/site-header.inc.php';
?>

<!-- Schema.org HowTo markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "Getting Started with Alfred AI",
  "description": "A step-by-step guide to set up and start using Alfred AI — from account creation to deploying your first AI fleet.",
  "totalTime": "PT5M",
  "estimatedCost": {
    "@type": "MonetaryAmount",
    "currency": "USD",
    "value": "0"
  },
  "tool": {
    "@type": "HowToTool",
    "name": "Web browser or terminal with cURL"
  },
  "step": [
    {
      "@type": "HowToStep",
      "position": 1,
      "name": "Create an Account",
      "text": "Sign up at gositeme.com/alfred.php or use the POST /api/auth.php register endpoint.",
      "url": "https://gositeme.com/docs/getting-started#step-1"
    },
    {
      "@type": "HowToStep",
      "position": 2,
      "name": "Choose a Plan",
      "text": "Pick Starter ($3.99/mo), Professional ($9.99/mo), or Enterprise ($24.99/mo).",
      "url": "https://gositeme.com/docs/getting-started#step-2"
    },
    {
      "@type": "HowToStep",
      "position": 3,
      "name": "Make Your First API Call",
      "text": "Authenticate and execute a tool via the Tools API endpoint.",
      "url": "https://gositeme.com/docs/getting-started#step-3"
    },
    {
      "@type": "HowToStep",
      "position": 4,
      "name": "Try Voice",
      "text": "Call 1-833-GOSITEME or embed the voice widget to interact with Alfred by voice.",
      "url": "https://gositeme.com/docs/getting-started#step-4"
    },
    {
      "@type": "HowToStep",
      "position": 5,
      "name": "Build an Agent",
      "text": "Create a specialized AI agent with custom tool access and personality.",
      "url": "https://gositeme.com/docs/getting-started#step-5"
    },
    {
      "@type": "HowToStep",
      "position": 6,
      "name": "Deploy a Fleet",
      "text": "Deploy multiple agents as a fleet for parallel task execution.",
      "url": "https://gositeme.com/docs/getting-started#step-6"
    }
  ],
  "author": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "url": "https://gositeme.com"
  },
  "datePublished": "2026-03-04",
  "dateModified": "2026-03-04"
}
</script>

<style>
:root {
    --doc-bg: #0a0a14;
    --doc-surface: #12121e;
    --doc-surface-2: #1a1a2e;
    --doc-surface-3: #22223a;
    --doc-border: rgba(255,255,255,0.08);
    --doc-accent: #6c5ce7;
    --doc-accent-light: #a29bfe;
    --doc-blue: #0984e3;
    --doc-green: #00b894;
    --doc-orange: #fdcb6e;
    --doc-fire: #e17055;
    --doc-pink: #fd79a8;
    --doc-cyan: #00cec9;
    --doc-text: #e8e8f0;
    --doc-text-muted: #8a8a9a;
    --doc-radius: 12px;
    --doc-sidebar-w: 280px;
}

/* Breadcrumbs */
.doc-breadcrumbs {
    max-width: 1400px;
    margin: 0 auto;
    padding: 100px 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
}
.doc-breadcrumbs a {
    color: var(--doc-text-muted);
    text-decoration: none;
    transition: color 0.2s;
}
.doc-breadcrumbs a:hover { color: var(--doc-accent-light); }
.doc-breadcrumbs span { color: var(--doc-text-muted); }
.doc-breadcrumbs .current { color: var(--doc-text); font-weight: 600; }

/* Layout */
.doc-layout {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px 80px;
    gap: 40px;
}

/* Sidebar */
.doc-sidebar {
    width: var(--doc-sidebar-w);
    flex-shrink: 0;
    position: sticky;
    top: 100px;
    height: fit-content;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}
.doc-sidebar::-webkit-scrollbar { width: 4px; }
.doc-sidebar::-webkit-scrollbar-thumb { background: var(--doc-accent); border-radius: 4px; }
.doc-sidebar-nav { list-style: none; padding: 0; margin: 0; }
.doc-sidebar-nav li { margin-bottom: 2px; }
.doc-sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    color: var(--doc-text-muted);
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}
.doc-sidebar-nav a:hover { background: var(--doc-surface-2); color: var(--doc-text); }
.doc-sidebar-nav a.active { background: var(--doc-surface-2); color: var(--doc-accent-light); font-weight: 600; border-left: 3px solid var(--doc-accent); }
.doc-sidebar-nav i { width: 20px; text-align: center; font-size: 0.9rem; }

/* Mobile sidebar toggle */
.doc-sidebar-toggle {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--doc-accent);
    color: #fff;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(108,92,231,0.4);
}

/* Main content */
.doc-main { flex: 1; min-width: 0; }
.doc-main h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.4rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.doc-main .doc-subtitle {
    color: var(--doc-text-muted);
    font-size: 1.1rem;
    margin-bottom: 32px;
    line-height: 1.6;
}
.doc-main h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 48px 0 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--doc-border);
}
.doc-main h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--doc-accent-light);
    margin: 28px 0 10px;
}
.doc-main h4 {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--doc-text);
    margin: 20px 0 8px;
}
.doc-main p, .doc-main li {
    color: var(--doc-text-muted);
    line-height: 1.7;
    font-size: 0.95rem;
}
.doc-main ul, .doc-main ol { padding-left: 20px; margin: 12px 0; }
.doc-main a { color: var(--doc-accent-light); text-decoration: none; }
.doc-main a:hover { text-decoration: underline; }

/* Code blocks */
.doc-code-wrap {
    position: relative;
    margin: 16px 0;
    border-radius: var(--doc-radius);
    overflow: hidden;
    border: 1px solid var(--doc-border);
}
.doc-code-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    background: var(--doc-surface-3);
    border-bottom: 1px solid var(--doc-border);
}
.doc-code-lang {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--doc-accent-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.doc-copy-btn {
    background: none;
    border: 1px solid var(--doc-border);
    color: var(--doc-text-muted);
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}
.doc-copy-btn:hover { color: var(--doc-text); border-color: var(--doc-accent); }
.doc-copy-btn.copied { color: var(--doc-green); border-color: var(--doc-green); }
.doc-code-block {
    background: var(--doc-surface);
    padding: 20px;
    overflow-x: auto;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    color: var(--doc-text);
    margin: 0;
}
.doc-code-block code { background: none; padding: 0; }

/* Info boxes */
.doc-info {
    padding: 16px 20px;
    border-radius: var(--doc-radius);
    margin: 16px 0;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.doc-info i { margin-top: 3px; font-size: 1.1rem; flex-shrink: 0; }
.doc-info.tip { background: rgba(0,184,148,0.08); border: 1px solid rgba(0,184,148,0.2); }
.doc-info.tip i { color: var(--doc-green); }
.doc-info.warn { background: rgba(253,203,110,0.08); border: 1px solid rgba(253,203,110,0.2); }
.doc-info.warn i { color: var(--doc-orange); }
.doc-info.note { background: rgba(108,92,231,0.08); border: 1px solid rgba(108,92,231,0.2); }
.doc-info.note i { color: var(--doc-accent-light); }

/* Step cards */
.doc-step {
    background: var(--doc-surface);
    border: 1px solid var(--doc-border);
    border-radius: var(--doc-radius);
    padding: 24px;
    margin: 20px 0;
    position: relative;
    padding-left: 80px;
}
.doc-step-num {
    position: absolute;
    left: 20px;
    top: 24px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--doc-accent), var(--doc-blue));
    color: #fff;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.doc-step h3 {
    margin-top: 0 !important;
    color: #fff !important;
    font-size: 1.15rem;
}
.doc-step p { margin: 8px 0; }

/* Endpoint badge */
.doc-endpoint {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0;
    padding: 8px 16px;
    background: var(--doc-surface);
    border: 1px solid var(--doc-border);
    border-radius: 8px;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 0.85rem;
}
.doc-method {
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
}
.doc-method.get { background: rgba(0,184,148,0.15); color: var(--doc-green); }
.doc-method.post { background: rgba(108,92,231,0.15); color: var(--doc-accent-light); }

/* Timeline */
.doc-timeline {
    position: relative;
    padding-left: 30px;
    margin: 24px 0;
}
.doc-timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--doc-accent), var(--doc-blue), var(--doc-green));
    border-radius: 2px;
}
.doc-timeline-item {
    position: relative;
    padding-bottom: 24px;
}
.doc-timeline-item::before {
    content: '';
    position: absolute;
    left: -26px;
    top: 6px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--doc-accent);
    border: 2px solid var(--doc-bg);
}
.doc-timeline-item:last-child { padding-bottom: 0; }

/* Params table */
.doc-params {
    width: 100%;
    border-collapse: collapse;
    margin: 12px 0;
}
.doc-params th {
    text-align: left;
    padding: 10px 14px;
    background: var(--doc-surface-2);
    color: var(--doc-text);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--doc-border);
}
.doc-params td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--doc-border);
    font-size: 0.85rem;
    color: var(--doc-text-muted);
}
.doc-params code {
    background: var(--doc-surface-2);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: var(--doc-cyan);
}

/* Quick links grid */
.doc-quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
    margin: 24px 0;
}
.doc-quick-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--doc-surface);
    border: 1px solid var(--doc-border);
    border-radius: var(--doc-radius);
    color: var(--doc-text);
    text-decoration: none !important;
    transition: all 0.2s;
}
.doc-quick-link:hover {
    border-color: var(--doc-accent);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(108,92,231,0.15);
}
.doc-quick-link i {
    font-size: 1.3rem;
    color: var(--doc-accent-light);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(108,92,231,0.1);
    border-radius: 8px;
    flex-shrink: 0;
}
.doc-quick-link span { font-weight: 600; font-size: 0.9rem; }

/* Responsive */
@media (max-width: 900px) {
    .doc-sidebar {
        position: fixed;
        top: 0; left: -300px;
        width: 280px;
        height: 100vh;
        background: var(--doc-bg);
        z-index: 999;
        padding: 80px 16px 20px;
        transition: left 0.3s ease;
        border-right: 1px solid var(--doc-border);
    }
    .doc-sidebar.open { left: 0; }
    .doc-sidebar-toggle { display: flex; align-items: center; justify-content: center; }
    .doc-layout { padding: 20px 16px 60px; }
    .doc-breadcrumbs { padding-top: 80px; }
    .doc-step { padding-left: 24px; padding-top: 60px; }
    .doc-step-num { top: 16px; left: 20px; }
}
</style>

<!-- Breadcrumbs -->
<div class="doc-breadcrumbs">
    <a href="/docs/">Docs</a>
    <span>›</span>
    <span class="current">Getting Started</span>
</div>

<!-- Layout -->
<div class="doc-layout">
    <!-- Sidebar -->
    <nav class="doc-sidebar" id="docSidebar">
        <ul class="doc-sidebar-nav">
            <li><a href="/docs/"><i class="fas fa-home"></i> Docs Home</a></li>
            <li><a href="/docs/getting-started" class="active"><i class="fas fa-rocket"></i> Getting Started</a></li>
            <li><a href="/docs/api-reference"><i class="fas fa-plug"></i> API Reference</a></li>
            <li><a href="/docs/voice-integration"><i class="fas fa-microphone"></i> Voice Integration</a></li>
            <li><a href="/docs/tools-guide"><i class="fas fa-wrench"></i> Tools Guide</a></li>
        </ul>
    </nav>

    <!-- Mobile Toggle -->
    <button class="doc-sidebar-toggle" id="docSidebarToggle" aria-label="Toggle documentation menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="doc-main">
        <h1><i class="fas fa-rocket"></i> Getting Started with Alfred AI</h1>
        <p class="doc-subtitle">Go from zero to a fully deployed AI fleet in under 5 minutes. This guide walks you through account creation, your first API call, voice setup, and fleet deployment.</p>

        <div class="doc-info tip">
            <i class="fas fa-lightbulb"></i>
            <div><strong>Free Trial:</strong> Alfred AI includes a 14-day free trial with full access to all 1,220+ tools. No credit card required to start.</div>
        </div>

        <!-- Quick overview timeline -->
        <h2 id="overview">Setup Overview</h2>
        <div class="doc-timeline">
            <div class="doc-timeline-item">
                <strong style="color: var(--doc-text);">1. Create Account</strong> — Sign up via web or API <em style="color: var(--doc-text-muted);">(30 seconds)</em>
            </div>
            <div class="doc-timeline-item">
                <strong style="color: var(--doc-text);">2. Choose Plan</strong> — Starter, Professional, or Enterprise <em style="color: var(--doc-text-muted);">(1 minute)</em>
            </div>
            <div class="doc-timeline-item">
                <strong style="color: var(--doc-text);">3. First API Call</strong> — Execute a tool with one HTTP request <em style="color: var(--doc-text-muted);">(1 minute)</em>
            </div>
            <div class="doc-timeline-item">
                <strong style="color: var(--doc-text);">4. Try Voice</strong> — Talk to Alfred by phone or embed the widget <em style="color: var(--doc-text-muted);">(1 minute)</em>
            </div>
            <div class="doc-timeline-item">
                <strong style="color: var(--doc-text);">5. Build Agent</strong> — Create a specialized AI agent <em style="color: var(--doc-text-muted);">(1 minute)</em>
            </div>
            <div class="doc-timeline-item">
                <strong style="color: var(--doc-text);">6. Deploy Fleet</strong> — Launch multiple agents in parallel <em style="color: var(--doc-text-muted);">(30 seconds)</em>
            </div>
        </div>

        <!-- ==================== STEP 1 ==================== -->
        <h2 id="step-1">Step 1: Create an Account</h2>
        <div class="doc-step">
            <div class="doc-step-num">1</div>
            <h3>Sign Up via the Web</h3>
            <p>Visit <a href="/alfred.php">gositeme.com/alfred.php</a> and click <strong>Get Started</strong>. Enter your name, email, and a password with at least 8 characters.</p>
        </div>

        <h3>Or Register via API</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/auth.php</div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>curl -X POST https://gositeme.com/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "register",
    "email": "you@example.com",
    "password": "your-secure-password",
    "name": "Your Name"
  }'</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const response = await fetch('https://gositeme.com/api/auth.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'register',
    email: 'you@example.com',
    password: 'your-secure-password',
    name: 'Your Name'
  })
});

const data = await response.json();
console.log(data.token); // "sess_abc123..."</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

resp = requests.post('https://gositeme.com/api/auth.php', json={
    'action': 'register',
    'email': 'you@example.com',
    'password': 'your-secure-password',
    'name': 'Your Name'
})

data = resp.json()
print(data['token'])  # "sess_abc123..."</code></pre>
        </div>

        <h4>Response</h4>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JSON</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>{
  "success": true,
  "token": "sess_abc123def456...",
  "user": {
    "id": 42,
    "name": "Your Name",
    "email": "you@example.com",
    "plan": "trial"
  }
}</code></pre>
        </div>

        <div class="doc-info note">
            <i class="fas fa-info-circle"></i>
            <div><strong>Note:</strong> Save your session token — you'll include it as a <code>Bearer</code> token in the <code>Authorization</code> header for all subsequent API calls.</div>
        </div>

        <!-- ==================== STEP 2 ==================== -->
        <h2 id="step-2">Step 2: Choose a Plan</h2>
        <div class="doc-step">
            <div class="doc-step-num">2</div>
            <h3>Available Plans</h3>
            <p>Alfred offers three plans, each with full access to all 1,220+ tools:</p>
        </div>

        <table class="doc-params">
            <thead>
                <tr><th>Plan</th><th>Price</th><th>API Calls</th><th>Fleets</th><th>Rate Limit</th></tr>
            </thead>
            <tbody>
                <tr><td><strong style="color: var(--doc-green)">Starter</strong></td><td>$3.99/mo</td><td>50/day</td><td>1</td><td>100 req/min</td></tr>
                <tr><td><strong style="color: var(--doc-accent-light)">Professional</strong></td><td>$9.99/mo</td><td>Unlimited</td><td>10</td><td>1,000 req/min</td></tr>
                <tr><td><strong style="color: var(--doc-orange)">Enterprise</strong></td><td>$24.99/mo</td><td>Unlimited</td><td>Unlimited</td><td>10,000 req/min</td></tr>
            </tbody>
        </table>

        <p>Visit the <a href="/pricing.php">pricing page</a> for full details, or query plans via the API:</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>curl https://gositeme.com/api/stripe.php?action=plans</code></pre>
        </div>

        <p>Subscribe to a plan:</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const checkout = await fetch('https://gositeme.com/api/stripe.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'create_checkout',
    plan: 'professional'
  })
}).then(r => r.json());

// Redirect user to Stripe checkout
window.location.href = checkout.checkout_url;</code></pre>
        </div>

        <!-- ==================== STEP 3 ==================== -->
        <h2 id="step-3">Step 3: Make Your First API Call</h2>
        <div class="doc-step">
            <div class="doc-step-num">3</div>
            <h3>Execute a Tool</h3>
            <p>With your token in hand, you can call any of the 1,220+ tools. Let's start with a simple <code>summarize_text</code> call:</p>
        </div>

        <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/tools.php?action=execute</div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const response = await fetch('https://gositeme.com/api/tools.php?action=execute', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    tool_name: 'summarize_text',
    args: {
      text: 'Alfred AI is a powerful platform with 1,220+ tools...',
      max_length: 100
    }
  })
});

const data = await response.json();
console.log(data.result);
// "Alfred AI provides 1,220+ tools for automation, voice, and fleet management."</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

response = requests.post(
    'https://gositeme.com/api/tools.php?action=execute',
    headers={'Authorization': 'Bearer sess_abc123...'},
    json={
        'tool_name': 'summarize_text',
        'args': {
            'text': 'Alfred AI is a powerful platform with 1,220+ tools...',
            'max_length': 100
        }
    }
)

print(response.json()['result'])</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">PHP</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>$ch = curl_init('https://gositeme.com/api/tools.php?action=execute');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer sess_abc123...'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'tool_name' => 'summarize_text',
        'args' => [
            'text' => 'Alfred AI is a powerful platform with 1,220+ tools...',
            'max_length' => 100
        ]
    ]),
    CURLOPT_RETURNTRANSFER => true
]);

$result = json_decode(curl_exec($ch), true);
curl_close($ch);
echo $result['result'];</code></pre>
        </div>

        <div class="doc-info tip">
            <i class="fas fa-lightbulb"></i>
            <div><strong>Quick Test:</strong> Browse available tools at <a href="/alfred-tools.php">gositeme.com/alfred-tools.php</a> before calling them programmatically.</div>
        </div>

        <!-- ==================== STEP 4 ==================== -->
        <h2 id="step-4">Step 4: Try Voice</h2>
        <div class="doc-step">
            <div class="doc-step-num">4</div>
            <h3>Talk to Alfred</h3>
            <p>Alfred supports voice interaction via phone, web widget, and VAPI webhooks. Try it now:</p>
        </div>

        <h3>Option A: Call by Phone</h3>
        <p>Dial <strong>1-833-GOSITEME</strong> (1-833-467-4836) and talk to Alfred directly. Say things like:</p>
        <ul>
            <li>"Summarize this article for me" — then dictate or provide a URL</li>
            <li>"Look up DNS records for example.com"</li>
            <li>"Draft a cease and desist letter"</li>
            <li>"What's the weather in Montreal?"</li>
        </ul>

        <h3>Option B: Web Voice Widget</h3>
        <p>Visit <a href="/voice.php">gositeme.com/voice.php</a> to try the browser-based voice interface. Or embed it in your own site:</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">HTML</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>&lt;!-- Alfred Voice Widget --&gt;
&lt;script src="https://gositeme.com/assets/js/alfred-voice-widget.js"&gt;&lt;/script&gt;
&lt;script&gt;
  AlfredVoice.init({
    token: 'sess_abc123...',
    position: 'bottom-right',
    theme: 'dark',
    greeting: 'Hi! How can I help you today?'
  });
&lt;/script&gt;</code></pre>
        </div>

        <p>For full voice integration details, see the <a href="/docs/voice-integration">Voice Integration Guide</a>.</p>

        <!-- ==================== STEP 5 ==================== -->
        <h2 id="step-5">Step 5: Build an Agent</h2>
        <div class="doc-step">
            <div class="doc-step-num">5</div>
            <h3>Create a Specialized Agent</h3>
            <p>Agents are AI instances with specific tool access, personality, and knowledge base. Create one that handles customer support:</p>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const agent = await fetch('https://gositeme.com/api/fleet.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'create_agent',
    name: 'Support Bot',
    personality: 'Friendly and helpful customer support agent',
    tools: ['search_knowledge_base', 'create_ticket', 'summarize_text'],
    knowledge_base: 'kb_support_docs',
    voice_enabled: true,
    voice_engine: 'kokoro'
  })
}).then(r => r.json());

console.log(agent);
// { id: "agent_xyz", name: "Support Bot", status: "ready", tools: 3, voice: true }</code></pre>
        </div>

        <h3>Agent Configuration Options</h3>
        <table class="doc-params">
            <thead><tr><th>Field</th><th>Type</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>name</code></td><td>string</td><td>Display name for the agent</td></tr>
                <tr><td><code>personality</code></td><td>string</td><td>System prompt / personality description</td></tr>
                <tr><td><code>tools</code></td><td>array</td><td>List of tool names the agent can use</td></tr>
                <tr><td><code>knowledge_base</code></td><td>string</td><td>Knowledge base ID to attach</td></tr>
                <tr><td><code>voice_enabled</code></td><td>boolean</td><td>Enable voice interaction</td></tr>
                <tr><td><code>voice_engine</code></td><td>string</td><td>TTS engine: kokoro, orpheus, cartesia, elevenlabs</td></tr>
                <tr><td><code>max_tokens</code></td><td>integer</td><td>Max response length (default: 4096)</td></tr>
            </tbody>
        </table>

        <!-- ==================== STEP 6 ==================== -->
        <h2 id="step-6">Step 6: Deploy a Fleet</h2>
        <div class="doc-step">
            <div class="doc-step-num">6</div>
            <h3>Launch Multiple Agents</h3>
            <p>Fleets let you deploy multiple agents working in parallel on related tasks. Perfect for customer support, content creation, or research.</p>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const fleet = await fetch('https://gositeme.com/api/fleet.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'create_fleet',
    name: 'Customer Support Fleet',
    description: 'Handles inbound support tickets and live chat',
    agents: ['agent_xyz', 'agent_abc'],
    max_agents: 5,
    auto_scale: true
  })
}).then(r => r.json());

console.log(fleet);
// { id: "fleet_123", name: "Customer Support Fleet", status: "active", agents: 2 }</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

fleet = requests.post(
    'https://gositeme.com/api/fleet.php',
    headers={'Authorization': 'Bearer sess_abc123...'},
    json={
        'action': 'create_fleet',
        'name': 'Research Fleet',
        'description': 'Parallel research and analysis agents',
        'agents': ['agent_researcher', 'agent_analyst'],
        'max_agents': 10,
        'auto_scale': True
    }
).json()

print(f"Fleet {fleet['id']} created with {fleet['agents']} agents")</code></pre>
        </div>

        <p>Monitor your fleet from the <a href="/fleet-dashboard.php">Fleet Dashboard</a> or via the API:</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>curl -H "Authorization: Bearer sess_abc123..." \
  "https://gositeme.com/api/fleet.php?action=status&fleet_id=fleet_123"

# {
#   "id": "fleet_123",
#   "name": "Customer Support Fleet",
#   "status": "active",
#   "agents": 2,
#   "tasks_completed": 147,
#   "avg_response_time": "1.2s",
#   "uptime": "99.97%"
# }</code></pre>
        </div>

        <!-- ==================== NEXT STEPS ==================== -->
        <h2 id="next-steps">What's Next?</h2>
        <p>You're all set! Here are the best places to go from here:</p>

        <div class="doc-quick-links">
            <a href="/docs/api-reference" class="doc-quick-link">
                <i class="fas fa-plug"></i>
                <span>Full API Reference</span>
            </a>
            <a href="/docs/tools-guide" class="doc-quick-link">
                <i class="fas fa-wrench"></i>
                <span>1,220+ Tools Guide</span>
            </a>
            <a href="/docs/voice-integration" class="doc-quick-link">
                <i class="fas fa-microphone"></i>
                <span>Voice Integration</span>
            </a>
            <a href="/alfred-tools.php" class="doc-quick-link">
                <i class="fas fa-th"></i>
                <span>Tool Directory</span>
            </a>
            <a href="/pricing.php" class="doc-quick-link">
                <i class="fas fa-tags"></i>
                <span>View Pricing</span>
            </a>
            <a href="/voice.php" class="doc-quick-link">
                <i class="fas fa-phone-alt"></i>
                <span>Voice Demo</span>
            </a>
        </div>

        <div class="doc-info note">
            <i class="fas fa-headset"></i>
            <div><strong>Need Help?</strong> Chat with Alfred directly at <a href="/alfred.php">gositeme.com/alfred.php</a>, call <strong>1-833-GOSITEME</strong>, or email <strong>support@gositeme.com</strong>.</div>
        </div>

    </main>
</div>

<script>
// Copy to clipboard
function copyCode(btn) {
    const codeBlock = btn.closest('.doc-code-wrap').querySelector('.doc-code-block code');
    navigator.clipboard.writeText(codeBlock.textContent).then(() => {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fas fa-copy"></i> Copy';
        }, 2000);
    });
}

// Mobile sidebar toggle
const sidebarToggle = document.getElementById('docSidebarToggle');
const sidebar = document.getElementById('docSidebar');
if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        sidebarToggle.innerHTML = sidebar.classList.contains('open')
            ? '<i class="fas fa-times"></i>'
            : '<i class="fas fa-bars"></i>';
    });
}
</script>

<?php include __DIR__ . '/../includes/site-footer.inc.php'; ?>
