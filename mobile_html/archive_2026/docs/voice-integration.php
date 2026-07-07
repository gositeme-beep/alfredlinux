<?php
$page_title = 'Voice Integration — Alfred AI Documentation | GoSiteMe';
$page_description = 'Integrate Alfred AI voice into your application. Voice API setup, webhook configuration, phone number provisioning, voice engines, and conference room API.';
$page_canonical = 'https://gositeme.com/docs/voice-integration';
$page_og_title = 'Alfred AI Voice Integration Guide — Voice API, Webhooks & Voice Engines';
$page_og_description = 'Complete guide to integrating Alfred voice into your app. Voice API webhooks, phone provisioning, voice agent config, and supported engines: Kokoro, Orpheus, Cartesia, ElevenLabs.';
include __DIR__ . '/../includes/lang.php';
include __DIR__ . '/../includes/site-header.inc.php';
?>

<!-- Schema.org TechArticle markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "Alfred AI Voice Integration Guide",
  "description": "How to integrate Alfred AI voice into your application — Voice API setup, webhook configuration, voice engines, and conference room API.",
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
  "mainEntityOfPage": "https://gositeme.com/docs/voice-integration",
  "proficiencyLevel": "Beginner"
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

.doc-breadcrumbs {
    max-width: 1400px;
    margin: 0 auto;
    padding: 100px 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
}
.doc-breadcrumbs a { color: var(--doc-text-muted); text-decoration: none; transition: color 0.2s; }
.doc-breadcrumbs a:hover { color: var(--doc-accent-light); }
.doc-breadcrumbs span { color: var(--doc-text-muted); }
.doc-breadcrumbs .current { color: var(--doc-text); font-weight: 600; }

.doc-layout {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px 80px;
    gap: 40px;
}
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

.doc-sidebar-sub { list-style: none; padding: 0 0 0 30px; margin: 4px 0 8px; }
.doc-sidebar-sub li { margin-bottom: 1px; }
.doc-sidebar-sub a {
    padding: 6px 12px;
    font-size: 0.82rem;
    color: var(--doc-text-muted);
    display: block;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s;
}
.doc-sidebar-sub a:hover { color: var(--doc-accent-light); background: var(--doc-surface); }

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

/* Voice engine cards */
.voice-engine-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin: 20px 0;
}
.voice-engine-card {
    background: var(--doc-surface);
    border: 1px solid var(--doc-border);
    border-radius: var(--doc-radius);
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
}
.voice-engine-card:hover {
    border-color: var(--doc-accent);
    transform: translateY(-2px);
}
.voice-engine-card .engine-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 6px;
}
.voice-engine-card .engine-desc {
    color: var(--doc-text-muted);
    font-size: 0.82rem;
    line-height: 1.5;
    margin-bottom: 10px;
}
.voice-engine-card .engine-tag {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.tag-fast { background: rgba(0,184,148,0.15); color: var(--doc-green); }
.tag-quality { background: rgba(108,92,231,0.15); color: var(--doc-accent-light); }
.tag-premium { background: rgba(253,203,110,0.15); color: var(--doc-orange); }
.tag-expressive { background: rgba(253,121,168,0.15); color: var(--doc-pink); }

/* Architecture diagram */
.voice-arch {
    background: var(--doc-surface);
    border: 1px solid var(--doc-border);
    border-radius: var(--doc-radius);
    padding: 24px;
    margin: 20px 0;
    text-align: center;
}
.voice-arch-flow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin: 16px 0;
}
.voice-arch-node {
    background: var(--doc-surface-2);
    border: 1px solid var(--doc-border);
    border-radius: 8px;
    padding: 12px 18px;
    color: var(--doc-text);
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
}
.voice-arch-node.highlight {
    background: rgba(108,92,231,0.15);
    border-color: var(--doc-accent);
    color: var(--doc-accent-light);
}
.voice-arch-arrow {
    color: var(--doc-text-muted);
    font-size: 1.2rem;
}

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
    .voice-engine-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 500px) {
    .voice-engine-grid { grid-template-columns: 1fr; }
}
</style>

<!-- Breadcrumbs -->
<div class="doc-breadcrumbs">
    <a href="/docs/">Docs</a>
    <span>›</span>
    <span class="current">Voice Integration</span>
