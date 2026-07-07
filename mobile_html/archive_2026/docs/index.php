<?php
$page_title = 'Documentation — Alfred AI by GoSiteMe | API Reference & Guides';
$page_description = 'Complete Alfred AI documentation: API reference, voice integration, fleet management, tools guide, SDKs, and getting started tutorials with code examples.';
$page_canonical = 'https://gositeme.com/docs/';
$page_og_title = 'Alfred AI Documentation — API Reference & Developer Guides';
$page_og_description = 'Explore the full Alfred AI documentation. 1,220+ tools, voice-first API, fleet management, and more. Code examples in JavaScript, Python, PHP, and cURL.';
include __DIR__ . '/../includes/site-header.inc.php';
?>

<!-- Schema.org TechArticle markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "Alfred AI Documentation",
  "description": "Complete documentation for Alfred AI platform — API reference, voice integration, fleet management, tools guide, and SDKs.",
  "author": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "url": "https://gositeme.com"
  },
  "publisher": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "logo": {
      "@type": "ImageObject",
      "url": "https://gositeme.com/brand/logo_w.png"
    }
  },
  "datePublished": "2026-03-04",
  "dateModified": "2026-03-04",
  "mainEntityOfPage": "https://gositeme.com/docs/",
  "about": {
    "@type": "SoftwareApplication",
    "name": "Alfred AI",
    "applicationCategory": "AI Assistant",
    "operatingSystem": "Web, Voice",
    "offers": {
      "@type": "Offer",
      "price": "3.99",
      "priceCurrency": "USD"
    }
  }
}
</script>

<style>
/* ===== Docs Page Styles ===== */
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

/* Hero */
.doc-hero {
    padding: 120px 20px 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 50% 0%, #1a1033 0%, var(--doc-bg) 70%);
}
.doc-hero::before {
    content: '';
    position: absolute;
    top: -40%; left: -20%;
    width: 140%; height: 180%;
    background:
        radial-gradient(circle at 30% 25%, rgba(108,92,231,0.14) 0%, transparent 50%),
        radial-gradient(circle at 70% 65%, rgba(9,132,227,0.1) 0%, transparent 50%);
    pointer-events: none;
}
.doc-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
    position: relative;
}
.doc-hero p {
    color: var(--doc-text-muted);
    font-size: 1.1rem;
    margin-bottom: 30px;
    position: relative;
}
.doc-search-wrap {
    max-width: 560px;
    margin: 0 auto;
    position: relative;
}
.doc-search-wrap input {
    width: 100%;
    padding: 16px 20px 16px 50px;
    border-radius: 50px;
    border: 1px solid var(--doc-border);
    background: var(--doc-surface);
    color: var(--doc-text);
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
}
.doc-search-wrap input:focus {
    border-color: var(--doc-accent);
}
.doc-search-wrap i {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--doc-text-muted);
    font-size: 1.1rem;
}

/* Layout */
.doc-layout {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px 80px;
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

.doc-nav-group { margin-bottom: 8px; }
.doc-nav-group-title {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    color: var(--doc-text);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    border-radius: 8px;
    transition: background 0.2s;
    user-select: none;
}
.doc-nav-group-title:hover { background: var(--doc-surface-2); }
.doc-nav-group-title i.fa-chevron-right {
    margin-left: auto;
    font-size: 0.7rem;
    transition: transform 0.2s;
}
.doc-nav-group.active .doc-nav-group-title i.fa-chevron-right { transform: rotate(90deg); }
.doc-nav-group-title .nav-icon { width: 18px; text-align: center; color: var(--doc-accent-light); }

.doc-nav-items {
    display: none;
    padding-left: 42px;
}
.doc-nav-group.active .doc-nav-items { display: block; }
.doc-nav-item {
    display: block;
    padding: 7px 14px;
    color: var(--doc-text-muted);
    font-size: 0.85rem;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s;
    cursor: pointer;
}
.doc-nav-item:hover { color: var(--doc-text); background: var(--doc-surface); }
.doc-nav-item.active { color: var(--doc-accent-light); background: var(--doc-surface-2); }

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
.doc-main {
    flex: 1;
    min-width: 0;
}
.doc-section {
    display: none;
    animation: docFadeIn 0.3s ease;
}
.doc-section.active { display: block; }
@keyframes docFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.doc-section h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--doc-border);
}
.doc-section h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--doc-accent-light);
    margin: 32px 0 12px;
}
.doc-section h4 {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--doc-text);
    margin: 24px 0 8px;
}
.doc-section p, .doc-section li {
    color: var(--doc-text-muted);
    line-height: 1.7;
    font-size: 0.95rem;
}
.doc-section ul, .doc-section ol {
    padding-left: 20px;
    margin: 12px 0;
}
.doc-section a { color: var(--doc-accent-light); text-decoration: none; }
.doc-section a:hover { text-decoration: underline; }

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
}
.doc-code-block code { background: none; padding: 0; }

/* Endpoint badges */
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
.doc-method.put { background: rgba(253,203,110,0.15); color: var(--doc-orange); }
.doc-method.delete { background: rgba(225,112,85,0.15); color: var(--doc-fire); }

/* Info boxes */
.doc-info {
    padding: 16px 20px;
    border-radius: var(--doc-radius);
    margin: 16px 0;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.doc-info i { margin-top: 3px; font-size: 1.1rem; }
.doc-info.tip { background: rgba(0,184,148,0.08); border: 1px solid rgba(0,184,148,0.2); }
.doc-info.tip i { color: var(--doc-green); }
.doc-info.warn { background: rgba(253,203,110,0.08); border: 1px solid rgba(253,203,110,0.2); }
.doc-info.warn i { color: var(--doc-orange); }
.doc-info.note { background: rgba(108,92,231,0.08); border: 1px solid rgba(108,92,231,0.2); }
.doc-info.note i { color: var(--doc-accent-light); }

/* Tabs for language switching */
.doc-lang-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: -1px;
    position: relative;
    z-index: 1;
}
.doc-lang-tab {
    padding: 6px 16px;
    border-radius: 8px 8px 0 0;
    background: var(--doc-surface);
    color: var(--doc-text-muted);
    border: 1px solid var(--doc-border);
    border-bottom: none;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.2s;
}
.doc-lang-tab.active {
    background: var(--doc-surface-2);
    color: var(--doc-accent-light);
    border-color: var(--doc-accent);
}

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
    .doc-layout { padding: 0 16px 60px; }
}
</style>

