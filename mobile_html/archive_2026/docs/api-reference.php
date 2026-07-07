<?php
$page_title = 'API Reference — Alfred AI Documentation | GoSiteMe';
$page_description = 'Complete REST API v1 reference for Alfred AI. Authentication, agents, chat, tools, fleets, voice, marketplace, usage endpoints with code examples in Node.js, Python, PHP, and cURL.';
$page_canonical = 'https://gositeme.com/docs/api-reference';
$page_og_title = 'Alfred AI API Reference — Complete v1 Endpoint Documentation';
$page_og_description = 'Full REST API v1 documentation for Alfred AI: API keys, OAuth2, agents, chat, 1,220+ tools, fleet management, voice, marketplace. SDK examples in JS, Python, PHP.';
include __DIR__ . '/../includes/lang.php';
include __DIR__ . '/../includes/site-header.inc.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "Alfred AI API Reference v1",
  "description": "Complete REST API v1 documentation for Alfred AI — authentication, agents, chat, tools, fleets, voice, marketplace, and usage endpoints.",
  "author": { "@type": "Organization", "name": "GoSiteMe", "url": "https://gositeme.com" },
  "publisher": { "@type": "Organization", "name": "GoSiteMe", "logo": { "@type": "ImageObject", "url": "https://gositeme.com/brand/logo_w.png" } },
  "datePublished": "2026-03-04",
  "dateModified": "2026-03-04",
  "mainEntityOfPage": "https://gositeme.com/docs/api-reference",
  "about": { "@type": "WebAPI", "name": "Alfred AI REST API v1", "documentation": "https://gositeme.com/docs/api-reference" },
  "proficiencyLevel": "Beginner"
}
</script>

<link rel="stylesheet" href="/assets/js/vendor/prism-tomorrow.min.css">

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

.doc-breadcrumbs { max-width:1400px; margin:0 auto; padding:100px 20px 0; display:flex; align-items:center; gap:8px; font-size:0.85rem; }
.doc-breadcrumbs a { color:var(--doc-text-muted); text-decoration:none; transition:color 0.2s; }
.doc-breadcrumbs a:hover { color:var(--doc-accent-light); }
.doc-breadcrumbs span { color:var(--doc-text-muted); }
.doc-breadcrumbs .current { color:var(--doc-text); font-weight:600; }

.doc-layout { display:flex; max-width:1400px; margin:0 auto; padding:30px 20px 80px; gap:40px; }
.doc-sidebar { width:var(--doc-sidebar-w); flex-shrink:0; position:sticky; top:100px; height:fit-content; max-height:calc(100vh - 120px); overflow-y:auto; }
.doc-sidebar::-webkit-scrollbar { width:4px; }
.doc-sidebar::-webkit-scrollbar-thumb { background:var(--doc-accent); border-radius:4px; }
.doc-sidebar-nav { list-style:none; padding:0; margin:0; }
.doc-sidebar-nav li { margin-bottom:2px; }
.doc-sidebar-nav a { display:flex; align-items:center; gap:10px; padding:10px 14px; color:var(--doc-text-muted); text-decoration:none; border-radius:8px; font-size:0.9rem; font-weight:500; transition:all 0.2s; }
.doc-sidebar-nav a:hover { background:var(--doc-surface-2); color:var(--doc-text); }
.doc-sidebar-nav a.active { background:var(--doc-surface-2); color:var(--doc-accent-light); font-weight:600; border-left:3px solid var(--doc-accent); }
.doc-sidebar-nav i { width:20px; text-align:center; font-size:0.9rem; }
.doc-sidebar-sub { list-style:none; padding:0 0 0 30px; margin:4px 0 8px; }
.doc-sidebar-sub li { margin-bottom:1px; }
.doc-sidebar-sub a { padding:6px 12px; font-size:0.82rem; color:var(--doc-text-muted); display:block; text-decoration:none; border-radius:6px; transition:all 0.2s; }
.doc-sidebar-sub a:hover { color:var(--doc-accent-light); background:var(--doc-surface); }

