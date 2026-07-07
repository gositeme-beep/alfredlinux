<?php
$page_title = 'Alfred AI SDKs — Official Client Libraries | GoSiteMe';
$page_description = 'Integrate Alfred AI into your application in minutes with official SDKs for Node.js, Python, and PHP. Type-safe, auto-retry, streaming support, and webhook verification.';
$page_canonical = 'https://root.com/sdks';
$page_og_title = 'Alfred AI SDKs — Node.js, Python, PHP Client Libraries';
$page_og_description = 'Official SDKs for the Alfred AI platform. Type-safe clients for 13,000+ tools via 6 providers, agents, fleets, voice, robotics, and chat. Get started in under 5 minutes.';
include __DIR__ . '/includes/lang.php';
include __DIR__ . '/includes/site-header.inc.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareSourceCode",
  "name": "Alfred AI SDKs",
  "description": "Official client libraries for the Alfred AI REST API",
  "codeRepository": "https://alfredlinux.com/forge/explore/repos",
  "programmingLanguage": ["TypeScript", "Python", "PHP"],
  "author": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "url": "https://root.com"
  }
}
</script>

<style>
:root {
    --al-bg: #0a0a14;
    --al-surface: #12121e;
    --al-surface-2: #1a1a2e;
    --al-surface-3: #22223a;
    --al-border: rgba(255,255,255,0.08);
    --al-accent: #6c5ce7;
    --al-accent-light: #a29bfe;
    --al-green: #00b894;
    --al-blue: #0984e3;
    --al-orange: #fdcb6e;
    --al-fire: #e17055;
    --al-pink: #fd79a8;
    --al-cyan: #00cec9;
    --al-text: #e8e8f0;
    --al-text-muted: #8a8a9a;
    --al-radius: 12px;
}

/* ─── Hero ─── */
.sdk-hero {
    text-align: center;
    padding: 140px 20px 60px;
    max-width: 900px;
    margin: 0 auto;
}
.sdk-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.4rem);
    font-weight: 800;
    background: linear-gradient(135deg, #fff 30%, var(--al-accent-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 16px;
}
.sdk-hero p {
    font-size: 1.2rem;
    color: var(--al-text-muted);
    max-width: 600px;
    margin: 0 auto 32px;
    line-height: 1.7;
}
.sdk-hero-badges {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.sdk-hero-badges span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: 20px;
    font-size: 0.82rem;
    color: var(--al-text-muted);
}
.sdk-hero-badges span i { color: var(--al-accent-light); }

/* ─── Quick Start Tabs ─── */
.sdk-quickstart {
    max-width: 900px;
    margin: 0 auto 80px;
    padding: 0 20px;
}
.sdk-quickstart h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin-bottom: 24px;
}
.sdk-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 0;
    background: var(--al-surface);
    border-radius: var(--al-radius) var(--al-radius) 0 0;
    border: 1px solid var(--al-border);
    border-bottom: none;
    overflow: hidden;
}
.sdk-tab {
    flex: 1;
    padding: 14px 20px;
    background: none;
    border: none;
    color: var(--al-text-muted);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.sdk-tab:hover { color: var(--al-text); background: var(--al-surface-2); }
.sdk-tab.active { color: var(--al-accent-light); background: var(--al-surface-2); border-bottom: 2px solid var(--al-accent); }
.sdk-tab img { width: 20px; height: 20px; }

.sdk-tab-content {
    display: none;
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-top: none;
    border-radius: 0 0 var(--al-radius) var(--al-radius);
    overflow: hidden;
}
.sdk-tab-content.active { display: block; }

.sdk-install-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: var(--al-surface-2);
    border-bottom: 1px solid var(--al-border);
}
.sdk-install-cmd {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 0.9rem;
    color: var(--al-green);
}
.sdk-copy-btn {
    background: none;
    border: 1px solid var(--al-border);
    color: var(--al-text-muted);
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}
.sdk-copy-btn:hover { color: var(--al-text); border-color: var(--al-accent); }
.sdk-copy-btn.copied { color: var(--al-green); border-color: var(--al-green); }