</div>

<!-- Layout -->
<div class="doc-layout">
    <!-- Sidebar -->
    <nav class="doc-sidebar" id="docSidebar">
        <ul class="doc-sidebar-nav">
            <li><a href="/docs/"><i class="fas fa-home"></i> Docs Home</a></li>
            <li><a href="/docs/getting-started"><i class="fas fa-rocket"></i> Getting Started</a></li>
            <li><a href="/docs/api-reference"><i class="fas fa-plug"></i> API Reference</a></li>
            <li>
                <a href="/docs/voice-integration" class="active"><i class="fas fa-microphone"></i> Voice Integration</a>
                <ul class="doc-sidebar-sub">
                    <li><a href="#overview">Overview</a></li>
                    <li><a href="#voice-api">Voice API Setup</a></li>
                    <li><a href="#webhooks">Webhook Config</a></li>
                    <li><a href="#phone">Phone Numbers</a></li>
                    <li><a href="#voice-agents">Voice Agents</a></li>
                    <li><a href="#engines">Voice Engines</a></li>
                    <li><a href="#widget">Web Widget</a></li>
                    <li><a href="#conference">Conference Rooms</a></li>
                </ul>
            </li>
            <li><a href="/docs/tools-guide"><i class="fas fa-wrench"></i> Tools Guide</a></li>
        </ul>
    </nav>

    <!-- Mobile Toggle -->
    <button class="doc-sidebar-toggle" id="docSidebarToggle" aria-label="Toggle documentation menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="doc-main">
        <h1><i class="fas fa-microphone"></i> Voice Integration</h1>
        <p class="doc-subtitle">Integrate Alfred's voice-first AI into your applications. Support for phone calls, web widgets, webhooks, and multiple voice engines.</p>

        <!-- ==================== OVERVIEW ==================== -->
        <h2 id="overview"><i class="fas fa-layer-group"></i> Architecture Overview</h2>
        <p>Alfred Voice uses a real-time voice transport layer built into the platform. Calls come in via phone or web widget, get processed through the Alfred Voice API, and route to Alfred's tool engine for execution.</p>

        <div class="voice-arch">
            <p style="color: var(--doc-text); font-weight: 600; margin-bottom: 16px;">Voice Call Flow</p>
            <div class="voice-arch-flow">
                <div class="voice-arch-node">User (Phone/Web)</div>
                <div class="voice-arch-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="voice-arch-node">Voice Transport</div>
                <div class="voice-arch-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="voice-arch-node highlight">Alfred Webhook</div>
                <div class="voice-arch-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="voice-arch-node">Tool Engine (1,220+)</div>
                <div class="voice-arch-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="voice-arch-node">Voice Response</div>
            </div>
            <p style="color: var(--doc-text-muted); font-size: 0.82rem; margin-top: 12px;">STT → Alfred Processing → TTS, with real-time tool execution in the middle</p>
        </div>

        <h3>Key Components</h3>
        <ul>
            <li><strong>Alfred Voice API</strong> — Real-time voice transport and speech-to-text/text-to-speech pipeline</li>
            <li><strong>Webhook Server</strong> — Your endpoint that receives voice events and returns tool results</li>
            <li><strong>Tool Engine</strong> — Alfred's 1,220+ tools, executable via voice or API</li>
            <li><strong>Voice Engines</strong> — Kokoro, Orpheus, Cartesia Sonic, ElevenLabs for TTS output</li>
            <li><strong>Conference Rooms</strong> — Multi-agent voice collaboration spaces</li>
        </ul>

        <!-- ==================== VOICE API SETUP ==================== -->
        <h2 id="voice-api"><i class="fas fa-cog"></i> Voice API Setup</h2>
        <p>The Alfred Voice API handles the real-time voice connection between users and Alfred. You'll need to configure a voice assistant and point it to your Alfred webhook.</p>

        <h3>Step 1: Get Your Voice API Key</h3>
        <p>Navigate to your <a href="/developer-portal.php">Developer Portal</a> and generate a Voice API key from the dashboard.</p>

        <h3>Step 2: Create an Assistant</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>curl -X POST https://gositeme.com/api/voice-assistant \
  -H "Authorization: Bearer your_voice_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Alfred AI",
    "model": {
      "provider": "custom-llm",
      "url": "https://gositeme.com/api/vapi-webhook.php",
      "model": "alfred-v2"
    },
    "voice": {
      "provider": "cartesia",
      "voiceId": "sonic-english-male-1"
    },
    "firstMessage": "Hello! I am Alfred, your AI assistant. How can I help you today?",
    "serverUrl": "https://gositeme.com/api/vapi-webhook.php"
  }'</code></pre>
        </div>

        <h3>Step 3: Configure Server URL</h3>
        <p>Point your voice assistant's server URL to Alfred's webhook endpoint:</p>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/vapi-webhook.php</div>
        <p>This endpoint receives all voice events: call start, speech input, tool calls, and call end.</p>

        <div class="doc-info tip">
            <i class="fas fa-lightbulb"></i>
            <div><strong>Managed Setup:</strong> If you're using Alfred's hosted service, the Voice API is pre-configured. You only need manual setup when self-hosting or customizing the voice pipeline.</div>
        </div>

        <!-- ==================== WEBHOOKS ==================== -->
        <h2 id="webhooks"><i class="fas fa-exchange-alt"></i> Webhook Configuration</h2>
        <p>Alfred exposes multiple webhook endpoints for voice event handling:</p>

        <table class="doc-params">
            <thead><tr><th>Endpoint</th><th>Purpose</th></tr></thead>
            <tbody>
                <tr><td><code>/api/vapi-webhook.php</code></td><td>Main voice event handler (call events, messages, tool calls)</td></tr>
                <tr><td><code>/api/vapi-callback.php</code></td><td>Callback handler for async tool results</td></tr>
                <tr><td><code>/api/vapi-tools.php</code></td><td>Tool registration endpoint for voice function calling</td></tr>
                <tr><td><code>/api/vapi-auth.php</code></td><td>Voice session authentication</td></tr>
                <tr><td><code>/api/vapi-outbound.php</code></td><td>Outbound call initiation</td></tr>
            </tbody>
        </table>

        <h3>Webhook Event Types</h3>
        <p>The main webhook receives events in this format:</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JSON</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>// Call started
{
  "message": {
    "type": "assistant-request",
    "call": {
      "id": "call_abc123",
      "phoneNumber": "+18334674836",
      "customer": { "number": "+15145551234" }
    }
  }
}