.doc-sidebar-toggle { display:none; position:fixed; bottom:20px; right:20px; z-index:1000; width:56px; height:56px; border-radius:50%; background:var(--doc-accent); color:#fff; border:none; font-size:1.3rem; cursor:pointer; box-shadow:0 4px 20px rgba(108,92,231,0.4); }
.doc-main { flex:1; min-width:0; }
.doc-main h1 { font-family:'Space Grotesk',sans-serif; font-size:clamp(1.8rem,4vw,2.4rem); font-weight:700; color:#fff; margin-bottom:8px; }
.doc-main .doc-subtitle { color:var(--doc-text-muted); font-size:1.1rem; margin-bottom:32px; line-height:1.6; }
.doc-main h2 { font-family:'Space Grotesk',sans-serif; font-size:1.5rem; font-weight:700; color:#fff; margin:48px 0 16px; padding-bottom:12px; border-bottom:1px solid var(--doc-border); }
.doc-main h3 { font-family:'Space Grotesk',sans-serif; font-size:1.2rem; font-weight:600; color:var(--doc-accent-light); margin:28px 0 10px; }
.doc-main h4 { font-size:1.05rem; font-weight:600; color:var(--doc-text); margin:20px 0 8px; }
.doc-main p,.doc-main li { color:var(--doc-text-muted); line-height:1.7; font-size:0.95rem; }
.doc-main ul,.doc-main ol { padding-left:20px; margin:12px 0; }
.doc-main a { color:var(--doc-accent-light); text-decoration:none; }
.doc-main a:hover { text-decoration:underline; }

.doc-code-wrap { position:relative; margin:16px 0; border-radius:var(--doc-radius); overflow:hidden; border:1px solid var(--doc-border); }
.doc-code-header { display:flex; align-items:center; justify-content:space-between; padding:8px 16px; background:var(--doc-surface-3); border-bottom:1px solid var(--doc-border); }
.doc-code-lang { font-size:0.75rem; font-weight:600; color:var(--doc-accent-light); text-transform:uppercase; letter-spacing:0.5px; }
.doc-copy-btn { background:none; border:1px solid var(--doc-border); color:var(--doc-text-muted); padding:4px 12px; border-radius:6px; font-size:0.75rem; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:5px; }
.doc-copy-btn:hover { color:var(--doc-text); border-color:var(--doc-accent); }
.doc-copy-btn.copied { color:var(--doc-green); border-color:var(--doc-green); }
.doc-code-block { background:var(--doc-surface); padding:20px; overflow-x:auto; font-family:'JetBrains Mono','Fira Code',monospace; font-size:0.85rem; line-height:1.6; color:var(--doc-text); margin:0; }
.doc-code-block code { background:none; padding:0; }

.doc-endpoint { display:inline-flex; align-items:center; gap:8px; margin:8px 0; padding:8px 16px; background:var(--doc-surface); border:1px solid var(--doc-border); border-radius:8px; font-family:'JetBrains Mono','Fira Code',monospace; font-size:0.85rem; }
.doc-method { padding:2px 8px; border-radius:4px; font-weight:700; font-size:0.75rem; text-transform:uppercase; }
.doc-method.get { background:rgba(0,184,148,0.15); color:var(--doc-green); }
.doc-method.post { background:rgba(108,92,231,0.15); color:var(--doc-accent-light); }
.doc-method.put { background:rgba(253,203,110,0.15); color:var(--doc-orange); }
.doc-method.patch { background:rgba(253,203,110,0.15); color:var(--doc-orange); }
.doc-method.delete { background:rgba(225,112,85,0.15); color:var(--doc-fire); }

.doc-info { padding:16px 20px; border-radius:var(--doc-radius); margin:16px 0; display:flex; gap:12px; align-items:flex-start; }
.doc-info i { margin-top:3px; font-size:1.1rem; flex-shrink:0; }
.doc-info.tip { background:rgba(0,184,148,0.08); border:1px solid rgba(0,184,148,0.2); }
.doc-info.tip i { color:var(--doc-green); }
.doc-info.warn { background:rgba(253,203,110,0.08); border:1px solid rgba(253,203,110,0.2); }
.doc-info.warn i { color:var(--doc-orange); }
.doc-info.note { background:rgba(108,92,231,0.08); border:1px solid rgba(108,92,231,0.2); }
.doc-info.note i { color:var(--doc-accent-light); }

.doc-params { width:100%; border-collapse:collapse; margin:12px 0; }
.doc-params th { text-align:left; padding:10px 14px; background:var(--doc-surface-2); color:var(--doc-text); font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--doc-border); }
.doc-params td { padding:10px 14px; border-bottom:1px solid var(--doc-border); font-size:0.85rem; color:var(--doc-text-muted); }
.doc-params code { background:var(--doc-surface-2); padding:2px 6px; border-radius:4px; font-size:0.8rem; color:var(--doc-cyan); }
.doc-params .required { color:var(--doc-fire); font-size:0.75rem; font-weight:600; }

.doc-rate-limit-table { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin:16px 0; }
.doc-rate-card { background:var(--doc-surface); border:1px solid var(--doc-border); border-radius:var(--doc-radius); padding:20px; text-align:center; }
.doc-rate-card .rate-tier { color:var(--doc-text-muted); font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; }
.doc-rate-card .rate-value { font-family:'Space Grotesk',sans-serif; font-size:1.8rem; font-weight:700; color:var(--doc-accent-light); }
.doc-rate-card .rate-label { color:var(--doc-text-muted); font-size:0.82rem; margin-top:4px; }

.doc-sdk-tabs { display:flex; gap:0; border-bottom:1px solid var(--doc-border); margin:16px 0 0; }
.doc-sdk-tab { padding:8px 16px; background:none; border:none; color:var(--doc-text-muted); font-size:0.82rem; font-weight:600; cursor:pointer; border-bottom:2px solid transparent; transition:all 0.2s; }
.doc-sdk-tab:hover { color:var(--doc-text); }
.doc-sdk-tab.active { color:var(--doc-accent-light); border-bottom-color:var(--doc-accent); }
.doc-sdk-panel { display:none; }
.doc-sdk-panel.active { display:block; }

.doc-version-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; background:rgba(108,92,231,0.15); border:1px solid rgba(108,92,231,0.3); border-radius:20px; font-size:0.75rem; font-weight:600; color:var(--doc-accent-light); margin-left:8px; vertical-align:middle; }

@media (max-width:900px) {
    .doc-sidebar { position:fixed; top:0; left:-300px; width:280px; height:100vh; background:var(--doc-bg); z-index:999; padding:80px 16px 20px; transition:left 0.3s ease; border-right:1px solid var(--doc-border); }
    .doc-sidebar.open { left:0; }
    .doc-sidebar-toggle { display:flex; align-items:center; justify-content:center; }
    .doc-layout { padding:20px 16px 60px; }
    .doc-breadcrumbs { padding-top:80px; }
    .doc-rate-limit-table { grid-template-columns:1fr 1fr; }
}
</style>

<div class="doc-breadcrumbs">
    <a href="/docs/">Docs</a>
    <span>›</span>
    <span class="current">API Reference v1</span>
</div>

<div class="doc-layout">
    <nav class="doc-sidebar" id="docSidebar">
        <ul class="doc-sidebar-nav">
            <li><a href="/docs/"><i class="fas fa-home"></i> Docs Home</a></li>
            <li><a href="/docs/getting-started"><i class="fas fa-rocket"></i> Getting Started</a></li>
            <li>
                <a href="/docs/api-reference" class="active"><i class="fas fa-plug"></i> API Reference</a>
                <ul class="doc-sidebar-sub">
                    <li><a href="#overview">Overview</a></li>
                    <li><a href="#auth">Authentication</a></li>
                    <li><a href="#agents">Agents</a></li>
                    <li><a href="#chat">Chat</a></li>
                    <li><a href="#tools">Tools</a></li>
                    <li><a href="#fleets">Fleets</a></li>
                    <li><a href="#voice">Voice</a></li>
                    <li><a href="#marketplace">Marketplace</a></li>
                    <li><a href="#usage">Usage</a></li>
                    <li><a href="#rate-limits">Rate Limits</a></li>
                    <li><a href="#errors">Error Codes</a></li>
                    <li><a href="#webhooks">Webhooks</a></li>
                    <li><a href="#sdks">SDKs</a></li>
                </ul>
            </li>
            <li><a href="/docs/voice-integration"><i class="fas fa-microphone"></i> Voice Integration</a></li>
            <li><a href="/docs/tools-guide"><i class="fas fa-wrench"></i> Tools Guide</a></li>
            <li><a href="/sdks"><i class="fas fa-cube"></i> SDKs</a></li>
        </ul>
    </nav>

    <button class="doc-sidebar-toggle" id="docSidebarToggle" aria-label="Toggle documentation menu">
        <i class="fas fa-bars"></i>
    </button>

    <main class="doc-main">
        <h1><i class="fas fa-plug"></i> API Reference <span class="doc-version-badge"><i class="fas fa-code-branch"></i> v1</span></h1>
        <p class="doc-subtitle">Complete REST API documentation for the Alfred AI platform. All endpoints use JSON, return structured responses, and support API Key &amp; OAuth2 authentication.</p>

        <!-- OVERVIEW -->
        <h2 id="overview"><i class="fas fa-server"></i> Overview</h2>

        <div class="doc-info note">
            <i class="fas fa-server"></i>
            <div><strong>Base URL:</strong> <code>https://gositeme.com/api/v1/</code> — All endpoint paths below are relative to this base.</div>
        </div>

        <p>The Alfred API v1 is a RESTful JSON API organized around 7 core resources:</p>
        <table class="doc-params">
            <thead><tr><th>Resource</th><th>Base Path</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>agents</code></td><td>/agents</td><td>Create, manage, and execute AI agents</td></tr>
                <tr><td><code>chat</code></td><td>/chat</td><td>Conversational AI with streaming support</td></tr>
                <tr><td><code>tools</code></td><td>/tools</td><td>1,220+ tools — search, discover, execute</td></tr>
                <tr><td><code>fleets</code></td><td>/fleets</td><td>Multi-agent fleet orchestration</td></tr>
                <tr><td><code>voice</code></td><td>/voice</td><td>Voice calls &amp; conference rooms</td></tr>
                <tr><td><code>marketplace</code></td><td>/marketplace</td><td>Browse &amp; install community extensions</td></tr>
                <tr><td><code>usage</code></td><td>/usage</td><td>Usage metrics &amp; billing</td></tr>
            </tbody>
        </table>

        <h4>Response Envelope</h4>
        <p>Successful responses return a <code>data</code> key (single resource) or paginated envelope:</p>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">JSON — Single Resource</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": { "id": 1, "agent_name": "Support Bot", "status": "idle" }
}</code></pre>
        </div>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">JSON — Paginated</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": [ ... ],
  "meta": {
    "total": 42,
    "page": 1,
    "per_page": 20,
    "total_pages": 3
  }
}</code></pre>
        </div>
        <p>All paginated endpoints accept <code>?page=N&amp;per_page=N</code> (max 100).</p>

        <h4>Request Headers</h4>
        <table class="doc-params">
            <thead><tr><th>Header</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>Authorization</code></td><td class="required">Yes</td><td><code>Bearer &lt;api_key or oauth_token&gt;</code></td></tr>
                <tr><td><code>Content-Type</code></td><td>POST/PUT</td><td><code>application/json</code></td></tr>
                <tr><td><code>X-Request-ID</code></td><td>No</td><td>Client-generated request ID for tracing</td></tr>
            </tbody>
        </table>

        <!-- AUTHENTICATION -->
        <h2 id="auth"><i class="fas fa-lock"></i> Authentication</h2>
        <p>The API supports three authentication methods:</p>

        <h3>1. API Key (Recommended)</h3>
        <p>Create API keys in the <a href="/developer-portal">Developer Portal</a>. Keys use the format <code>ak_live_&lt;prefix&gt;_&lt;secret&gt;</code> (production) or <code>ak_test_&lt;prefix&gt;_&lt;secret&gt;</code> (sandbox).</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">cURL</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-bash">curl https://gositeme.com/api/v1/tools \
  -H "Authorization: Bearer ak_live_abc123_secretkey456"</code></pre>
        </div>

        <div class="doc-sdk-tabs" data-group="auth">
            <button class="doc-sdk-tab active" onclick="showSdkPanel(this,'auth')">Node.js</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'auth')">Python</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'auth')">PHP</button>
        </div>
        <div class="doc-sdk-panel active" data-group="auth">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-typescript">import { AlfredClient } from '@alfredai/sdk';