.sdk-code-block {
    padding: 20px;
    overflow-x: auto;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 0.85rem;
    line-height: 1.7;
    color: var(--al-text);
    margin: 0;
    background: var(--al-surface);
}
.sdk-code-block code { background: none; padding: 0; }

/* ─── Feature Grid ─── */
.sdk-features {
    max-width: 1100px;
    margin: 0 auto 80px;
    padding: 0 20px;
}
.sdk-features h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin-bottom: 40px;
}
.sdk-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.sdk-feature-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 28px;
    transition: all 0.3s;
}
.sdk-feature-card:hover {
    border-color: rgba(108,92,231,0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(108,92,231,0.1);
}
.sdk-feature-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 16px;
}
.sdk-feature-card h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 8px;
}
.sdk-feature-card p {
    color: var(--al-text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
    margin: 0;
}

/* ─── Examples Section ─── */
.sdk-examples {
    max-width: 900px;
    margin: 0 auto 80px;
    padding: 0 20px;
}
.sdk-examples h2 {
    font-family: 'Space Grotesky', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin-bottom: 40px;
}
.sdk-example {
    margin-bottom: 32px;
}
.sdk-example h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--al-accent-light);
    margin-bottom: 12px;
}
.sdk-example-code {
    position: relative;
    border-radius: var(--al-radius);
    overflow: hidden;
    border: 1px solid var(--al-border);
}
.sdk-example-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    background: var(--al-surface-3);
    border-bottom: 1px solid var(--al-border);
}
.sdk-example-lang {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--al-accent-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ─── SDK Cards ─── */
.sdk-cards {
    max-width: 1100px;
    margin: 0 auto 80px;
    padding: 0 20px;
}
.sdk-cards h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin-bottom: 40px;
}
.sdk-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}
.sdk-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 32px;
    transition: all 0.3s;
}
.sdk-card:hover {
    border-color: rgba(108,92,231,0.3);
    box-shadow: 0 8px 30px rgba(108,92,231,0.1);
}
.sdk-card-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.sdk-card-title i { font-size: 1.6rem; }
.sdk-card-title h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}
.sdk-card p {
    color: var(--al-text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 20px;
}
.sdk-card-install {
    background: var(--al-surface-2);
    border-radius: 8px;
    padding: 12px 16px;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 0.82rem;
    color: var(--al-green);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.sdk-card-links {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.sdk-card-links a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--al-surface-2);
    border: 1px solid var(--al-border);
    border-radius: 8px;
    color: var(--al-text-muted);
    text-decoration: none;
    font-size: 0.82rem;
    transition: all 0.2s;
}
.sdk-card-links a:hover {
    color: var(--al-accent-light);
    border-color: var(--al-accent);
}

/* ─── CTA ─── */
.sdk-cta {
    text-align: center;
    padding: 60px 20px 80px;
    max-width: 700px;
    margin: 0 auto;
}
.sdk-cta h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 16px;
}
.sdk-cta p {
    color: var(--al-text-muted);
    margin-bottom: 24px;
    line-height: 1.6;
}
.sdk-cta-links {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.sdk-cta-links a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.95rem;
}
.sdk-cta-links .primary {
    background: var(--al-accent);
    color: #fff;
}
.sdk-cta-links .primary:hover {
    background: #5a4bd1;
    box-shadow: 0 6px 20px rgba(108,92,231,0.4);
}
.sdk-cta-links .secondary {
    background: var(--al-surface);
    color: var(--al-text);
    border: 1px solid var(--al-border);
}
.sdk-cta-links .secondary:hover {
    border-color: var(--al-accent);
}