<!-- Hero Section -->
<section class="doc-hero">
    <h1><i class="fas fa-book"></i> Alfred Documentation</h1>
    <p>Everything you need to build with Alfred AI — 1,220+ tools at your fingertips</p>
    <div class="doc-search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="docSearch" placeholder="Search documentation..." autocomplete="off">
    </div>
</section>

<!-- Main Layout -->
<div class="doc-layout">
    <!-- Sidebar Navigation -->
    <nav class="doc-sidebar" id="docSidebar">
        <div class="doc-nav-group active" data-group="getting-started">
            <div class="doc-nav-group-title">
                <i class="fas fa-rocket nav-icon"></i> Getting Started
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item active" data-section="quickstart">Quick Start</a>
                <a class="doc-nav-item" data-section="installation">Installation</a>
                <a class="doc-nav-item" data-section="first-steps">First Steps</a>
            </div>
        </div>
        <div class="doc-nav-group" data-group="api-reference">
            <div class="doc-nav-group-title">
                <i class="fas fa-plug nav-icon"></i> API Reference
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item" data-section="api-auth">Authentication</a>
                <a class="doc-nav-item" data-section="api-tools">Tools API</a>
                <a class="doc-nav-item" data-section="api-fleet">Fleet API</a>
                <a class="doc-nav-item" data-section="api-stripe">Stripe API</a>
            </div>
        </div>
        <div class="doc-nav-group" data-group="voice">
            <div class="doc-nav-group-title">
                <i class="fas fa-microphone nav-icon"></i> Voice Integration
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item" data-section="vapi-setup">VAPI Setup</a>
                <a class="doc-nav-item" data-section="voice-commands">Voice Commands</a>
                <a class="doc-nav-item" data-section="phone-agent">Phone Agent</a>
            </div>
        </div>
        <div class="doc-nav-group" data-group="tools-guide">
            <div class="doc-nav-group-title">
                <i class="fas fa-wrench nav-icon"></i> Tools Guide
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item" data-section="tool-categories">Tool Categories</a>
                <a class="doc-nav-item" data-section="using-tools">Using Tools</a>
                <a class="doc-nav-item" data-section="custom-tools">Custom Tools</a>
            </div>
        </div>
        <div class="doc-nav-group" data-group="fleet">
            <div class="doc-nav-group-title">
                <i class="fas fa-users-cog nav-icon"></i> Fleet Management
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item" data-section="creating-fleets">Creating Fleets</a>
                <a class="doc-nav-item" data-section="agent-deployment">Agent Deployment</a>
                <a class="doc-nav-item" data-section="monitoring">Monitoring</a>
            </div>
        </div>
        <div class="doc-nav-group" data-group="conference">
            <div class="doc-nav-group-title">
                <i class="fas fa-video nav-icon"></i> Conference Rooms
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item" data-section="creating-rooms">Creating Rooms</a>
                <a class="doc-nav-item" data-section="adding-agents">Adding Agents</a>
                <a class="doc-nav-item" data-section="recording">Recording</a>
            </div>
        </div>
        <div class="doc-nav-group" data-group="billing">
            <div class="doc-nav-group-title">
                <i class="fas fa-credit-card nav-icon"></i> Billing
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item" data-section="plans">Plans</a>
                <a class="doc-nav-item" data-section="checkout">Checkout</a>
                <a class="doc-nav-item" data-section="portal">Portal</a>
                <a class="doc-nav-item" data-section="webhooks">Webhooks</a>
            </div>
        </div>
        <div class="doc-nav-group" data-group="sdks">
            <div class="doc-nav-group-title">
                <i class="fas fa-code nav-icon"></i> SDKs
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="doc-nav-items">
                <a class="doc-nav-item" data-section="sdk-javascript">JavaScript</a>
                <a class="doc-nav-item" data-section="sdk-python">Python</a>
                <a class="doc-nav-item" data-section="sdk-php">PHP</a>
                <a class="doc-nav-item" data-section="sdk-curl">cURL</a>
            </div>
        </div>
    </nav>

    <!-- Mobile Toggle -->
    <button class="doc-sidebar-toggle" id="docSidebarToggle" aria-label="Toggle documentation menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="doc-main">

        <!-- ==================== GETTING STARTED ==================== -->
        <div class="doc-section active" id="section-quickstart">
            <h2><i class="fas fa-rocket"></i> Quick Start</h2>
            <p>Get up and running with Alfred AI in under 5 minutes. Alfred provides 1,220+ AI tools accessible via REST API, voice commands, and the web dashboard.</p>

            <div class="doc-info tip">
                <i class="fas fa-lightbulb"></i>
                <div><strong>Tip:</strong> You can try Alfred for free with a 14-day trial. No credit card required.</div>
            </div>

            <h3>Step 1: Create an Account</h3>
            <p>Sign up at <a href="https://gositeme.com/alfred.php">gositeme.com/alfred.php</a> or use the API:</p>

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

            <h3>Step 2: Get Your API Key</h3>
            <p>After registration, log in to retrieve your session token:</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>curl -X POST https://gositeme.com/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "login",
    "email": "you@example.com",
    "password": "your-secure-password"
  }'

# Response:
# {
#   "success": true,
#   "token": "sess_abc123...",
#   "user": { "id": 42, "name": "Your Name", "plan": "starter" }
# }</code></pre>
            </div>

            <h3>Step 3: Call Your First Tool</h3>
            <p>Execute any of 1,220+ tools with a single API call:</p>

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
      text: 'Your long article text here...',
      max_length: 200
    }
  })
});

const data = await response.json();
console.log(data.result); // Summarized text</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-installation">
            <h2><i class="fas fa-download"></i> Installation</h2>
            <p>Alfred is primarily a cloud-based API — no installation required. Access it via REST endpoints, the web dashboard, or voice calls.</p>

            <h3>Web Dashboard</h3>
            <p>Visit <a href="https://gositeme.com/alfred.php">gositeme.com/alfred.php</a> to use Alfred directly in your browser. Log in and start chatting immediately.</p>

            <h3>Desktop App (Veil Browser)</h3>
            <p>Download Veil Browser for a native desktop experience with built-in Alfred AI:</p>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Windows</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code># Download and extract the Windows portable zip
# From: https://gositeme.com/downloads/Veil-Browser-3.0.0-win-x64.zip
# Extract and run "Veil Browser.exe"</code></pre>
            </div>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">macOS</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code># Intel Mac:
curl -LO https://gositeme.com/downloads/Veil-Browser-3.0.0-mac-intel.zip
unzip Veil-Browser-3.0.0-mac-intel.zip
open "Veil Browser.app"