const alfred = new AlfredClient({ apiKey: 'ak_live_abc123_secretkey456' });</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="auth">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-python">from alfred_sdk import AlfredClient
client = AlfredClient(api_key="ak_live_abc123_secretkey456")</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="auth">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-php">$alfred = new \AlfredAI\Alfred(['api_key' => 'ak_live_abc123_secretkey456']);</code></pre>
            </div>
        </div>

        <h3>2. OAuth2 Bearer Token</h3>
        <p>OAuth apps receive tokens prefixed with <code>aat_</code>. Register your app in the Developer Portal to get client credentials.</p>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">cURL</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-bash">curl https://gositeme.com/api/v1/agents \
  -H "Authorization: Bearer aat_your_oauth_token_here"</code></pre>
        </div>

        <h3>3. Session Token (Legacy)</h3>
        <p>Session tokens from <code>/api/auth.php</code> (prefix <code>sess_</code>) are still supported for backward compatibility.</p>

        <div class="doc-info warn">
            <i class="fas fa-exclamation-triangle"></i>
            <div><strong>API keys in query parameters</strong> (<code>?api_key=...</code>) are supported for testing but not recommended for production — URLs may be logged by proxies and servers.</div>
        </div>

        <h3>Scopes</h3>
        <p>API keys and OAuth tokens can be scoped to specific permissions:</p>
        <table class="doc-params">
            <thead><tr><th>Scope</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>*</code></td><td>Full access (default for API keys)</td></tr>
                <tr><td><code>agents:read</code></td><td>Read agent data</td></tr>
                <tr><td><code>agents:write</code></td><td>Create, update, delete agents</td></tr>
                <tr><td><code>chat:read</code></td><td>Read chat endpoint info</td></tr>
                <tr><td><code>chat:write</code></td><td>Send chat messages</td></tr>
                <tr><td><code>tools:read</code></td><td>List and search tools</td></tr>
                <tr><td><code>tools:execute</code></td><td>Execute tools</td></tr>
                <tr><td><code>fleets:read</code></td><td>Read fleet data</td></tr>
                <tr><td><code>fleets:write</code></td><td>Create, manage fleets</td></tr>
                <tr><td><code>voice:read</code></td><td>Read call/room data</td></tr>
                <tr><td><code>voice:write</code></td><td>Create rooms, start calls</td></tr>
                <tr><td><code>marketplace:read</code></td><td>Browse marketplace</td></tr>
                <tr><td><code>marketplace:write</code></td><td>Install marketplace items</td></tr>
                <tr><td><code>usage:read</code></td><td>View usage data</td></tr>
            </tbody>
        </table>

        <!-- AGENTS -->
        <h2 id="agents"><i class="fas fa-robot"></i> Agents</h2>
        <p>Create, manage, and execute AI agents. Agents can be assigned roles, skills, voice capabilities, and attached to fleets.</p>

        <h3>POST /agents — Create Agent</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /agents</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>agent_name</code></td><td>string</td><td class="required">Yes</td><td>Display name (max 100 chars)</td></tr>
                <tr><td><code>agent_role</code></td><td>string</td><td class="required">Yes</td><td><code>leader</code>, <code>specialist</code>, <code>generalist</code>, <code>reviewer</code></td></tr>
                <tr><td><code>task</code></td><td>string</td><td>No</td><td>Initial task description (max 2000 chars)</td></tr>
                <tr><td><code>skills</code></td><td>string[]</td><td>No</td><td>Array of tool names the agent can use</td></tr>
                <tr><td><code>fleet_id</code></td><td>integer</td><td>No</td><td>Fleet to attach the agent to</td></tr>
                <tr><td><code>voice_enabled</code></td><td>boolean</td><td>No</td><td>Enable voice interaction</td></tr>
                <tr><td><code>voice_engine</code></td><td>string</td><td>No</td><td><code>kokoro</code>, <code>orpheus</code>, <code>cartesia</code>, <code>elevenlabs</code></td></tr>
            </tbody>
        </table>

        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">cURL</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-bash">curl -X POST https://gositeme.com/api/v1/agents \
  -H "Authorization: Bearer ak_live_xxx_yyy" \
  -H "Content-Type: application/json" \
  -d '{
    "agent_name": "Support Bot",
    "agent_role": "specialist",
    "task": "Handle customer billing questions",
    "skills": ["product_lookup", "ticket_create"],
    "voice_enabled": true,
    "voice_engine": "cartesia"
  }'</code></pre>
        </div>

        <div class="doc-sdk-tabs" data-group="agent-create">
            <button class="doc-sdk-tab active" onclick="showSdkPanel(this,'agent-create')">Node.js</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'agent-create')">Python</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'agent-create')">PHP</button>
        </div>
        <div class="doc-sdk-panel active" data-group="agent-create">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-typescript">const agent = await alfred.agents.create({
  agent_name: 'Support Bot',
  agent_role: 'specialist',
  task: 'Handle customer billing questions',
  skills: ['product_lookup', 'ticket_create'],
  voice_enabled: true,
  voice_engine: 'cartesia',
});</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="agent-create">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-python">agent = client.agents.create(
    agent_name="Support Bot",
    agent_role="specialist",
    task="Handle customer billing questions",
    skills=["product_lookup", "ticket_create"],
    voice_enabled=True,
    voice_engine="cartesia",
)</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="agent-create">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-php">$agent = $alfred->agents->create([
    'agent_name' => 'Support Bot',
    'agent_role' => 'specialist',
    'task' => 'Handle customer billing questions',
    'skills' => ['product_lookup', 'ticket_create'],
    'voice_enabled' => true,
    'voice_engine' => 'cartesia',
]);</code></pre>
            </div>
        </div>

        <h3>GET /agents — List Agents</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /agents</div>
        <p>Returns a paginated list of the authenticated user's agents.</p>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">cURL</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-bash">curl "https://gositeme.com/api/v1/agents?page=1&per_page=10" \
  -H "Authorization: Bearer ak_live_xxx_yyy"</code></pre>
        </div>

        <h3>GET /agents/{id} — Get Agent</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /agents/{id}</div>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">Response</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "id": 42,
    "agent_name": "Support Bot",
    "agent_role": "specialist",
    "task": "Handle customer billing questions",
    "skills": ["product_lookup", "ticket_create"],
    "fleet_id": null,
    "status": "idle",
    "voice_enabled": true,
    "voice_engine": "cartesia",
    "created_at": "2026-03-04T12:00:00Z",
    "updated_at": "2026-03-04T12:00:00Z"
  }
}</code></pre>
        </div>

        <h3>PUT /agents/{id} — Update Agent</h3>
        <div class="doc-endpoint"><span class="doc-method put">PUT</span> /agents/{id}</div>
        <p>Update any mutable agent fields. Only include fields you want to change.</p>

        <h3>DELETE /agents/{id} — Delete Agent</h3>
        <div class="doc-endpoint"><span class="doc-method delete">DELETE</span> /agents/{id}</div>

        <h3>POST /agents/{id}/execute — Execute Task</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /agents/{id}/execute</div>
        <p>Send a message to an agent and receive a response with tool usage details.</p>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>message</code></td><td>string</td><td class="required">Yes</td><td>Task or message for the agent</td></tr>
                <tr><td><code>tools</code></td><td>string[]</td><td>No</td><td>Override available tools for this execution</td></tr>
                <tr><td><code>context</code></td><td>object</td><td>No</td><td>Additional context data</td></tr>
            </tbody>
        </table>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">Response</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "reply": "I looked up the account and created support ticket #1234.",
    "tools_used": ["product_lookup", "ticket_create"],
    "execution_time_ms": 2340
  }
}</code></pre>
        </div>

        <h3>POST /agents/{id}/deploy — Deploy Agent</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /agents/{id}/deploy</div>
        <p>Deploy an agent to start accepting tasks. Returns the updated agent with <code>status: "working"</code>.</p>

        <!-- CHAT -->
        <h2 id="chat"><i class="fas fa-comments"></i> Chat</h2>
        <p>Send messages to Alfred AI and receive responses. Supports streaming via Server-Sent Events.</p>

        <h3>POST /chat — Send Message</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /chat</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>message</code></td><td>string</td><td class="required">Yes</td><td>Message (max 10,000 chars)</td></tr>
                <tr><td><code>conversation_id</code></td><td>string</td><td>No</td><td>Continue existing conversation</td></tr>
                <tr><td><code>model</code></td><td>string</td><td>No</td><td>AI model (default: <code>alfred-default</code>)</td></tr>
                <tr><td><code>temperature</code></td><td>float</td><td>No</td><td>0.0–1.0 creativity (default: 0.7)</td></tr>
                <tr><td><code>tools</code></td><td>string[]</td><td>No</td><td>Tools to make available</td></tr>
                <tr><td><code>stream</code></td><td>boolean</td><td>No</td><td>Enable SSE streaming (default: false)</td></tr>
            </tbody>
        </table>

        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">cURL</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-bash">curl -X POST https://gositeme.com/api/v1/chat \
  -H "Authorization: Bearer ak_live_xxx_yyy" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "What are the DNS records for example.com?",
    "tools": ["dns_lookup"],
    "temperature": 0.5
  }'</code></pre>
        </div>

        <h4>Response (Non-Streaming)</h4>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">JSON</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "reply": "Here are the DNS records for example.com...",
    "conversation_id": "conv_abc123",
    "model": "alfred-default",
    "tools_used": ["dns_lookup"],
    "tokens_used": 347,
    "execution_time_ms": 1240
  }
}</code></pre>
        </div>

        <h4>Streaming (SSE)</h4>
        <p>Set <code>"stream": true</code> to receive Server-Sent Events. Each event contains a JSON chunk:</p>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">SSE Stream</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code>data: {"type":"text","content":"Here are "}