@media (max-width: 768px) {
    .sdk-hero { padding-top: 90px; }
    .sdk-tabs { flex-wrap: wrap; }
    .sdk-features-grid, .sdk-cards-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ═══════ HERO ═══════ -->
<section class="sdk-hero">
    <h1><i class="fas fa-cube" style="margin-right:10px;"></i>Alfred AI SDKs</h1>
    <p>Integrate Alfred into your application in minutes. Official, type-safe client libraries for Node.js, Python, and PHP.</p>
    <div class="sdk-hero-badges">
        <span><i class="fas fa-shield-alt"></i> Type-Safe</span>
        <span><i class="fas fa-sync-alt"></i> Auto-Retry</span>
        <span><i class="fas fa-stream"></i> Streaming</span>
        <span><i class="fas fa-bolt"></i> 13,000+ Tools</span>
        <span><i class="fas fa-lock"></i> Webhook Verification</span>
        <span><i class="fas fa-plug"></i> 6 Tool Providers</span>
        <span><i class="fas fa-robot"></i> Robotics</span>
        <span><i class="fas fa-palette"></i> Creative AI</span>
        <span><i class="fas fa-book-open"></i> Deep Research</span>
        <span><i class="fas fa-music"></i> SSP Music API</span>
        <span><i class="fas fa-ticket-alt"></i> Events & Ticketing</span>
        <span><i class="fas fa-coins"></i> Solana Payments</span>
    </div>
</section>

<!-- ═══════ QUICK START ═══════ -->
<section class="sdk-quickstart">
    <h2>Quick Start</h2>
    <div class="sdk-tabs">
        <button class="sdk-tab active" onclick="switchTab('node')" id="tab-node">
            <i class="fab fa-node-js" style="color:#68a063"></i> Node.js
        </button>
        <button class="sdk-tab" onclick="switchTab('python')" id="tab-python">
            <i class="fab fa-python" style="color:#3776ab"></i> Python
        </button>
        <button class="sdk-tab" onclick="switchTab('php')" id="tab-php">
            <i class="fab fa-php" style="color:#777bb4"></i> PHP
        </button>
    </div>

    <!-- Node.js -->
    <div class="sdk-tab-content active" id="content-node">
        <div class="sdk-install-row">
            <span class="sdk-install-cmd">npm install @alfredai/sdk</span>
            <button class="sdk-copy-btn" onclick="copyText('npm install @alfredai/sdk', this)"><i class="fas fa-copy"></i> Copy</button>
        </div>
        <pre class="sdk-code-block"><code class="language-typescript">import { AlfredClient } from '@alfredai/sdk';

const alfred = new AlfredClient({ apiKey: 'ak_live_xxx_yyy' });

// Execute a tool
const result = await alfred.tools.execute('dns_lookup', {
  args: { domain: 'example.com', type: 'A' }
});
console.log(result.data.result);

// Chat with Alfred
const reply = await alfred.chat.ask('Summarize this document...');
console.log(reply);

// Stream a response
for await (const chunk of alfred.chat.stream({ message: 'Write a story' })) {
  if (chunk.type === 'text') process.stdout.write(chunk.content);
}</code></pre>
    </div>

    <!-- Python -->
    <div class="sdk-tab-content" id="content-python">
        <div class="sdk-install-row">
            <span class="sdk-install-cmd">pip install alfred-ai-sdk</span>
            <button class="sdk-copy-btn" onclick="copyText('pip install alfred-ai-sdk', this)"><i class="fas fa-copy"></i> Copy</button>
        </div>
        <pre class="sdk-code-block"><code class="language-python">from alfred_sdk import AlfredClient

client = AlfredClient(api_key="ak_live_xxx_yyy")

# Execute a tool
result = client.tools.execute("dns_lookup", args={"domain": "example.com", "type": "A"})
print(result["data"]["result"])

# Chat with Alfred
reply = client.chat.ask("Summarize this document...")
print(reply)

# Stream a response
for chunk in client.chat.stream("Write a story"):
    if chunk.get("type") == "text":
        print(chunk["content"], end="", flush=True)</code></pre>
    </div>

    <!-- PHP -->
    <div class="sdk-tab-content" id="content-php">
        <div class="sdk-install-row">
            <span class="sdk-install-cmd">composer require alfredai/sdk</span>
            <button class="sdk-copy-btn" onclick="copyText('composer require alfredai/sdk', this)"><i class="fas fa-copy"></i> Copy</button>
        </div>
        <pre class="sdk-code-block"><code class="language-php">use AlfredAI\Alfred;

$alfred = new Alfred(['api_key' => 'ak_live_xxx_yyy']);

// Execute a tool
$result = $alfred->tools->execute('dns_lookup', ['domain' => 'example.com', 'type' => 'A']);
print_r($result['data']['result']);

// Chat with Alfred
$reply = $alfred->chat->ask('Summarize this document...');
echo $reply;

// Stream a response
foreach ($alfred->chat->stream('Write a story') as $chunk) {
    if (($chunk['type'] ?? '') === 'text') {
        echo $chunk['content'];
    }
}</code></pre>
    </div>
</section>

<!-- ═══════ FEATURES ═══════ -->
<section class="sdk-features">
    <h2>Built for Developers</h2>
    <div class="sdk-features-grid">
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(108,92,231,0.12); color:var(--al-accent-light);">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Type-Safe</h3>
            <p>Full TypeScript definitions, Python type hints, and PHP 8.1+ strict types. Catch errors at compile time, not runtime.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(0,184,148,0.12); color:var(--al-green);">
                <i class="fas fa-sync-alt"></i>
            </div>
            <h3>Auto-Retry</h3>
            <p>Automatic retries with exponential backoff on transient errors and rate limits. No manual retry logic needed.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(0,206,201,0.12); color:var(--al-cyan);">
                <i class="fas fa-stream"></i>
            </div>
            <h3>Streaming Support</h3>
            <p>Native streaming for chat responses. Async iterators in Node.js, generators in Python, yield in PHP.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(253,121,168,0.12); color:var(--al-pink);">
                <i class="fas fa-fingerprint"></i>
            </div>
            <h3>Webhook Verification</h3>
            <p>Built-in HMAC-SHA256 signature verification for incoming webhooks. Constant-time comparison to prevent timing attacks.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(253,203,110,0.12); color:var(--al-orange);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Rich Error Handling</h3>
            <p>Typed exception classes for every error scenario — AuthError, RateLimitError, NotFoundError, ValidationError, and more.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(9,132,227,0.12); color:var(--al-blue);">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <h3>Rate Limit Awareness</h3>
            <p>Automatic tracking of rate limit headers. Access remaining quota after every request. Auto-wait on 429 responses.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(162,155,254,0.12); color:#a29bfe;">
                <i class="fas fa-gamepad"></i>
            </div>
            <h3>Game Engine SDK v2.1</h3>
            <p>Build 3D WebXR games with Three.js, multiplayer via WebSocket, AI agents with personality negotiation, proximity spatial audio, gamepad support, and Solana payments — all integrated.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(0,206,201,0.12); color:var(--al-cyan);">
                <i class="fas fa-plug"></i>
            </div>
            <h3>6 Tool Providers</h3>
            <p>Discover 13,000+ tools across Native, MCP, External MCP (870+ servers), Multi-App Hub (850+ apps), Voice Platform, and Marketplace providers at runtime.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(253,121,168,0.12); color:var(--al-pink);">
                <i class="fas fa-robot"></i>
            </div>
            <h3>Robotics v2.0</h3>
            <p>ROS 2 integration via rosbridge. Control robots, read sensors, navigate, and orchestrate fleets — from browser or server SDKs.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(253,203,110,0.12); color:var(--al-orange);">
                <i class="fas fa-palette"></i>
            </div>
            <h3>Creative AI</h3>
            <p>Generate images (FLUX, DALL-E), video (Kling), music (MusicGen), and speech (Alfred Premium, F5-TTS) through a unified API.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(9,132,227,0.12); color:var(--al-blue);">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h3>Vertical Domains</h3>
            <p>Specialized APIs for law (CanLII), academia (Semantic Scholar), translation (DeepL), math (Wolfram Alpha), finance, and weather.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(0,184,148,0.12); color:var(--al-green);">
                <i class="fas fa-book-open"></i>
            </div>
            <h3>Deep Research</h3>
            <p>Multi-step research pipelines with AI synthesis, source citations, and document parsing for PDF, DOCX, CSV, images, and more.</p>
        </div>
        <div class="sdk-feature-card">
            <div class="sdk-feature-icon" style="background:rgba(0,206,201,0.12); color:var(--al-cyan);">
                <i class="fas fa-coins"></i>
            </div>
            <h3>Solana & Crypto</h3>
            <p>Accept SOL, GSM Token, and USDC payments. Jupiter DEX integration, Phantom wallet connect, and on-chain transaction tracking.</p>
        </div>
    </div>
</section>

<!-- ═══════ CODE EXAMPLES ═══════ -->
<section class="sdk-examples">
    <h2>Real-World Examples</h2>

    <div class="sdk-example">
        <h3><i class="fas fa-robot" style="margin-right:8px;"></i>Create and Deploy an Agent</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">const agent = await alfred.agents.create({
  agent_name: 'Customer Support Bot',
  agent_role: 'specialist',
  task: 'Handle billing and account questions',
  skills: ['product_lookup', 'ticket_create', 'refund_process'],
  voice_enabled: true,
  voice_engine: 'cartesia',
});