# Apple Silicon (M1/M2/M3/M4):
curl -LO https://gositeme.com/downloads/Veil-Browser-3.0.0-mac-arm64.zip
unzip Veil-Browser-3.0.0-mac-arm64.zip
open "Veil Browser.app"</code></pre>
            </div>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Linux (AppImage)</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code># Download Veil Browser AppImage
wget https://gositeme.com/downloads/Veil-Browser-3.0.0.AppImage
chmod +x Veil-Browser-3.0.0.AppImage
./Veil-Browser-3.0.0.AppImage</code></pre>
            </div>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Ubuntu / Debian (.deb)</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code># Download and install .deb package
wget https://gositeme.com/downloads/veil-browser_3.0.0_amd64.deb
sudo dpkg -i veil-browser_3.0.0_amd64.deb
sudo apt-get install -f  # Fix any dependency issues
veil-browser</code></pre>
            </div>

            <h3>GoCodeMe IDE (Web)</h3>
            <p>Open <a href="https://gositeme.com/editor/">gositeme.com/editor/</a> to use the cloud-based GoCodeMe IDE directly in your browser. No download needed.</p>

            <h3>API Integration</h3>
            <p>Integrate Alfred into any application using REST API calls. All endpoints are at <code>https://gositeme.com/api/</code>. See the <a href="#" onclick="showSection('api-auth')">API Reference</a> for full details.</p>

            <h3>Voice Access</h3>
            <p>Call <strong>1-833-GOSITEME</strong> (1-833-467-4836) to interact with Alfred via phone. Voice integration uses VAPI for natural language processing and tool execution.</p>
        </div>

        <div class="doc-section" id="section-first-steps">
            <h2><i class="fas fa-shoe-prints"></i> First Steps</h2>
            <p>Now that you have an account, here's what to explore first:</p>

            <h3>1. Browse Available Tools</h3>
            <p>Alfred has 1,220+ tools organized into 21 categories. Browse them:</p>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>// Get all tool categories
const categories = await fetch('https://gositeme.com/api/tools.php?action=categories')
  .then(r => r.json());

console.log(categories);
// ["legal", "healthcare", "education", "devops", "media", "finance", ...]

// List tools in a category
const legalTools = await fetch('https://gositeme.com/api/tools.php?action=list&category=legal')
  .then(r => r.json());

console.log(legalTools.tools.length); // 43 legal tools</code></pre>
            </div>

            <h3>2. Create Your First Fleet</h3>
            <p>Fleets let you deploy AI agents for specific tasks:</p>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>const fleet = await fetch('https://gositeme.com/api/fleet.php?action=create', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    name: 'Customer Support Fleet',
    description: 'Handles inbound support questions',
    tools: ['summarize_text', 'search_knowledge_base', 'create_ticket'],
    max_agents: 5
  })
}).then(r => r.json());

console.log(fleet); // { id: "fleet_xyz", name: "Customer Support Fleet", status: "active" }</code></pre>
            </div>

            <h3>3. Set Up Voice</h3>
            <p>Configure your VAPI webhook to enable voice commands. See <a href="#" onclick="showSection('vapi-setup')">VAPI Setup</a>.</p>

            <h3>4. Choose a Plan</h3>
            <p>View plans at <a href="/pricing.php">/pricing.php</a> or via the API:</p>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>curl https://gositeme.com/api/stripe.php?action=plans

# Response:
# {
#   "plans": [
#     { "name": "Builder", "price": "$15/mo", "tokens": 300000, "websites": 1 },
#     { "name": "Creator", "price": "$22/mo", "tokens": 450000, "websites": 3 },
#     { "name": "Professional", "price": "$29/mo", "tokens": 600000, "websites": 5 },
#     { "name": "Studio", "price": "$59/mo", "tokens": 1500000, "websites": 10 },
#     { "name": "Business", "price": "$99/mo", "tokens": 3000000, "websites": 25 }
#   ]
# }</code></pre>
            </div>
        </div>

        <!-- ==================== API REFERENCE ==================== -->
        <div class="doc-section" id="section-api-auth">
            <h2><i class="fas fa-lock"></i> Authentication API</h2>
            <p>All API requests require authentication via session token. Obtain a token by logging in.</p>

            <h3>POST /api/auth.php — Login</h3>
            <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/auth.php</div>

            <table class="doc-params">
                <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>action</code></td><td>string</td><td>Yes</td><td>Set to <code>"login"</code></td></tr>
                    <tr><td><code>email</code></td><td>string</td><td>Yes</td><td>User email address</td></tr>
                    <tr><td><code>password</code></td><td>string</td><td>Yes</td><td>User password</td></tr>
                </tbody>
            </table>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>curl -X POST https://gositeme.com/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"action":"login","email":"user@example.com","password":"secret"}'

# 200 OK
# {"success":true,"token":"sess_abc123","user":{"id":1,"name":"John","plan":"professional"}}</code></pre>
            </div>

            <h3>POST /api/auth.php — Register</h3>
            <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/auth.php</div>

            <table class="doc-params">
                <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>action</code></td><td>string</td><td>Yes</td><td>Set to <code>"register"</code></td></tr>
                    <tr><td><code>email</code></td><td>string</td><td>Yes</td><td>Email address</td></tr>
                    <tr><td><code>password</code></td><td>string</td><td>Yes</td><td>Min 8 characters</td></tr>
                    <tr><td><code>name</code></td><td>string</td><td>Yes</td><td>Display name</td></tr>
                </tbody>
            </table>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Python</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>import requests

response = requests.post('https://gositeme.com/api/auth.php', json={
    'action': 'register',
    'email': 'newuser@example.com',
    'password': 'securePassword123',
    'name': 'Jane Doe'
})

data = response.json()
print(data['token'])  # sess_xyz789...</code></pre>
            </div>

            <h3>GET /api/auth.php — Check Session</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/auth.php?action=check</div>
            <p>Verify current session validity. Include token in Authorization header.</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">PHP</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>$ch = curl_init('https://gositeme.com/api/auth.php?action=check');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer sess_abc123'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if ($response['authenticated']) {
    echo "Logged in as: " . $response['user']['name'];
}</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-api-tools">
            <h2><i class="fas fa-wrench"></i> Tools API</h2>
            <p>The Tools API gives you access to all 1,220+ Alfred tools. Browse categories, search tools, and execute them programmatically.</p>

            <h3>GET /api/tools.php — List Categories</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/tools.php?action=categories</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>const res = await fetch('https://gositeme.com/api/tools.php?action=categories');