data: {"type":"text","content":"the DNS records..."}
data: {"type":"tool_start","tool":"dns_lookup"}
data: {"type":"tool_end","tool":"dns_lookup"}
data: {"type":"done","conversation_id":"conv_abc123"}
data: [DONE]</code></pre>
        </div>

        <div class="doc-sdk-tabs" data-group="chat-stream">
            <button class="doc-sdk-tab active" onclick="showSdkPanel(this,'chat-stream')">Node.js</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'chat-stream')">Python</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'chat-stream')">PHP</button>
        </div>
        <div class="doc-sdk-panel active" data-group="chat-stream">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-typescript">for await (const chunk of alfred.chat.stream({ message: 'Explain DNS' })) {
  if (chunk.type === 'text') process.stdout.write(chunk.content || '');
}</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="chat-stream">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-python">for chunk in client.chat.stream("Explain DNS"):
    if chunk.get("type") == "text":
        print(chunk["content"], end="", flush=True)</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="chat-stream">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-php">$alfred->chat->stream('Explain DNS', function($chunk) {
    if ($chunk['type'] === 'text') echo $chunk['content'];
});</code></pre>
            </div>
        </div>

        <h3>GET /chat — List Conversations</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /chat</div>
        <p>Returns a paginated list of recent conversations.</p>

        <!-- TOOLS -->
        <h2 id="tools"><i class="fas fa-wrench"></i> Tools</h2>
        <p>Search, discover, and execute 1,220+ tools across 17 categories — legal, healthcare, devops, security, analytics, and more.</p>

        <h3>GET /tools — List Tools</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /tools</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>search</code> / <code>q</code></td><td>string</td><td>No</td><td>Search query</td></tr>
                <tr><td><code>category</code></td><td>string</td><td>No</td><td>Filter by category slug</td></tr>
                <tr><td><code>tier</code></td><td>string</td><td>No</td><td>Filter by tier (free, starter, pro, enterprise)</td></tr>
                <tr><td><code>page</code></td><td>integer</td><td>No</td><td>Page number (default: 1)</td></tr>
                <tr><td><code>per_page</code></td><td>integer</td><td>No</td><td>Results per page (max: 100)</td></tr>
            </tbody>
        </table>

        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">cURL</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-bash">curl "https://gositeme.com/api/v1/tools?search=dns&category=devops&per_page=5" \
  -H "Authorization: Bearer ak_live_xxx_yyy"</code></pre>
        </div>

        <h3>GET /tools/categories — List Categories</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /tools/categories</div>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">Response</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": [
    { "slug": "legal", "name": "Legal", "count": 85 },
    { "slug": "healthcare", "name": "Healthcare", "count": 62 },
    { "slug": "devops", "name": "DevOps", "count": 94 },
    { "slug": "security", "name": "Security", "count": 71 }
  ]
}</code></pre>
        </div>

        <h3>GET /tools/{name} — Get Tool Details</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /tools/{name}</div>
        <p>Returns tool metadata including JSON Schema input definition.</p>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">Response</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "name": "dns_lookup",
    "description": "Look up DNS records for a domain",
    "category": "devops",
    "tier": "free",
    "input_schema": {
      "type": "object",
      "properties": {
        "domain": { "type": "string", "description": "Domain name to look up" },
        "type": { "type": "string", "enum": ["A","AAAA","MX","NS","TXT","CNAME"], "default": "A" }
      },
      "required": ["domain"]
    },
    "tags": ["dns", "network", "devops"]
  }
}</code></pre>
        </div>

        <h3>POST /tools/{name}/execute — Execute Tool</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /tools/{name}/execute</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>args</code></td><td>object</td><td class="required">Yes</td><td>Tool-specific arguments (see tool's input_schema)</td></tr>
            </tbody>
        </table>

        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">cURL</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-bash">curl -X POST https://gositeme.com/api/v1/tools/dns_lookup/execute \
  -H "Authorization: Bearer ak_live_xxx_yyy" \
  -H "Content-Type: application/json" \
  -d '{"args": {"domain": "example.com", "type": "A"}}'</code></pre>
        </div>

        <div class="doc-sdk-tabs" data-group="tool-exec">
            <button class="doc-sdk-tab active" onclick="showSdkPanel(this,'tool-exec')">Node.js</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'tool-exec')">Python</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'tool-exec')">PHP</button>
        </div>
        <div class="doc-sdk-panel active" data-group="tool-exec">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-typescript">const result = await alfred.tools.execute('dns_lookup', {
  args: { domain: 'example.com', type: 'A' },
});
console.log(result.data.result); // { records: [...] }</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="tool-exec">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-python">result = client.tools.execute("dns_lookup", args={"domain": "example.com", "type": "A"})
print(result["data"]["result"])</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="tool-exec">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-php">$result = $alfred->tools->execute('dns_lookup', ['domain' => 'example.com', 'type' => 'A']);
print_r($result['data']['result']);</code></pre>
            </div>
        </div>

        <h4>Execution Response</h4>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">JSON</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "tool": "dns_lookup",
    "result": {
      "records": [
        { "type": "A", "value": "93.184.216.34", "ttl": 300 }
      ]
    },
    "execution_time_ms": 142,
    "credits_used": 1
  }
}</code></pre>
        </div>

        <!-- FLEETS -->
        <h2 id="fleets"><i class="fas fa-users-cog"></i> Fleets</h2>
        <p>Orchestrate multiple agents as a fleet. Fleets support parallel execution, auto-scaling, and round-robin task distribution.</p>

        <h3>POST /fleets — Create Fleet</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /fleets</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>name</code></td><td>string</td><td class="required">Yes</td><td>Fleet display name</td></tr>
                <tr><td><code>description</code></td><td>string</td><td>No</td><td>Fleet description</td></tr>
                <tr><td><code>max_agents</code></td><td>integer</td><td>No</td><td>Maximum agent slots (default: 5)</td></tr>
                <tr><td><code>auto_scale</code></td><td>boolean</td><td>No</td><td>Enable auto-scaling (default: false)</td></tr>
            </tbody>
        </table>

        <h3>GET /fleets — List Fleets</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /fleets</div>

        <h3>GET /fleets/{id} — Get Fleet</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /fleets/{id}</div>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">Response</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "id": 7,
    "name": "Support Team",
    "description": "Customer support agents",
    "status": "active",
    "max_agents": 10,
    "auto_scale": true,
    "agent_count": 3,
    "tasks_completed": 1247,
    "created_at": "2026-03-01T10:00:00Z"
  }
}</code></pre>
        </div>

        <h3>PUT /fleets/{id} — Update Fleet</h3>
        <div class="doc-endpoint"><span class="doc-method put">PUT</span> /fleets/{id}</div>

        <h3>DELETE /fleets/{id} — Delete Fleet</h3>
        <div class="doc-endpoint"><span class="doc-method delete">DELETE</span> /fleets/{id}</div>

        <h3>POST /fleets/{id}/deploy — Deploy Fleet</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /fleets/{id}/deploy</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>task</code></td><td>string</td><td>No</td><td>Task for the fleet to execute</td></tr>
                <tr><td><code>strategy</code></td><td>string</td><td>No</td><td><code>parallel</code>, <code>sequential</code>, <code>round-robin</code></td></tr>
                <tr><td><code>timeout</code></td><td>integer</td><td>No</td><td>Timeout in seconds</td></tr>
            </tbody>
        </table>

        <!-- VOICE -->
        <h2 id="voice"><i class="fas fa-microphone"></i> Voice</h2>
        <p>Voice calls and AI-powered conference rooms. Supports Kokoro, Orpheus, Cartesia, and ElevenLabs engines.</p>

        <h3>GET /voice/calls — List Call History</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /voice/calls</div>
        <p>Returns a paginated list of voice calls.</p>

        <h3>GET /voice/calls/{id} — Get Call Details</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /voice/calls/{id}</div>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">Response</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "id": 156,
    "room_name": "Sales Call",
    "status": "completed",
    "duration_seconds": 340,
    "participants": 2,
    "voice_engine": "cartesia",
    "transcript": "Agent: Hello, how can I help today?...",
    "created_at": "2026-03-04T14:30:00Z"
  }
}</code></pre>
        </div>

        <h3>POST /voice/rooms — Create Room</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /voice/rooms</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>name</code></td><td>string</td><td class="required">Yes</td><td>Room name</td></tr>
                <tr><td><code>max_participants</code></td><td>integer</td><td>No</td><td>Max participants (default: 10)</td></tr>
                <tr><td><code>voice_engine</code></td><td>string</td><td>No</td><td><code>kokoro</code>, <code>orpheus</code>, <code>cartesia</code>, <code>elevenlabs</code></td></tr>
                <tr><td><code>agent_id</code></td><td>integer</td><td>No</td><td>AI agent to join the room</td></tr>
            </tbody>
        </table>

        <h3>GET /voice/rooms — List Active Rooms</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /voice/rooms</div>

        <h3>GET /voice/rooms/{id} — Get Room Details</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /voice/rooms/{id}</div>

        <h3>POST /voice/calls — Start Outbound Call</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /voice/calls</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>to</code></td><td>string</td><td class="required">Yes</td><td>Phone number (E.164) or SIP URI</td></tr>
                <tr><td><code>agent_id</code></td><td>integer</td><td>No</td><td>Agent to handle the call</td></tr>
                <tr><td><code>voice_engine</code></td><td>string</td><td>No</td><td>Voice engine override</td></tr>
            </tbody>
        </table>

        <!-- MARKETPLACE -->
        <h2 id="marketplace"><i class="fas fa-store"></i> Marketplace</h2>
        <p>Browse and install community-created agents, tools, fleet templates, and extensions.</p>

        <h3>GET /marketplace — List Items</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /marketplace</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>search</code></td><td>string</td><td>No</td><td>Search query</td></tr>
                <tr><td><code>type</code></td><td>string</td><td>No</td><td><code>agent</code>, <code>tool</code>, <code>fleet_template</code>, <code>extension</code></td></tr>
                <tr><td><code>sort</code></td><td>string</td><td>No</td><td><code>popular</code>, <code>newest</code>, <code>rating</code></td></tr>
            </tbody>
        </table>

        <h3>GET /marketplace/{id} — Get Item Details</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /marketplace/{id}</div>

        <h3>POST /marketplace/{id}/install — Install Item</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /marketplace/{id}/install</div>

        <!-- USAGE -->
        <h2 id="usage"><i class="fas fa-chart-bar"></i> Usage</h2>
        <p>Monitor API usage, tool executions, and billing metrics.</p>

        <h3>GET /usage — Current Period Summary</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /usage</div>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">Response</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "data": {
    "period": "2026-03",
    "plan": "professional",
    "api_calls": { "used": 4521, "limit": null },
    "tools_executed": { "used": 892, "limit": null },
    "agents_active": { "used": 3, "limit": 20 },
    "fleets_active": { "used": 1, "limit": 10 },
    "voice_minutes": { "used": 45, "limit": 500 },
    "storage_mb": { "used": 128, "limit": 5120 }
  }
}</code></pre>
        </div>

        <h3>GET /usage/history — Historical Records</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /usage/history</div>
        <table class="doc-params">
            <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>start_date</code></td><td>string</td><td>No</td><td>ISO date (e.g. 2026-03-01)</td></tr>
                <tr><td><code>end_date</code></td><td>string</td><td>No</td><td>ISO date</td></tr>
                <tr><td><code>resource_type</code></td><td>string</td><td>No</td><td>Filter by type (tools, agents, chat, voice)</td></tr>
            </tbody>
        </table>

        <!-- RATE LIMITS -->
        <h2 id="rate-limits"><i class="fas fa-tachometer-alt"></i> Rate Limits</h2>
        <p>Rate limits are enforced per-key using a 1-minute sliding window. Headers are included on every response.</p>

        <div class="doc-rate-limit-table">
            <div class="doc-rate-card">
                <div class="rate-tier">Free</div>
                <div class="rate-value">10</div>
                <div class="rate-label">req/min</div>
            </div>
            <div class="doc-rate-card">
                <div class="rate-tier">Starter</div>
                <div class="rate-value">30</div>
                <div class="rate-label">req/min</div>
            </div>
            <div class="doc-rate-card">
                <div class="rate-tier">Professional</div>
                <div class="rate-value">60</div>
                <div class="rate-label">req/min</div>
            </div>
            <div class="doc-rate-card">
                <div class="rate-tier">Enterprise</div>
                <div class="rate-value">200</div>
                <div class="rate-label">req/min</div>
            </div>
        </div>

        <h4>Hourly &amp; Daily Limits</h4>
        <table class="doc-params">
            <thead><tr><th>Tier</th><th>Per Minute</th><th>Per Hour</th><th>Per Day</th></tr></thead>
            <tbody>
                <tr><td>Free</td><td>10</td><td>100</td><td>500</td></tr>
                <tr><td>Starter</td><td>30</td><td>500</td><td>5,000</td></tr>
                <tr><td>Professional</td><td>60</td><td>2,000</td><td>20,000</td></tr>
                <tr><td>Enterprise</td><td>200</td><td>10,000</td><td>100,000</td></tr>
            </tbody>
        </table>

        <h4>Rate Limit Headers</h4>
        <table class="doc-params">
            <thead><tr><th>Header</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>X-RateLimit-Limit</code></td><td>Maximum requests per minute for your tier</td></tr>
                <tr><td><code>X-RateLimit-Remaining</code></td><td>Requests remaining in the current window</td></tr>
                <tr><td><code>X-RateLimit-Reset</code></td><td>Unix timestamp when the window resets</td></tr>
                <tr><td><code>Retry-After</code></td><td>Seconds to wait (only on 429 responses)</td></tr>
            </tbody>
        </table>

        <div class="doc-info tip">
            <i class="fas fa-lightbulb"></i>
            <div><strong>Best Practice:</strong> All official SDKs automatically handle rate limiting with exponential backoff. If making raw HTTP requests, implement retry logic on 429 responses.</div>
        </div>

        <!-- ERROR CODES -->
        <h2 id="errors"><i class="fas fa-exclamation-circle"></i> Error Codes</h2>
        <p>All API errors return a consistent JSON envelope:</p>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">JSON</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "error": {
    "code": "validation_error",
    "message": "Missing required fields: agent_name",
    "status": 400
  }
}</code></pre>
        </div>

        <table class="doc-params">
            <thead><tr><th>Status</th><th>Code</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>400</code></td><td><code>validation_error</code></td><td>Missing or invalid parameters</td></tr>
                <tr><td><code>401</code></td><td><code>auth_required</code></td><td>Missing or invalid API key / OAuth token</td></tr>
                <tr><td><code>403</code></td><td><code>insufficient_scope</code></td><td>Token lacks required scopes</td></tr>
                <tr><td><code>404</code></td><td><code>resource_not_found</code></td><td>Resource or endpoint not found</td></tr>
                <tr><td><code>405</code></td><td><code>method_not_allowed</code></td><td>HTTP method not supported for this endpoint</td></tr>
                <tr><td><code>429</code></td><td><code>rate_limit_exceeded</code></td><td>Too many requests — check <code>Retry-After</code></td></tr>
                <tr><td><code>500</code></td><td><code>internal_error</code></td><td>Server error — contact support with <code>X-Request-ID</code></td></tr>
                <tr><td><code>503</code></td><td><code>service_unavailable</code></td><td>Service temporarily unavailable</td></tr>
            </tbody>
        </table>

        <div class="doc-sdk-tabs" data-group="errors-sdk">
            <button class="doc-sdk-tab active" onclick="showSdkPanel(this,'errors-sdk')">Node.js</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'errors-sdk')">Python</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'errors-sdk')">PHP</button>
        </div>
        <div class="doc-sdk-panel active" data-group="errors-sdk">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-typescript">import { AuthError, RateLimitError, NotFoundError } from '@alfredai/sdk';