// Deploy the agent
await alfred.agents.deploy(agent.data.id, {
  task: 'Monitor incoming support tickets',
  auto_start: true,
});

console.log(`Agent ${agent.data.agent_name} deployed!`);</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-comments" style="margin-right:8px;"></i>Streaming Chat Response</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Python</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-python">from alfred_sdk import AlfredClient

client = AlfredClient(api_key="ak_live_xxx_yyy")

for chunk in client.chat.stream(
    "Analyze the security posture of my infrastructure",
    tools=["security_scan", "vulnerability_check", "ssl_verify"],
):
    if chunk.get("type") == "text":
        print(chunk["content"], end="", flush=True)
    elif chunk.get("type") == "tool_start":
        print(f"\n🔧 Running {chunk['tool']}...", flush=True)
    elif chunk.get("type") == "tool_end":
        print(f"  ✅ Done", flush=True)</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-wrench" style="margin-right:8px;"></i>Execute a Tool</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">PHP</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-php">use AlfredAI\Alfred;
use AlfredAI\Exceptions\RateLimitException;

$alfred = new Alfred(['api_key' => 'ak_live_xxx_yyy']);

try {
    $result = $alfred->tools->execute('seo_audit', [
        'url' => 'https://example.com',
        'depth' => 3,
        'check_mobile' => true,
    ]);
    echo "Score: " . $result['data']['result']['score'] . "/100\n";
    echo "Issues: " . count($result['data']['result']['issues']) . "\n";
} catch (RateLimitException $e) {
    echo "Rate limited — retry in {$e->retryAfter}s\n";
}</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-phone-alt" style="margin-right:8px;"></i>Start a Voice Call</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// Create a voice room with an AI agent
const room = await alfred.voice.createRoom({
  name: 'Sales Call',
  max_participants: 3,
  voice_engine: 'cartesia',
  agent_id: 42,
});