const data = await res.json();
// { "categories": ["legal","healthcare","education","devops","media",...], "total": 21 }</code></pre>
            </div>

            <h3>GET /api/tools.php — List Tools by Category</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/tools.php?action=list&amp;category=legal</div>

            <table class="doc-params">
                <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>action</code></td><td>string</td><td>Yes</td><td><code>"list"</code></td></tr>
                    <tr><td><code>category</code></td><td>string</td><td>Yes</td><td>Category slug (e.g. "legal", "devops")</td></tr>
                </tbody>
            </table>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Python</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>import requests

response = requests.get('https://gositeme.com/api/tools.php', params={
    'action': 'list',
    'category': 'legal'
})

tools = response.json()['tools']
for tool in tools:
    print(f"{tool['name']}: {tool['description']}")
# draft_motion: Draft a legal motion
# case_research: Research case law
# ...</code></pre>
            </div>

            <h3>GET /api/tools.php — Search Tools</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/tools.php?action=search&amp;q=homework</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>curl "https://gositeme.com/api/tools.php?action=search&q=homework"

# {"results":[
#   {"name":"homework_helper","category":"education","description":"Help solve homework problems step by step"},
#   {"name":"essay_writer","category":"education","description":"Generate essay drafts with citations"},
#   ...
# ],"total":12}</code></pre>
            </div>

            <h3>POST /api/tools.php — Execute Tool</h3>
            <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/tools.php?action=execute</div>

            <table class="doc-params">
                <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>tool_name</code></td><td>string</td><td>Yes</td><td>Tool identifier (e.g. "summarize_text")</td></tr>
                    <tr><td><code>args</code></td><td>object</td><td>Varies</td><td>Tool-specific arguments</td></tr>
                </tbody>
            </table>

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
        'Authorization: Bearer sess_abc123'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'tool_name' => 'dns_lookup',
        'args' => ['domain' => 'example.com', 'type' => 'A']
    ]),
    CURLOPT_RETURNTRANSFER => true
]);

$result = json_decode(curl_exec($ch), true);
curl_close($ch);

print_r($result);
// ["result" => ["records" => [["type"=>"A","value"=>"93.184.216.34","ttl"=>3600]]]]</code></pre>
            </div>

            <div class="doc-info warn">
                <i class="fas fa-exclamation-triangle"></i>
                <div><strong>Rate Limits:</strong> Starter plan: 50 tool calls/day. Professional: unlimited. Enterprise: unlimited + priority queue.</div>
            </div>
        </div>

        <div class="doc-section" id="section-api-fleet">
            <h2><i class="fas fa-users-cog"></i> Fleet API</h2>
            <p>Manage AI agent fleets programmatically. Create, monitor, and scale your fleet deployments.</p>

            <h3>GET /api/fleet.php — List Fleets</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/fleet.php?action=list</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>const fleets = await fetch('https://gositeme.com/api/fleet.php?action=list', {
  headers: { 'Authorization': 'Bearer sess_abc123' }
}).then(r => r.json());

fleets.data.forEach(fleet => {
  console.log(`${fleet.name} — ${fleet.agent_count} agents — ${fleet.status}`);
});
// Customer Support Fleet — 3 agents — active
// Sales Outreach — 2 agents — active</code></pre>
            </div>

            <h3>POST /api/fleet.php — Create Fleet</h3>
            <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/fleet.php?action=create</div>

            <table class="doc-params">
                <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>name</code></td><td>string</td><td>Yes</td><td>Fleet name</td></tr>
                    <tr><td><code>description</code></td><td>string</td><td>No</td><td>Fleet description</td></tr>
                    <tr><td><code>tools</code></td><td>array</td><td>No</td><td>List of tool names to assign</td></tr>
                    <tr><td><code>max_agents</code></td><td>int</td><td>No</td><td>Max agents (default: 5)</td></tr>
                </tbody>
            </table>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Python</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>import requests

fleet = requests.post('https://gositeme.com/api/fleet.php?action=create',
    headers={'Authorization': 'Bearer sess_abc123'},
    json={
        'name': 'Legal Research Team',
        'description': 'Automated legal research agents',
        'tools': ['case_research', 'draft_motion', 'statute_lookup'],
        'max_agents': 10
    }
).json()

print(f"Fleet created: {fleet['id']}")  # fleet_abc123</code></pre>
            </div>

            <h3>GET /api/fleet.php — Dashboard Stats</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/fleet.php?action=dashboard</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>curl -H "Authorization: Bearer sess_abc123" \
  "https://gositeme.com/api/fleet.php?action=dashboard"

# {
#   "total_fleets": 3,
#   "total_agents": 12,
#   "active_tasks": 5,
#   "completed_today": 142,
#   "avg_response_time": "1.2s"
# }</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-api-stripe">
            <h2><i class="fas fa-credit-card"></i> Stripe Billing API</h2>
            <p>Manage subscriptions, create checkout sessions, and access the customer portal via the Stripe integration.</p>

            <h3>POST /api/stripe.php — Create Checkout</h3>
            <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/stripe.php?action=create_checkout</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>const checkout = await fetch('https://gositeme.com/api/stripe.php?action=create_checkout', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123'
  },
  body: JSON.stringify({ plan: 'professional' })
}).then(r => r.json());

// Redirect user to Stripe Checkout
window.location.href = checkout.url;</code></pre>
            </div>

            <h3>POST /api/stripe.php — Customer Portal</h3>
            <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/stripe.php?action=create_portal</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>curl -X POST https://gositeme.com/api/stripe.php?action=create_portal \
  -H "Authorization: Bearer sess_abc123"

# {"url":"https://billing.stripe.com/p/session/..."}</code></pre>
            </div>

            <h3>GET /api/stripe.php — Plans</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/stripe.php?action=plans</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Python</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>import requests

plans = requests.get('https://gositeme.com/api/stripe.php?action=plans').json()

for plan in plans['plans']:
    print(f"{plan['name']}: ${plan['price']}/mo — {plan['description']}")
# Builder: $15/mo — 300K tokens, 1 website, 1,220+ tools
# Creator: $22/mo — 450K tokens, 3 websites, priority support
# Professional: $29/mo — 600K tokens, 5 websites, SSH/SFTP
# Studio: $59/mo — 1.5M tokens, 10 websites, team sharing
# Business: $99/mo — 3M tokens, 25 websites, SSO/SAML</code></pre>
            </div>

            <h3>GET /api/stripe.php — Subscription Status</h3>
            <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/stripe.php?action=status</div>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">PHP</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>$ch = curl_init('https://gositeme.com/api/stripe.php?action=status');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer sess_abc123']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$status = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Plan: " . $status['plan'];           // professional