// Tool call request
{
  "message": {
    "type": "tool-calls",
    "toolCalls": [
      {
        "id": "tc_xyz",
        "type": "function",
        "function": {
          "name": "weather_lookup",
          "arguments": "{\"location\":\"Montreal\"}"
        }
      }
    ]
  }
}

// Call ended
{
  "message": {
    "type": "end-of-call-report",
    "call": { "id": "call_abc123" },
    "summary": "User asked about weather in Montreal",
    "duration": 45
  }
}</code></pre>
        </div>

        <h3>Handling Webhook Events (PHP)</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">PHP</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>&lt;?php
// vapi-webhook.php - Handle incoming voice events
$payload = json_decode(file_get_contents('php://input'), true);
$type = $payload['message']['type'] ?? '';

switch ($type) {
    case 'assistant-request':
        // New call - return assistant configuration
        echo json_encode([
            'assistant' => [
                'firstMessage' => 'Hello! I am Alfred. How can I help?',
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4',
                    'systemMessage' => 'You are Alfred, an AI assistant with 1,220+ tools...'
                ],
                'voice' => ['provider' => 'cartesia', 'voiceId' => 'sonic-english-male-1']
            ]
        ]);
        break;

    case 'tool-calls':
        // Execute tool calls
        $results = [];
        foreach ($payload['message']['toolCalls'] as $tc) {
            $args = json_decode($tc['function']['arguments'], true);
            $result = executeAlfredTool($tc['function']['name'], $args);
            $results[] = [
                'toolCallId' => $tc['id'],
                'result' => json_encode($result)
            ];
        }
        echo json_encode(['results' => $results]);
        break;

    case 'end-of-call-report':
        // Log call summary
        logCall($payload['message']);
        echo json_encode(['success' => true]);
        break;
}
?&gt;</code></pre>
        </div>

        <!-- ==================== PHONE NUMBERS ==================== -->
        <h2 id="phone"><i class="fas fa-phone-alt"></i> Phone Number Provisioning</h2>
        <p>Alfred's primary phone number is <strong>1-833-GOSITEME (1-833-467-4836)</strong>. For custom phone numbers, provision through the Voice API or the voice management endpoint:</p>

        <h3>Provision a Number</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/voice-manage.php</div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const number = await fetch('https://gositeme.com/api/voice-manage.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'provision_number',
    area_code: '514',
    country: 'CA',
    agent_id: 'agent_xyz'
  })
}).then(r => r.json());