console.log(`Room created: ${room.data.name}`);
console.log(`Join at: /voice/rooms/${room.data.id}`);</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-music" style="margin-right:8px;"></i>SSP Music API — Fetch Tracks & Venues</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// Fetch tracks from SoundStudioPro Music API
const tracks = await fetch('/api/ssp-music.php?action=tracks&genre=house');
const data = await tracks.json();
console.log(`${data.tracks.length} tracks found`);

// Get all 16 world venues
const venues = await fetch('/api/ssp-music.php?action=venues');
const venueData = await venues.json();
venueData.venues.forEach(v => {
  console.log(`${v.name} — capacity: ${v.capacity}`);
});</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-ticket-alt" style="margin-right:8px;"></i>SSP Events API — Purchase Tickets with Solana</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// List upcoming DJ events
const events = await fetch('/api/ssp-events.php?action=events&filter=featured');
const { events: list } = await events.json();

// Purchase VIP tickets with SOL
const purchase = await fetch('/api/ssp-events.php?action=purchase', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    event_id: 'evt-001',
    tier: 'vip',
    quantity: 2,
    payment_method: 'sol',
    wallet: 'YourSolanaWalletAddress...'
  })
});
const ticket = await purchase.json();
console.log(`Ticket: ${ticket.purchase.ticket_id}`);
console.log(`Total: ${ticket.purchase.total} SOL`);</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-cross" style="margin-right:8px;"></i>Sanctuary API v4.0 — Brotherhood, 60 Agents, 50 Languages</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// Brotherhood of Jesus Christ — 60 agents speaking 50 languages
const agents = await fetch('/api/brotherhood.php?action=agents');
const { agents: brotherhood } = await agents.json();
console.log(`${brotherhood.length} Brotherhood agents ready`);
brotherhood.filter(a => a.role === 'apostle').forEach(a =>
  console.log(`${a.name} — ${a.languages.join(', ')}`));