try {
  await alfred.tools.execute('some_tool', { args: {} });
} catch (err) {
  if (err instanceof AuthError) console.error('Auth failed');
  if (err instanceof RateLimitError) console.error('Retry after ' + err.retryAfter + 's');
  if (err instanceof NotFoundError) console.error('Not found');
}</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="errors-sdk">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-python">from alfred_sdk import AuthenticationError, RateLimitError, NotFoundError

try:
    client.tools.execute("some_tool", args={})
except AuthenticationError:
    print("Auth failed")
except RateLimitError as e:
    print(f"Retry in {e.retry_after}s")
except NotFoundError:
    print("Not found")</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="errors-sdk">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-php">use AlfredAI\Exceptions\{AuthException, RateLimitException, NotFoundException};

try {
    $alfred->tools->execute('some_tool', []);
} catch (AuthException $e) {
    echo "Auth failed";
} catch (RateLimitException $e) {
    echo "Retry in " . $e->retryAfter . "s";
} catch (NotFoundException $e) {
    echo "Not found";
}</code></pre>
            </div>
        </div>

        <!-- WEBHOOKS -->
        <h2 id="webhooks"><i class="fas fa-broadcast-tower"></i> Webhooks</h2>
        <p>Alfred can send real-time event notifications to your application via webhooks. Configure webhook URLs in the Developer Portal.</p>

        <h3>Webhook Delivery Format</h3>
        <p>Each webhook delivery includes these headers:</p>
        <table class="doc-params">
            <thead><tr><th>Header</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>X-Webhook-Signature</code></td><td><code>sha256=&lt;HMAC hex digest&gt;</code> — HMAC-SHA256 of the payload using your webhook secret</td></tr>
                <tr><td><code>X-Webhook-Event</code></td><td>Event name (e.g. <code>agent.deployed</code>)</td></tr>
                <tr><td><code>X-Webhook-Delivery</code></td><td>Unique delivery ID</td></tr>
                <tr><td><code>User-Agent</code></td><td><code>Alfred-Webhooks/1.0</code></td></tr>
            </tbody>
        </table>

        <h4>Payload Format</h4>
        <div class="doc-code-wrap">
            <div class="doc-code-header"><span class="doc-code-lang">JSON</span><button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button></div>
            <pre class="doc-code-block"><code class="language-json">{
  "id": "d4e5f6a7b8c9d0e1",
  "event": "agent.deployed",
  "timestamp": "2026-03-04T15:30:00+00:00",
  "data": {
    "agent_id": 42,
    "agent_name": "Support Bot",
    "fleet_id": 7,
    "status": "working"
  }
}</code></pre>
        </div>

        <h3>Available Events</h3>
        <table class="doc-params">
            <thead><tr><th>Event</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>agent.created</code></td><td>Agent was created</td></tr>
                <tr><td><code>agent.deployed</code></td><td>Agent was deployed / started</td></tr>
                <tr><td><code>agent.completed</code></td><td>Agent completed a task</td></tr>
                <tr><td><code>agent.error</code></td><td>Agent encountered an error</td></tr>
                <tr><td><code>fleet.created</code></td><td>Fleet was created</td></tr>
                <tr><td><code>fleet.deployed</code></td><td>Fleet deployment started</td></tr>
                <tr><td><code>tool.executed</code></td><td>Tool was executed</td></tr>
                <tr><td><code>voice.call.started</code></td><td>Voice call started</td></tr>
                <tr><td><code>voice.call.ended</code></td><td>Voice call ended</td></tr>
                <tr><td><code>voice.room.created</code></td><td>Voice room created</td></tr>
                <tr><td><code>marketplace.installed</code></td><td>Marketplace item installed</td></tr>
                <tr><td><code>usage.threshold</code></td><td>Usage threshold reached (80%, 100%)</td></tr>
            </tbody>
        </table>

        <h3>Signature Verification</h3>
        <p>Always verify webhook signatures to ensure requests come from Alfred. Use constant-time comparison.</p>

        <div class="doc-sdk-tabs" data-group="webhook-verify">
            <button class="doc-sdk-tab active" onclick="showSdkPanel(this,'webhook-verify')">Node.js</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'webhook-verify')">Python</button>
            <button class="doc-sdk-tab" onclick="showSdkPanel(this,'webhook-verify')">PHP</button>
        </div>
        <div class="doc-sdk-panel active" data-group="webhook-verify">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-typescript">// Express.js example