console.log(number);
// { phone_number: "+15141234567", status: "active", agent: "agent_xyz" }</code></pre>
        </div>

        <h3>List Your Numbers</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>curl -H "Authorization: Bearer sess_abc123..." \
  "https://gositeme.com/api/voice-manage.php?action=list_numbers"

# {
#   "numbers": [
#     { "number": "+15141234567", "agent": "agent_xyz", "calls_today": 23, "status": "active" }
#   ]
# }</code></pre>
        </div>

        <h3>Initiate Outbound Call</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/vapi-outbound.php</div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

call = requests.post('https://gositeme.com/api/vapi-outbound.php',
    headers={'Authorization': 'Bearer sess_abc123...'},
    json={
        'action': 'call',
        'to': '+15145551234',
        'from_number': '+15141234567',
        'agent_id': 'agent_xyz',
        'context': 'Follow up on support ticket #1234'
    }
).json()

print(f"Call initiated: {call['call_id']}")</code></pre>
        </div>

        <div class="doc-info warn">
            <i class="fas fa-exclamation-triangle"></i>
            <div><strong>Compliance:</strong> Outbound calls must comply with TCPA/CRTC regulations. Ensure you have consent before initiating automated calls. Enterprise plan required for outbound calling.</div>
        </div>

        <!-- ==================== VOICE AGENTS ==================== -->
        <h2 id="voice-agents"><i class="fas fa-user-cog"></i> Voice Agent Configuration</h2>
        <p>Voice agents are AI personalities that interact with callers. Configure their personality, tools, knowledge base, and voice settings.</p>

        <h3>Create a Voice Agent</h3>
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
    name: 'Receptionist',
    personality: 'Professional, warm receptionist for a law firm. Always ask for caller name and purpose.',
    tools: ['schedule_appointment', 'lookup_client', 'transfer_call', 'take_message'],
    knowledge_base: 'kb_firm_info',
    voice_enabled: true,
    voice_engine: 'cartesia',
    voice_config: {
      voice_id: 'sonic-english-female-1',
      speed: 1.0,
      emotion: 'friendly'
    },
    call_settings: {
      max_duration: 600,
      silence_timeout: 10,
      greeting: 'Thank you for calling Smith & Associates. How may I direct your call?'
    }
  })
}).then(r => r.json());</code></pre>
        </div>

        <h3>Agent Configuration Reference</h3>
        <table class="doc-params">
            <thead><tr><th>Field</th><th>Type</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>personality</code></td><td>string</td><td>System prompt defining agent behavior and tone</td></tr>
                <tr><td><code>tools</code></td><td>array</td><td>Tool names the agent can invoke during calls</td></tr>
                <tr><td><code>knowledge_base</code></td><td>string</td><td>Knowledge base ID for RAG-powered responses</td></tr>
                <tr><td><code>voice_engine</code></td><td>string</td><td>TTS engine: <code>kokoro</code>, <code>orpheus</code>, <code>cartesia</code>, <code>elevenlabs</code></td></tr>
                <tr><td><code>voice_config.voice_id</code></td><td>string</td><td>Specific voice ID from chosen engine</td></tr>
                <tr><td><code>voice_config.speed</code></td><td>float</td><td>Speech speed multiplier (0.5–2.0, default: 1.0)</td></tr>
                <tr><td><code>call_settings.max_duration</code></td><td>integer</td><td>Max call duration in seconds (default: 600)</td></tr>
                <tr><td><code>call_settings.silence_timeout</code></td><td>integer</td><td>Hang up after N seconds of silence (default: 10)</td></tr>
                <tr><td><code>call_settings.greeting</code></td><td>string</td><td>First message spoken when call connects</td></tr>
            </tbody>
        </table>

        <!-- ==================== VOICE ENGINES ==================== -->
        <h2 id="engines"><i class="fas fa-volume-up"></i> Supported Voice Engines</h2>
        <p>Alfred supports multiple text-to-speech engines. Choose based on your needs for latency, quality, and expressiveness.</p>

        <div class="voice-engine-grid">
            <div class="voice-engine-card">
                <div class="engine-name">Kokoro</div>
                <div class="engine-desc">Ultra-fast, open-source TTS. Great for real-time interactions with minimal latency.</div>
                <span class="engine-tag tag-fast">Fastest</span>
            </div>
            <div class="voice-engine-card">
                <div class="engine-name">Orpheus</div>
                <div class="engine-desc">High-quality open-source voice with natural prosody and emotional range.</div>
                <span class="engine-tag tag-expressive">Expressive</span>
            </div>
            <div class="voice-engine-card">
                <div class="engine-name">Cartesia Sonic</div>
                <div class="engine-desc">Low-latency commercial TTS with excellent voice cloning and consistent quality.</div>
                <span class="engine-tag tag-quality">Best Balance</span>
            </div>
            <div class="voice-engine-card">
                <div class="engine-name">ElevenLabs</div>
                <div class="engine-desc">Premium voice synthesis with the most natural-sounding output. Voice cloning available.</div>
                <span class="engine-tag tag-premium">Premium</span>
            </div>
        </div>

        <h3>Engine Comparison</h3>
        <table class="doc-params">
            <thead><tr><th>Engine</th><th>Latency</th><th>Quality</th><th>Languages</th><th>Plan Required</th></tr></thead>
            <tbody>
                <tr><td><code>kokoro</code></td><td>~80ms</td><td>Good</td><td>EN, FR, ES, DE, JA</td><td>All plans</td></tr>
                <tr><td><code>orpheus</code></td><td>~150ms</td><td>Very Good</td><td>EN, FR, ES</td><td>All plans</td></tr>
                <tr><td><code>cartesia</code></td><td>~120ms</td><td>Excellent</td><td>EN, FR, ES, DE, PT, JA, KO</td><td>Professional+</td></tr>
                <tr><td><code>elevenlabs</code></td><td>~200ms</td><td>Premium</td><td>29 languages</td><td>Enterprise</td></tr>
            </tbody>
        </table>

        <h3>Setting a Voice Engine</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>// Update agent voice engine