echo "Status: " . $status['status'];       // active
echo "Renews: " . $status['renews_at'];    // 2026-04-04
echo "Tool calls today: " . $status['usage']['tool_calls_today']; // 42</code></pre>
            </div>
        </div>

        <!-- ==================== VOICE INTEGRATION ==================== -->
        <div class="doc-section" id="section-vapi-setup">
            <h2><i class="fas fa-phone-alt"></i> VAPI Setup</h2>
            <p>Alfred integrates with VAPI for voice-first AI interactions. Users can call Alfred via phone or browser-based voice.</p>

            <h3>Webhook Configuration</h3>
            <p>Set up your VAPI assistant to point to the Alfred webhook endpoint:</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Webhook URL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>https://gositeme.com/api/vapi-webhook.php</code></pre>
            </div>

            <h3>VAPI Tool Format</h3>
            <p>Tools exposed to VAPI follow this schema:</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JSON</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>{
  "type": "function",
  "function": {
    "name": "weather_lookup",
    "description": "Get current weather for a location",
    "parameters": {
      "type": "object",
      "properties": {
        "location": {
          "type": "string",
          "description": "City name or zip code"
        }
      },
      "required": ["location"]
    }
  }
}</code></pre>
            </div>

            <div class="doc-info note">
                <i class="fas fa-info-circle"></i>
                <div><strong>Note:</strong> VAPI tool calls are routed through <code>/api/vapi-tools.php</code> which maps voice requests to Alfred's 1,220+ tool engine.</div>
            </div>
        </div>

        <div class="doc-section" id="section-voice-commands">
            <h2><i class="fas fa-microphone"></i> Voice Commands</h2>
            <p>Alfred understands natural language voice commands. Here are some examples:</p>

            <h3>Sample Voice Commands</h3>
            <ul>
                <li><strong>"Hey Alfred, summarize this article"</strong> — Triggers <code>summarize_text</code></li>
                <li><strong>"Schedule a meeting for tomorrow at 3pm"</strong> — Triggers <code>create_calendar_event</code></li>
                <li><strong>"What's the weather in Montreal?"</strong> — Triggers <code>weather_lookup</code></li>
                <li><strong>"Draft a demand letter for unpaid rent"</strong> — Triggers <code>draft_demand_letter</code></li>
                <li><strong>"Check my website's DNS records"</strong> — Triggers <code>dns_lookup</code></li>
                <li><strong>"How many agents are in my fleet?"</strong> — Triggers <code>fleet_dashboard</code></li>
                <li><strong>"Create a support ticket"</strong> — Triggers <code>create_ticket</code></li>
                <li><strong>"Translate this to French"</strong> — Triggers <code>translate_text</code></li>
            </ul>

            <h3>Voice Response Format</h3>
            <p>Alfred responds in natural language. Tool results are converted to spoken responses automatically via VAPI's text-to-speech engine.</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JSON — VAPI Response</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>{
  "results": [{
    "toolCallId": "call_abc123",
    "result": "The current weather in Montreal is -5°C with light snow. Wind chill brings it to -12°C."
  }]
}</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-phone-agent">
            <h2><i class="fas fa-headset"></i> Phone Agent</h2>
            <p>Deploy an AI phone agent using Alfred + VAPI. Your agent can answer calls, route inquiries, and execute tools on behalf of callers.</p>

            <h3>Setting Up Outbound Calls</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>const call = await fetch('https://gositeme.com/api/vapi-outbound.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123'
  },
  body: JSON.stringify({
    phone_number: '+15145551234',
    assistant_id: 'asst_xyz',
    first_message: 'Hi, this is Alfred from GoSiteMe. How can I help you today?'
  })
}).then(r => r.json());