app.post('/webhook', express.text({ type: '*/*' }), (req, res) => {
  const event = alfred.webhooks.verifyAndParse(
    req.body,
    req.headers['x-webhook-signature'],
    process.env.WEBHOOK_SECRET,
  );
  console.log(event.event, event.data);
  res.sendStatus(200);
});</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="webhook-verify">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-python"># Flask example
@app.post("/webhook")
def handle_webhook():
    event = client.webhooks.verify_and_parse(
        request.data.decode(),
        request.headers["X-Webhook-Signature"],
        WEBHOOK_SECRET,
    )
    print(f"{event['event']}: {event['data']}")
    return "", 200</code></pre>
            </div>
        </div>
        <div class="doc-sdk-panel" data-group="webhook-verify">
            <div class="doc-code-wrap" style="margin-top:0;border-radius:0 0 var(--doc-radius) var(--doc-radius);">
                <pre class="doc-code-block"><code class="language-php">$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

$verifier = new \AlfredAI\WebhookVerifier();
$event = $verifier->verifyAndParse($payload, $signature, $webhookSecret);
echo "Event: " . $event['event'];</code></pre>
            </div>
        </div>

        <!-- SDKs -->
        <h2 id="sdks"><i class="fas fa-cube"></i> Official SDKs</h2>
        <p>Get started faster with official client libraries that handle authentication, retries, and error handling.</p>

        <table class="doc-params">
            <thead><tr><th>Language</th><th>Package</th><th>Install</th></tr></thead>
            <tbody>
                <tr><td><i class="fab fa-node-js" style="color:#68a063"></i> Node.js / TypeScript</td><td><code>@alfredai/sdk</code></td><td><code>npm install @alfredai/sdk</code></td></tr>
                <tr><td><i class="fab fa-python" style="color:#3776ab"></i> Python</td><td><code>alfred-ai-sdk</code></td><td><code>pip install alfred-ai-sdk</code></td></tr>
                <tr><td><i class="fab fa-php" style="color:#777bb4"></i> PHP</td><td><code>alfredai/sdk</code></td><td><code>composer require alfredai/sdk</code></td></tr>
            </tbody>
        </table>

        <p>View all SDKs, examples, and features on the <a href="/sdks">SDKs page</a>.</p>

        <div class="doc-info note">
            <i class="fas fa-headset"></i>
            <div><strong>Need Help?</strong> If you encounter issues, contact <strong>support@gositeme.com</strong> with the <code>X-Request-ID</code> from the response headers.</div>
        </div>

    </main>