await fetch('https://gositeme.com/api/fleet.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'update_agent',
    agent_id: 'agent_xyz',
    voice_engine: 'cartesia',
    voice_config: {
      voice_id: 'sonic-english-female-1',
      speed: 1.1,
      emotion: 'professional'
    }
  })
});</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

requests.post('https://gositeme.com/api/fleet.php',
    headers={'Authorization': 'Bearer sess_abc123...'},
    json={
        'action': 'update_agent',
        'agent_id': 'agent_xyz',
        'voice_engine': 'elevenlabs',
        'voice_config': {
            'voice_id': 'rachel',
            'stability': 0.7,
            'similarity_boost': 0.8
        }
    }
)</code></pre>
        </div>

        <!-- ==================== WEB WIDGET ==================== -->
        <h2 id="widget"><i class="fas fa-comments"></i> Web Voice Widget</h2>
        <p>Embed Alfred's voice widget in your website for browser-based voice interaction. The widget handles microphone access, speech-to-text, and real-time responses.</p>

        <h3>Quick Embed</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">HTML</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>&lt;!-- Add to your page's &lt;body&gt; --&gt;
&lt;script src="https://gositeme.com/assets/js/alfred-voice-widget.js"&gt;&lt;/script&gt;
&lt;script&gt;
  AlfredVoice.init({
    token: 'sess_abc123...',       // Your session token
    position: 'bottom-right',       // bottom-right, bottom-left, top-right, top-left
    theme: 'dark',                  // dark or light
    accent: '#6c5ce7',              // Widget accent color
    greeting: 'Hi! How can I help you today?',
    agent_id: 'agent_xyz',          // Optional: use specific agent
    voice_engine: 'kokoro',         // TTS engine
    tools: ['weather_lookup', 'dns_lookup', 'summarize_text'],  // Allowed tools
    onReady: () => console.log('Widget loaded'),
    onMessage: (msg) => console.log('Alfred said:', msg.text),
    onError: (err) => console.error('Voice error:', err)
  });