// 50 languages — "Every man heard them speak in his own language"
const langs = await fetch('/api/brotherhood.php?action=languages');
const { languages } = await langs.json();
languages.forEach(l => console.log(`${l.native}: ${l.greeting}`));

// 13 interconnected games — all connected to the Gospel
const games = await fetch('/api/brotherhood.php?action=connections');
const { connections } = await games.json();
connections.forEach(g => console.log(`${g.game}: ${g.gospel_hook}`));

// Game Engine SDK — text, voice, API, transactions
const sdk = await fetch('/api/brotherhood.php?action=sdk');
const { sdk: config } = await sdk.json();
console.log(`${config.name} v${config.version}`);

// The Royal Line of Perez — 41 generations
const lineage = await fetch('/api/sanctuary.php?action=lineage');
const { lineage: generations, insight } = await lineage.json();
console.log(insight.title); // "The Secret of the Game of Life"

// 12 Classrooms, Donations, Daily verse — all still available
const classes = await fetch('/api/sanctuary.php?action=classrooms');
const { classrooms } = await classes.json();
classrooms.forEach(c => console.log(`${c.title} — ${c.teacher.name}`));</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-music" style="margin-right:8px;"></i>SSP Gospel Music API — Create & Automix</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// Browse 30 gospel tracks across 12 genres
const tracks = await fetch('/api/ssp-gospel.php?action=tracks');
const { tracks: gospelTracks } = await tracks.json();

// Get Psalms of David with instruments & keys
const psalms = await fetch('/api/ssp-gospel.php?action=psalms');
const { psalms: davidPsalms } = await psalms.json();
console.log(`${davidPsalms[0].title} — ${davidPsalms[0].key} at ${davidPsalms[0].bpm} BPM`);

// Create a gospel track with SSP token
const track = await fetch('/api/ssp-gospel.php?action=create', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    title: 'Amazing Grace Remix', genre: 'contemporary-worship',
    key: 'G major', instruments: ['harp-of-david', 'choir-angelic']
  })
});
const { track: newTrack } = await track.json();
console.log(`Created: ${newTrack.title} (SSP ID: ${newTrack.ssp_id})`);</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-plug" style="margin-right:8px;"></i>Discover Tool Providers (13,000+ Tools)</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// List all 6 tool providers
const providers = await alfred.tools.providers();
providers.data.forEach(p =>
  console.log(`${p.name}: ${p.tool_count} tools — ${p.status}`)
);