</div>

<script src="/assets/js/vendor/prism.min.js"></script>
<script src="/assets/js/vendor/prism-json.min.js"></script>
<script src="/assets/js/vendor/prism-typescript.min.js"></script>
<script src="/assets/js/vendor/prism-python.min.js"></script>
<script src="/assets/js/vendor/prism-php.min.js"></script>
<script src="/assets/js/vendor/prism-bash.min.js"></script>

<script>
function copyCode(btn) {
    const codeBlock = btn.closest('.doc-code-wrap').querySelector('.doc-code-block code');
    navigator.clipboard.writeText(codeBlock.textContent).then(() => {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 2000);
    });
}

function showSdkPanel(btn, group) {
    const tabs = btn.parentElement.querySelectorAll('.doc-sdk-tab');
    const tabIndex = Array.from(tabs).indexOf(btn);
    tabs.forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const panels = document.querySelectorAll('.doc-sdk-panel[data-group="' + group + '"]');
    panels.forEach(function(p, i) {
        if (i === tabIndex) p.classList.add('active');
        else p.classList.remove('active');
    });
}

// Sidebar toggle
var sidebarToggle = document.getElementById('docSidebarToggle');
var sidebar = document.getElementById('docSidebar');
if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        sidebarToggle.innerHTML = sidebar.classList.contains('open') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
    });
}

// Smooth scroll
document.querySelectorAll('.doc-sidebar-sub a[href^="#"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var target = document.querySelector(link.getAttribute('href'));
        if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); history.pushState(null, '', link.getAttribute('href')); }
    });
});

// Highlight active sidebar link on scroll
var sections = document.querySelectorAll('.doc-main h2[id]');
var sidebarLinks = document.querySelectorAll('.doc-sidebar-sub a[href^="#"]');
window.addEventListener('scroll', function() {
    var current = '';
    sections.forEach(function(s) { if (window.scrollY >= s.offsetTop - 150) current = s.id; });
    sidebarLinks.forEach(function(l) { l.style.color = l.getAttribute('href') === '#' + current ? 'var(--doc-accent-light)' : ''; });
});

// Highlight code
document.addEventListener('DOMContentLoaded', function() { if (typeof Prism !== 'undefined') Prism.highlightAll(); });
</script>

<?php include __DIR__ . '/../includes/site-footer.inc.php'; ?>