&lt;/script&gt;</code></pre>
        </div>

        <h3>Widget API</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>// Programmatic control
AlfredVoice.open();              // Open the widget
AlfredVoice.close();             // Close the widget
AlfredVoice.startListening();    // Start microphone
AlfredVoice.stopListening();     // Stop microphone
AlfredVoice.speak('Hello!');     // Text-to-speech
AlfredVoice.sendText('What is the weather in Montreal?');  // Send text input
AlfredVoice.destroy();           // Remove widget from DOM

// Event listeners
AlfredVoice.on('callStart', (data) => { /* call connected */ });
AlfredVoice.on('callEnd', (data) => { /* call ended */ });
AlfredVoice.on('transcript', (text) => { /* user speech recognized */ });
AlfredVoice.on('toolCall', (tool) => { /* tool being executed */ });</code></pre>
        </div>

        <h3>Custom Styling</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">CSS</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>/* Override widget styles */
.alfred-voice-widget {
    --av-bg: #12121e;
    --av-accent: #6c5ce7;
    --av-text: #e8e8f0;
    --av-radius: 16px;
    --av-shadow: 0 8px 32px rgba(0,0,0,0.4);
}

.alfred-voice-btn {
    width: 64px;
    height: 64px;
    border-radius: 50%;
}

.alfred-voice-panel {
    width: 380px;
    max-height: 500px;
}</code></pre>
        </div>

        <!-- ==================== CONFERENCE ROOMS ==================== -->
        <h2 id="conference"><i class="fas fa-video"></i> Conference Room API</h2>
        <p>Conference rooms allow multiple AI agents and human participants to collaborate in real-time voice sessions. Useful for multi-agent workflows, team meetings with AI support, and complex problem-solving.</p>

        <h3>Create a Room</h3>
        <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/fleet.php</div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const room = await fetch('https://gositeme.com/api/fleet.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'create_room',
    name: 'Strategy Session',
    agents: ['agent_analyst', 'agent_researcher', 'agent_writer'],
    max_participants: 10,
    recording: true,
    auto_transcribe: true
  })
}).then(r => r.json());

console.log(room);
// {
//   id: "room_abc",
//   name: "Strategy Session",
//   join_url: "https://gositeme.com/conference-room.php?id=room_abc",
//   agents: 3,
//   recording: true
// }</code></pre>
        </div>

        <h3>Join a Room</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>// Via web browser
window.location.href = room.join_url;

// Via API (add participant)
await fetch('https://gositeme.com/api/fleet.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'join_room',
    room_id: 'room_abc',
    participant: {
      name: 'John Doe',
      role: 'moderator'
    }
  })
});</code></pre>
        </div>

        <h3>Room Management</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code># Get room status
curl -H "Authorization: Bearer sess_abc123..." \
  "https://gositeme.com/api/fleet.php?action=room_status&room_id=room_abc"

# End room and get transcript
curl -X POST https://gositeme.com/api/fleet.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer sess_abc123..." \
  -d '{"action":"end_room","room_id":"room_abc"}'

# Response includes transcript and recording URL
# {
#   "transcript": "...",
#   "recording_url": "https://gositeme.com/recordings/room_abc.mp3",
#   "duration": 1847,
#   "participants": 5,
#   "tools_used": 12
# }</code></pre>
        </div>

        <div class="doc-info tip">
            <i class="fas fa-lightbulb"></i>
            <div><strong>Try It Live:</strong> Visit <a href="/conference-room.php">gositeme.com/conference-room.php</a> to create a conference room with multiple AI agents right now.</div>
        </div>

        <div class="doc-info note">
            <i class="fas fa-headset"></i>
            <div><strong>Need Help?</strong> For voice integration support, contact <strong>support@gositeme.com</strong> or call <strong>1-833-GOSITEME</strong>.</div>
        </div>

    </main>
</div>

<script>
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

document.querySelectorAll('.doc-sidebar-sub a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            history.pushState(null, '', link.getAttribute('href'));
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/site-footer.inc.php'; ?>