console.log(call.call_id); // call_abc123</code></pre>
            </div>
        </div>

        <!-- ==================== TOOLS GUIDE ==================== -->
        <div class="doc-section" id="section-tool-categories">
            <h2><i class="fas fa-th-large"></i> Tool Categories</h2>
            <p>Alfred's 1,220+ tools are organized into 21 categories:</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:20px 0;">
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-gavel" style="color:var(--doc-accent-light);margin-right:8px;"></i> <strong>Legal</strong> — 43 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-heartbeat" style="color:var(--doc-fire);margin-right:8px;"></i> <strong>Healthcare</strong> — 38 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-graduation-cap" style="color:var(--doc-blue);margin-right:8px;"></i> <strong>Education</strong> — 52 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-server" style="color:var(--doc-green);margin-right:8px;"></i> <strong>DevOps</strong> — 48 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-photo-video" style="color:var(--doc-pink);margin-right:8px;"></i> <strong>Media</strong> — 35 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-chart-line" style="color:var(--doc-orange);margin-right:8px;"></i> <strong>Finance</strong> — 41 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-shopping-cart" style="color:var(--doc-cyan);margin-right:8px;"></i> <strong>E-Commerce</strong> — 22 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-shield-alt" style="color:var(--doc-fire);margin-right:8px;"></i> <strong>Security</strong> — 29 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-paint-brush" style="color:var(--doc-pink);margin-right:8px;"></i> <strong>Creative</strong> — 44 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-database" style="color:var(--doc-blue);margin-right:8px;"></i> <strong>Data</strong> — 33 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-globe" style="color:var(--doc-green);margin-right:8px;"></i> <strong>Web Hosting</strong> — 56 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-users" style="color:var(--doc-accent-light);margin-right:8px;"></i> <strong>Demographics</strong> — 287 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-brain" style="color:var(--doc-cyan);margin-right:8px;"></i> <strong>Consciousness</strong> — 12 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-phone-volume" style="color:var(--doc-orange);margin-right:8px;"></i> <strong>Voice & Telephony</strong> — 31 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-home" style="color:var(--doc-green);margin-right:8px;"></i> <strong>Real Estate</strong> — 18 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-code" style="color:var(--doc-accent-light);margin-right:8px;"></i> <strong>Developer</strong> — 62 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-language" style="color:var(--doc-blue);margin-right:8px;"></i> <strong>Translation</strong> — 14 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-robot" style="color:var(--doc-fire);margin-right:8px;"></i> <strong>Automation</strong> — 27 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-comments" style="color:var(--doc-pink);margin-right:8px;"></i> <strong>Communication</strong> — 15 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-chart-pie" style="color:var(--doc-orange);margin-right:8px;"></i> <strong>Analytics</strong> — 24 tools</div>
                <div style="padding:14px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:10px;"><i class="fas fa-cogs" style="color:var(--doc-cyan);margin-right:8px;"></i> <strong>Utilities</strong> — 23 tools</div>
            </div>
        </div>

        <div class="doc-section" id="section-using-tools">
            <h2><i class="fas fa-play-circle"></i> Using Tools</h2>
            <p>Every tool follows the same execution pattern: send a POST request with the tool name and arguments.</p>

            <h3>Request Format</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JSON</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>{
  "tool_name": "tool_identifier",
  "args": {
    "param1": "value1",
    "param2": "value2"
  }
}</code></pre>
            </div>

            <h3>Response Format</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JSON</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>{
  "success": true,
  "tool": "tool_identifier",
  "result": { ... },
  "execution_time": "0.45s",
  "credits_used": 1
}</code></pre>
            </div>

            <h3>Error Handling</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>try {
  const res = await fetch('https://gositeme.com/api/tools.php?action=execute', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer sess_abc123'
    },
    body: JSON.stringify({ tool_name: 'dns_lookup', args: { domain: 'example.com' } })
  });

  const data = await res.json();
  
  if (!data.success) {
    console.error(`Error: ${data.error} (code: ${data.code})`);
    // Error codes: 401 (unauthorized), 429 (rate limit), 400 (bad request), 500 (server error)
  }
} catch (err) {
  console.error('Network error:', err);
}</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-custom-tools">
            <h2><i class="fas fa-puzzle-piece"></i> Custom Tools</h2>
            <p>Enterprise users can create custom tools that integrate into Alfred's tool engine. Custom tools appear alongside built-in tools and can be used via API, voice, and the dashboard.</p>

            <h3>Defining a Custom Tool</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JSON</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>{
  "name": "lookup_inventory",
  "description": "Check product inventory levels",
  "category": "custom",
  "parameters": {
    "type": "object",
    "properties": {
      "sku": { "type": "string", "description": "Product SKU" },
      "warehouse": { "type": "string", "description": "Warehouse code" }
    },
    "required": ["sku"]
  },
  "webhook_url": "https://your-api.com/inventory/check",
  "auth_header": "X-API-Key: your-key"
}</code></pre>
            </div>

            <div class="doc-info tip">
                <i class="fas fa-lightbulb"></i>
                <div><strong>Custom tools</strong> are available on Enterprise plans. Contact sales at <a href="/enterprise.php">enterprise</a> to get started.</div>
            </div>
        </div>

        <!-- ==================== FLEET MANAGEMENT ==================== -->
        <div class="doc-section" id="section-creating-fleets">
            <h2><i class="fas fa-layer-group"></i> Creating Fleets</h2>
            <p>Fleets are groups of AI agents that work together on tasks. Each fleet can have its own tools, personality, and deployment target.</p>

            <h3>Fleet Configuration</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>const fleet = await fetch('https://gositeme.com/api/fleet.php?action=create', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123'
  },
  body: JSON.stringify({
    name: 'Healthcare Triage',
    description: 'Routes patient inquiries to appropriate departments',
    tools: ['symptom_checker', 'appointment_scheduler', 'insurance_verify'],
    max_agents: 10,
    personality: 'professional, empathetic, HIPAA-aware',
    auto_scale: true
  })
}).then(r => r.json());

console.log(`Fleet ${fleet.id} created with ${fleet.tools.length} tools`);</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-agent-deployment">
            <h2><i class="fas fa-paper-plane"></i> Agent Deployment</h2>
            <p>Deploy agents within a fleet to handle specific channels (web chat, phone, email).</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Python</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>import requests

agent = requests.post('https://gositeme.com/api/fleet.php?action=deploy_agent',
    headers={'Authorization': 'Bearer sess_abc123'},
    json={
        'fleet_id': 'fleet_abc123',
        'channel': 'phone',
        'name': 'Support Agent Alpha',
        'greeting': 'Hello! You\'ve reached GoSiteMe support. How can I help?'
    }
).json()

print(f"Agent deployed: {agent['agent_id']} on {agent['channel']}")</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-monitoring">
            <h2><i class="fas fa-chart-bar"></i> Monitoring</h2>
            <p>Monitor fleet performance in real-time via the Fleet Dashboard API or the web UI at <a href="/fleet-dashboard.php">/fleet-dashboard.php</a>.</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>curl -H "Authorization: Bearer sess_abc123" \
  "https://gositeme.com/api/fleet.php?action=dashboard"

# {
#   "total_fleets": 3,
#   "total_agents": 12,
#   "active_tasks": 5,
#   "completed_today": 142,
#   "avg_response_time": "1.2s",
#   "uptime": "99.97%",
#   "top_tools": ["summarize_text","dns_lookup","create_ticket"]
# }</code></pre>
            </div>
        </div>

        <!-- ==================== CONFERENCE ROOMS ==================== -->
        <div class="doc-section" id="section-creating-rooms">
            <h2><i class="fas fa-video"></i> Creating Conference Rooms</h2>
            <p>Voice conference rooms let multiple AI agents and humans collaborate in real-time using LiveKit integration.</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>const room = await fetch('https://gositeme.com/api/voice-manage.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123'
  },
  body: JSON.stringify({
    action: 'create_room',
    name: 'Legal Strategy Session',
    max_participants: 6,
    recording: true
  })
}).then(r => r.json());

console.log(`Room created: ${room.room_name}`);
console.log(`Join URL: ${room.join_url}`);</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-adding-agents">
            <h2><i class="fas fa-user-plus"></i> Adding Agents to Rooms</h2>
            <p>Invite AI agents into a conference room to participate in discussions, take notes, or execute tools.</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Python</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>import requests

# Add an AI agent to the room
result = requests.post('https://gositeme.com/api/voice-manage.php', json={
    'action': 'add_agent',
    'room_name': 'Legal Strategy Session',
    'agent_type': 'legal_researcher',
    'voice': 'en-US-Neural2-F',
    'tools': ['case_research', 'statute_lookup', 'draft_motion']
}, headers={'Authorization': 'Bearer sess_abc123'}).json()

print(f"Agent {result['agent_id']} joined the room")</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-recording">
            <h2><i class="fas fa-record-vinyl"></i> Recording</h2>
            <p>Conference rooms support real-time transcription and recording. Access recordings and transcripts via the API.</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">cURL</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code># Get recording and transcript