// Discover tools from a specific provider
const appHubTools = await alfred.tools.discover('app-hub');
console.log(`App Hub: ${appHubTools.data.length} tools across 850+ apps`);

// Native (170) + MCP (807) + External MCP (1,200+) + App Hub (11,000+) + Voice (85) + Marketplace
const allTools = await alfred.tools.list();
console.log(`Total available: ${allTools.data.provider_summary.estimated_total}`);</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-book-open" style="margin-right:8px;"></i>Deep Research Mode</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// Launch a deep research task
const research = await fetch('/api/deep-research.php?action=research', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    question: 'What are the latest advances in quantum error correction?',
    mode: 'deep',   // quick | standard | deep
    max_sources: 8,
  })
});
const { task_id } = await research.json();

// Poll for results
const result = await fetch(`/api/deep-research.php?action=result&task_id=${task_id}`);
const { synthesis, sources, citations } = await result.json();
console.log(synthesis);
sources.forEach(s => console.log(`  [${s.domain}] ${s.title}`));</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-palette" style="margin-right:8px;"></i>Creative AI — Image, Video, Music, TTS</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// Generate an image with FLUX
const image = await fetch('/api/creative.php?action=image', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    prompt: 'A cyberpunk cityscape at sunset, neon lights reflecting in rain',
    model: 'flux-schnell', width: 1024, height: 1024
  })
});
const { output_url } = await image.json();

// Generate music with MusicGen
const music = await fetch('/api/creative.php?action=music', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ prompt: 'Upbeat jazz fusion with electric piano', duration: 15 })
});

// Text-to-Speech with Alfred Premium
const tts = await fetch('/api/creative.php?action=tts', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ text: 'Hello from Alfred AI!', model: 'elevenlabs' })
});</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-graduation-cap" style="margin-right:8px;"></i>Vertical APIs — Legal, Academic, Translation, Math</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / TypeScript</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">// Search Canadian case law via CanLII
const legal = await fetch('/api/verticals.php?domain=legal&action=search', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ query: 'data privacy breach', jurisdiction: 'ca' })
});

// Search academic papers via Semantic Scholar
const papers = await fetch('/api/verticals.php?domain=academic&action=search&q=transformer+architecture');
const { papers: results } = await papers.json();
results.forEach(p => console.log(`${p.title} (${p.year}) — ${p.citations} citations`));

// Generate BibTeX citation
const cite = await fetch('/api/verticals.php?domain=academic&action=cite', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ paper_id: results[0].paperId, style: 'bibtex' })
});

// Translate text with DeepL
const translation = await fetch('/api/verticals.php?domain=translate&action=translate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ text: 'Hello world', target: 'FR' })
});

// Compute with Wolfram Alpha
const math = await fetch('/api/verticals.php?domain=math&action=compute&q=integral+of+sin(x)dx');</code></pre>
        </div>
    </div>

    <div class="sdk-example">
        <h3><i class="fas fa-lock" style="margin-right:8px;"></i>Verify Webhook Signature</h3>
        <div class="sdk-example-code">
            <div class="sdk-example-header">
                <span class="sdk-example-lang">Node.js / Express</span>
                <button class="sdk-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="sdk-code-block"><code class="language-typescript">import express from 'express';
import { AlfredClient } from '@alfredai/sdk';

const alfred = new AlfredClient({ apiKey: 'ak_live_xxx_yyy' });
const app = express();

app.post('/webhooks/alfred', express.text({ type: '*/*' }), (req, res) => {
  try {
    const event = alfred.webhooks.verifyAndParse(
      req.body,
      req.headers['x-webhook-signature'] as string,
      process.env.WEBHOOK_SECRET!,
    );
    console.log(`Received ${event.event}:`, event.data);
    res.sendStatus(200);
  } catch {
    res.sendStatus(401);
  }
});</code></pre>
        </div>
    </div>
</section>