curl -H "Authorization: Bearer sess_abc123" \
  "https://gositeme.com/api/voice-manage.php?action=recording&room=Legal+Strategy+Session"

# {
#   "recording_url": "https://storage.gositeme.com/recordings/room_abc.mp4",
#   "transcript": [
#     {"speaker":"Human","time":"0:00","text":"Let's discuss the Smith case."},
#     {"speaker":"Legal AI","time":"0:05","text":"Based on my research, Smith v. Jones (2024)..."},
#     ...
#   ],
#   "duration": "14:32",
#   "participants": 4
# }</code></pre>
            </div>
        </div>

        <!-- ==================== BILLING ==================== -->
        <div class="doc-section" id="section-plans">
            <h2><i class="fas fa-tags"></i> Plans</h2>
            <p>Alfred offers three subscription tiers:</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0;">
                <div style="padding:20px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:12px;">
                    <h4 style="color:var(--doc-green);margin-top:0;">Builder — $15/mo</h4>
                    <ul style="padding-left:16px;"><li>1,220+ tools</li><li>300K tokens/month</li><li>1 website</li><li>AI Images & Video</li></ul>
                </div>
                <div style="padding:20px;background:var(--doc-surface);border:1px solid var(--doc-border);border-radius:12px;">
                    <h4 style="color:var(--doc-green);margin-top:0;">Creator — $22/mo</h4>
                    <ul style="padding-left:16px;"><li>450K tokens/month</li><li>3 websites</li><li>30GB NVMe</li><li>Priority support</li></ul>
                </div>
                <div style="padding:20px;background:var(--doc-surface);border:1px solid var(--doc-accent);border-radius:12px;">
                    <h4 style="color:var(--doc-accent-light);margin-top:0;">Professional — $29/mo</h4>
                    <ul style="padding-left:16px;"><li>600K tokens/month</li><li>5 websites</li><li>Git + SSH/SFTP</li><li>Database management</li></ul>
                </div>
                <div style="padding:20px;background:var(--doc-surface);border:1px solid var(--doc-orange);border-radius:12px;">
                    <h4 style="color:var(--doc-orange);margin-top:0;">Studio — $59/mo</h4>
                    <ul style="padding-left:16px;"><li>1.5M tokens/month</li><li>10 websites</li><li>Team sharing (5 users)</li><li>Premium AI models</li></ul>
                </div>
                <div style="padding:20px;background:var(--doc-surface);border:1px solid #ff3366;border-radius:12px;">
                    <h4 style="color:#ff3366;margin-top:0;">Business — $99/mo</h4>
                    <ul style="padding-left:16px;"><li>3M tokens/month</li><li>25 websites</li><li>SSO/SAML + RBAC</li><li>10 parallel AI sessions</li></ul>
                </div>
            </div>
        </div>

        <div class="doc-section" id="section-checkout">
            <h2><i class="fas fa-shopping-bag"></i> Checkout</h2>
            <p>Create a Stripe Checkout session to subscribe users:</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>async function subscribeToPlan(plan) {
  const res = await fetch('https://gositeme.com/api/stripe.php?action=create_checkout', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer sess_abc123'
    },
    body: JSON.stringify({
      plan: plan,  // 'starter', 'professional', or 'enterprise'
      success_url: 'https://gositeme.com/dashboard.php?upgraded=1',
      cancel_url: 'https://gositeme.com/pricing.php'
    })
  });
  
  const { url } = await res.json();
  window.location.href = url; // Redirect to Stripe Checkout
}</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-portal">
            <h2><i class="fas fa-user-circle"></i> Customer Portal</h2>
            <p>Let users manage their subscription, update payment methods, and view invoices via the Stripe Customer Portal:</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">PHP</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>// Redirect user to Stripe Customer Portal
$ch = curl_init('https://gositeme.com/api/stripe.php?action=create_portal');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $_SESSION['token']],
    CURLOPT_RETURNTRANSFER => true
]);
$portal = json_decode(curl_exec($ch), true);
curl_close($ch);

header('Location: ' . $portal['url']);
exit;</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-webhooks">
            <h2><i class="fas fa-bolt"></i> Webhooks</h2>
            <p>Receive real-time notifications for billing events via Stripe webhooks:</p>

            <h3>Webhook Events</h3>
            <ul>
                <li><code>checkout.session.completed</code> — User subscribed successfully</li>
                <li><code>customer.subscription.updated</code> — Plan changed</li>
                <li><code>customer.subscription.deleted</code> — Subscription cancelled</li>
                <li><code>invoice.payment_failed</code> — Payment failed</li>
            </ul>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">PHP — Webhook Handler</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>// Your webhook endpoint receives Stripe events
$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = \Stripe\Webhook::constructEvent($payload, $sig, $webhook_secret);

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        // Activate user subscription
        activateSubscription($session->client_reference_id, $session->subscription);
        break;
    case 'customer.subscription.deleted':
        // Downgrade user to free
        downgradeUser($event->data->object->metadata->user_id);
        break;
}</code></pre>
            </div>
        </div>

        <!-- ==================== SDKs ==================== -->
        <div class="doc-section" id="section-sdk-javascript">
            <h2><i class="fab fa-js-square"></i> JavaScript SDK</h2>
            <p>Use Alfred in any JavaScript environment — browser, Node.js, Deno, Bun.</p>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">JavaScript</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>class AlfredClient {
  constructor(token) {
    this.token = token;
    this.baseURL = 'https://gositeme.com/api';
  }

  async execute(toolName, args = {}) {
    const res = await fetch(`${this.baseURL}/tools.php?action=execute`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.token}`
      },
      body: JSON.stringify({ tool_name: toolName, args })
    });
    return res.json();
  }

  async listTools(category) {
    const res = await fetch(`${this.baseURL}/tools.php?action=list&category=${category}`, {
      headers: { 'Authorization': `Bearer ${this.token}` }
    });
    return res.json();
  }

  async search(query) {
    const res = await fetch(`${this.baseURL}/tools.php?action=search&q=${encodeURIComponent(query)}`, {
      headers: { 'Authorization': `Bearer ${this.token}` }
    });
    return res.json();
  }

  async createFleet(config) {
    const res = await fetch(`${this.baseURL}/fleet.php?action=create`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.token}`
      },
      body: JSON.stringify(config)
    });
    return res.json();
  }
}

// Usage
const alfred = new AlfredClient('sess_abc123');

const result = await alfred.execute('summarize_text', { text: 'Long article...', max_length: 100 });
console.log(result);</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-sdk-python">
            <h2><i class="fab fa-python"></i> Python SDK</h2>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Python</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>import requests