<!-- ═══════ SDK CARDS ═══════ -->
<section class="sdk-cards">
    <h2>Choose Your Language</h2>
    <div class="sdk-cards-grid">
        <div class="sdk-card">
            <div class="sdk-card-title">
                <i class="fab fa-node-js" style="color:#68a063"></i>
                <h3>Node.js / TypeScript</h3>
            </div>
            <p>Full TypeScript support with interfaces for every API type. Works with Node.js 18+ and modern browsers via bundlers.</p>
            <div class="sdk-card-install">
                <span>npm install @alfredai/sdk</span>
                <button class="sdk-copy-btn" onclick="copyText('npm install @alfredai/sdk', this)"><i class="fas fa-copy"></i></button>
            </div>
            <div class="sdk-card-links">
                <a href="/docs/api-reference"><i class="fas fa-book"></i> API Docs</a>
                <a href="/docs/getting-started"><i class="fas fa-rocket"></i> Getting Started</a>
                <a href="https://alfredlinux.com/forge/explore/repos" target="_blank"><i class="fas fa-code-branch"></i> Source</a>
            </div>
        </div>

        <div class="sdk-card">
            <div class="sdk-card-title">
                <i class="fab fa-python" style="color:#3776ab"></i>
                <h3>Python</h3>
            </div>
            <p>Pythonic interface with type hints, generators for streaming, and support for both sync and async (httpx) workflows.</p>
            <div class="sdk-card-install">
                <span>pip install alfred-ai-sdk</span>
                <button class="sdk-copy-btn" onclick="copyText('pip install alfred-ai-sdk', this)"><i class="fas fa-copy"></i></button>
            </div>
            <div class="sdk-card-links">
                <a href="/docs/api-reference"><i class="fas fa-book"></i> API Docs</a>
                <a href="/docs/getting-started"><i class="fas fa-rocket"></i> Getting Started</a>
                <a href="https://alfredlinux.com/forge/explore/repos" target="_blank"><i class="fas fa-code-branch"></i> Source</a>
            </div>
        </div>

        <div class="sdk-card">
            <div class="sdk-card-title">
                <i class="fab fa-php" style="color:#777bb4"></i>
                <h3>PHP</h3>
            </div>
            <p>PSR-4 autoloading, PHP 8.1+ strict types, and native cURL HTTP client. Perfect for Laravel, Symfony, or vanilla PHP.</p>
            <div class="sdk-card-install">
                <span>composer require alfredai/sdk</span>
                <button class="sdk-copy-btn" onclick="copyText('composer require alfredai/sdk', this)"><i class="fas fa-copy"></i></button>
            </div>
            <div class="sdk-card-links">
                <a href="/docs/api-reference"><i class="fas fa-book"></i> API Docs</a>
                <a href="/docs/getting-started"><i class="fas fa-rocket"></i> Getting Started</a>
                <a href="https://alfredlinux.com/forge/explore/repos" target="_blank"><i class="fas fa-code-branch"></i> Source</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════ CTA ═══════ -->
<section class="sdk-cta">
    <h2>Ready to Build?</h2>
    <p>Get your API key from the Developer Portal and start integrating Alfred AI into your application today.</p>
    <div class="sdk-cta-links">
        <a href="/developer-portal" class="primary"><i class="fas fa-key"></i> Get API Key</a>
        <a href="/docs/getting-started" class="secondary"><i class="fas fa-book"></i> Read the Docs</a>
        <a href="/docs/api-reference" class="secondary"><i class="fas fa-plug"></i> API Reference</a>
    </div>
</section>

<!-- Prism.js for syntax highlighting -->
<link rel="stylesheet" href="/assets/js/vendor/prism-tomorrow.min.css">
<script src="/assets/js/vendor/prism.min.js"></script>
<script src="/assets/js/vendor/prism-typescript.min.js"></script>
<script src="/assets/js/vendor/prism-python.min.js"></script>
<script src="/assets/js/vendor/prism-php.min.js"></script>
<script src="/assets/js/vendor/prism-bash.min.js"></script>

<script src="/assets/js/sdks-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