class AlfredClient:
    BASE_URL = 'https://gositeme.com/api'

    def __init__(self, token: str):
        self.token = token
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json'
        })

    def execute(self, tool_name: str, args: dict = None) -> dict:
        """Execute an Alfred tool."""
        return self.session.post(
            f'{self.BASE_URL}/tools.php?action=execute',
            json={'tool_name': tool_name, 'args': args or {}}
        ).json()

    def list_tools(self, category: str) -> dict:
        """List tools in a category."""
        return self.session.get(
            f'{self.BASE_URL}/tools.php?action=list&category={category}'
        ).json()

    def search(self, query: str) -> dict:
        """Search tools by keyword."""
        return self.session.get(
            f'{self.BASE_URL}/tools.php?action=search&q={query}'
        ).json()

    def create_fleet(self, name: str, tools: list, **kwargs) -> dict:
        """Create a new agent fleet."""
        return self.session.post(
            f'{self.BASE_URL}/fleet.php?action=create',
            json={'name': name, 'tools': tools, **kwargs}
        ).json()

    def fleet_dashboard(self) -> dict:
        """Get fleet dashboard stats."""
        return self.session.get(
            f'{self.BASE_URL}/fleet.php?action=dashboard'
        ).json()


# Usage
alfred = AlfredClient('sess_abc123')

# Execute a tool
result = alfred.execute('dns_lookup', {'domain': 'example.com', 'type': 'MX'})
print(result)

# Search tools
matches = alfred.search('homework')
for tool in matches['results']:
    print(f"  {tool['name']}: {tool['description']}")</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-sdk-php">
            <h2><i class="fab fa-php"></i> PHP SDK</h2>

            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">PHP</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>class AlfredClient {
    private string $token;
    private string $baseURL = 'https://gositeme.com/api';

    public function __construct(string $token) {
        $this->token = $token;
    }

    public function execute(string $toolName, array $args = []): array {
        return $this->post('/tools.php?action=execute', [
            'tool_name' => $toolName,
            'args' => $args
        ]);
    }

    public function listTools(string $category): array {
        return $this->get("/tools.php?action=list&category={$category}");
    }

    public function search(string $query): array {
        return $this->get("/tools.php?action=search&q=" . urlencode($query));
    }

    public function createFleet(string $name, array $tools, array $options = []): array {
        return $this->post('/fleet.php?action=create', array_merge([
            'name' => $name, 'tools' => $tools
        ], $options));
    }

    private function get(string $endpoint): array {
        $ch = curl_init($this->baseURL . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->token}"],
            CURLOPT_RETURNTRANSFER => true
        ]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $result;
    }

    private function post(string $endpoint, array $data): array {
        $ch = curl_init($this->baseURL . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->token}",
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true
        ]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $result;
    }
}

// Usage
$alfred = new AlfredClient('sess_abc123');

$result = $alfred->execute('summarize_text', ['text' => 'Your article...', 'max_length' => 200]);
print_r($result);

$tools = $alfred->listTools('legal');
echo count($tools['tools']) . " legal tools available\n";</code></pre>
            </div>
        </div>

        <div class="doc-section" id="section-sdk-curl">
            <h2><i class="fas fa-terminal"></i> cURL Examples</h2>
            <p>Use cURL for quick testing and shell scripts. Here are common patterns:</p>

            <h3>Login & Store Token</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Bash</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>#!/bin/bash
# Login and save token
TOKEN=$(curl -s -X POST https://gositeme.com/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"action":"login","email":"user@example.com","password":"secret"}' \
  | jq -r '.token')

echo "Token: $TOKEN"

# Execute a tool
curl -s -X POST "https://gositeme.com/api/tools.php?action=execute" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"tool_name":"weather_lookup","args":{"location":"Montreal"}}' | jq .

# List fleets
curl -s -H "Authorization: Bearer $TOKEN" \
  "https://gositeme.com/api/fleet.php?action=list" | jq .

# Check subscription status
curl -s -H "Authorization: Bearer $TOKEN" \
  "https://gositeme.com/api/stripe.php?action=status" | jq .</code></pre>
            </div>

            <h3>Search & Execute in One Script</h3>
            <div class="doc-code-wrap">
                <div class="doc-code-header">
                    <span class="doc-code-lang">Bash</span>
                    <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre class="doc-code-block"><code>#!/bin/bash
TOKEN="sess_abc123"

# Search for DNS tools
echo "=== DNS Tools ==="
curl -s -H "Authorization: Bearer $TOKEN" \
  "https://gositeme.com/api/tools.php?action=search&q=dns" | jq '.results[].name'

# Execute DNS lookup
echo "=== DNS Lookup ==="
curl -s -X POST "https://gositeme.com/api/tools.php?action=execute" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "tool_name": "dns_lookup",
    "args": {"domain": "gositeme.com", "type": "A"}
  }' | jq '.result'</code></pre>
            </div>
        </div>

    </main>
</div>

<script>
// Sidebar navigation
document.querySelectorAll('.doc-nav-group-title').forEach(title => {
    title.addEventListener('click', () => {
        const group = title.closest('.doc-nav-group');
        group.classList.toggle('active');
    });
});

// Section switching
function showSection(sectionId) {
    document.querySelectorAll('.doc-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.doc-nav-item').forEach(n => n.classList.remove('active'));

    const section = document.getElementById('section-' + sectionId);
    if (section) {
        section.classList.add('active');
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    const navItem = document.querySelector(`.doc-nav-item[data-section="${sectionId}"]`);
    if (navItem) {
        navItem.classList.add('active');
        const group = navItem.closest('.doc-nav-group');
        if (group) group.classList.add('active');
    }
}

document.querySelectorAll('.doc-nav-item').forEach(item => {
    item.addEventListener('click', () => {
        showSection(item.dataset.section);
    });
});

// Search
const docSearch = document.getElementById('docSearch');
if (docSearch) {
    docSearch.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        if (!query) return;

        const sections = document.querySelectorAll('.doc-section');
        let found = false;
        sections.forEach(section => {
            if (!found && section.textContent.toLowerCase().includes(query)) {
                const id = section.id.replace('section-', '');
                showSection(id);
                found = true;
            }
        });
    });
}

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

// Handle hash navigation
if (window.location.hash) {
    const hash = window.location.hash.replace('#', '');
    showSection(hash);
}
</script>

<?php include __DIR__ . '/../includes/site-footer.inc.php'; ?>
