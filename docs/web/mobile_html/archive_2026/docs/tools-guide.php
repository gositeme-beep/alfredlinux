<?php
$page_title = 'Tools Guide — Alfred AI Documentation | GoSiteMe';
$page_description = 'Complete guide to Alfred AI\'s 1,220+ tools across 17 categories. Learn to call tools via API or voice, chain tools into playbooks, create custom tools, and publish to the marketplace.';
$page_canonical = 'https://gositeme.com/docs/tools-guide';
$page_og_title = 'Alfred AI Tools Guide — 1,220+ Tools Across 17 Categories';
$page_og_description = 'Master Alfred AI\'s 1,220+ tools: categories overview, API vs voice calling, tool chaining, playbooks, custom tool creation, and marketplace publishing.';
include __DIR__ . '/../includes/lang.php';
include __DIR__ . '/../includes/site-header.inc.php';
?>

<!-- Schema.org TechArticle markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "Alfred AI Tools Guide — 1,220+ Tools Across 17 Categories",
  "description": "Complete guide to using Alfred AI's tools: categories overview, calling via API and voice, tool chaining, playbooks, custom tools, and marketplace publishing.",
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
  "mainEntityOfPage": "https://gositeme.com/docs/tools-guide",
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

/* Category grid */
.cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
    margin: 20px 0;
}
.cat-card {
    background: var(--doc-surface);
    border: 1px solid var(--doc-border);
    border-radius: var(--doc-radius);
    padding: 16px;
    transition: all 0.2s;
    text-decoration: none !important;
    display: block;
}
.cat-card:hover {
    border-color: var(--doc-accent);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(108,92,231,0.12);
}
.cat-card .cat-icon {
    font-size: 1.4rem;
    margin-bottom: 8px;
}
.cat-card .cat-name {
    font-weight: 700;
    color: var(--doc-text);
    font-size: 0.9rem;
    margin-bottom: 4px;
}
.cat-card .cat-count {
    color: var(--doc-text-muted);
    font-size: 0.78rem;
}

/* Workflow cards */
.workflow-card {
    background: var(--doc-surface);
    border: 1px solid var(--doc-border);
    border-radius: var(--doc-radius);
    padding: 24px;
    margin: 20px 0;
}
.workflow-card h4 {
    margin-top: 0 !important;
    color: #fff !important;
}
.workflow-steps {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin: 12px 0 8px;
}
.workflow-step {
    background: var(--doc-surface-2);
    border: 1px solid var(--doc-border);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    color: var(--doc-accent-light);
    font-weight: 600;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    white-space: nowrap;
}
.workflow-arrow {
    color: var(--doc-text-muted);
    font-size: 0.9rem;
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
    .cat-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
}
</style>

<!-- Breadcrumbs -->
<div class="doc-breadcrumbs">
    <a href="/docs/">Docs</a>
    <span>›</span>
    <span class="current">Tools Guide</span>
</div>

<!-- Layout -->
<div class="doc-layout">
    <!-- Sidebar -->
    <nav class="doc-sidebar" id="docSidebar">
        <ul class="doc-sidebar-nav">
            <li><a href="/docs/"><i class="fas fa-home"></i> Docs Home</a></li>
            <li><a href="/docs/getting-started"><i class="fas fa-rocket"></i> Getting Started</a></li>
            <li><a href="/docs/api-reference"><i class="fas fa-plug"></i> API Reference</a></li>
            <li><a href="/docs/voice-integration"><i class="fas fa-microphone"></i> Voice Integration</a></li>
            <li>
                <a href="/docs/tools-guide" class="active"><i class="fas fa-wrench"></i> Tools Guide</a>
                <ul class="doc-sidebar-sub">
                    <li><a href="#categories">Tool Categories</a></li>
                    <li><a href="#calling-api">Calling via API</a></li>
                    <li><a href="#calling-voice">Calling via Voice</a></li>
                    <li><a href="#chaining">Tool Chaining</a></li>
                    <li><a href="#playbooks">Playbooks</a></li>
                    <li><a href="#custom">Custom Tools</a></li>
                    <li><a href="#marketplace">Marketplace</a></li>
                    <li><a href="#workflows">Example Workflows</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- Mobile Toggle -->
    <button class="doc-sidebar-toggle" id="docSidebarToggle" aria-label="Toggle documentation menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="doc-main">
        <h1><i class="fas fa-wrench"></i> Tools Guide</h1>
        <p class="doc-subtitle">Alfred AI includes 1,220+ tools across 17 categories. This guide covers how to discover, call, chain, and create tools — whether you're using the API, voice, or dashboard.</p>

        <div class="doc-info note">
            <i class="fas fa-th"></i>
            <div><strong>Browse All Tools:</strong> Visit the <a href="/alfred-tools.php">Tool Directory</a> for a searchable, filterable catalog of all 1,220+ tools with live demos.</div>
        </div>

        <!-- ==================== CATEGORIES ==================== -->
        <h2 id="categories"><i class="fas fa-th-large"></i> Tool Categories (17)</h2>
        <p>Tools are organized into 17 categories covering business, development, creative, legal, healthcare, and more.</p>

        <div class="cat-grid">
            <div class="cat-card">
                <div class="cat-icon">⚖️</div>
                <div class="cat-name">Legal</div>
                <div class="cat-count">43 tools — Motions, contracts, case research</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🏥</div>
                <div class="cat-name">Healthcare</div>
                <div class="cat-count">38 tools — Records, symptoms, scheduling</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🎓</div>
                <div class="cat-name">Education</div>
                <div class="cat-count">52 tools — Tutoring, grading, research</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">⚙️</div>
                <div class="cat-name">DevOps</div>
                <div class="cat-count">67 tools — DNS, servers, deployments</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🎬</div>
                <div class="cat-name">Media</div>
                <div class="cat-count">58 tools — Images, video, audio, editing</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">💰</div>
                <div class="cat-name">Finance</div>
                <div class="cat-count">45 tools — Invoicing, analysis, crypto</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">📈</div>
                <div class="cat-name">Marketing</div>
                <div class="cat-count">61 tools — SEO, ads, social, email</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🏠</div>
                <div class="cat-name">Real Estate</div>
                <div class="cat-count">34 tools — Listings, valuations, docs</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🏛️</div>
                <div class="cat-name">Government</div>
                <div class="cat-count">29 tools — Forms, FOIA, compliance</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🔒</div>
                <div class="cat-name">Security</div>
                <div class="cat-count">42 tools — Scanning, monitoring, audits</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">📊</div>
                <div class="cat-name">Analytics</div>
                <div class="cat-count">55 tools — Data viz, reports, insights</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">💬</div>
                <div class="cat-name">Communications</div>
                <div class="cat-count">38 tools — Email, SMS, chat, calls</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">✅</div>
                <div class="cat-name">Productivity</div>
                <div class="cat-count">72 tools — Tasks, calendar, notes</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🎮</div>
                <div class="cat-name">Entertainment</div>
                <div class="cat-count">31 tools — Games, trivia, stories</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">✈️</div>
                <div class="cat-name">Travel</div>
                <div class="cat-count">36 tools — Flights, hotels, itineraries</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🍳</div>
                <div class="cat-name">Food</div>
                <div class="cat-count">28 tools — Recipes, nutrition, ordering</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">🔧</div>
                <div class="cat-name">Utilities</div>
                <div class="cat-count">146 tools — Converters, generators, lookups</div>
            </div>
        </div>

        <h3>List Categories via API</h3>
        <div class="doc-endpoint"><span class="doc-method get">GET</span> /api/tools.php?action=categories</div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const categories = await fetch('https://gositeme.com/api/tools.php?action=categories')
  .then(r => r.json());

console.log(categories);
// { categories: ["legal","healthcare","education",...], total: 17 }

// Get tools in a category
const legalTools = await fetch('https://gositeme.com/api/tools.php?action=list&category=legal')
  .then(r => r.json());

console.log(`${legalTools.tools.length} legal tools available`);</code></pre>
        </div>

        <!-- ==================== CALLING VIA API ==================== -->
        <h2 id="calling-api"><i class="fas fa-code"></i> Calling Tools via API</h2>
        <p>Execute any tool programmatically via the Tools API. All tool calls use the same endpoint with the tool name and arguments.</p>

        <div class="doc-endpoint"><span class="doc-method post">POST</span> /api/tools.php?action=execute</div>

        <h3>Basic Tool Call</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const result = await fetch('https://gositeme.com/api/tools.php?action=execute', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    tool_name: 'summarize_text',
    args: {
      text: 'Your long article text here...',
      max_length: 200,
      format: 'bullet_points'
    }
  })
}).then(r => r.json());

console.log(result);
// {
//   success: true,
//   tool: "summarize_text",
//   result: "• Point one\n• Point two\n• Point three",
//   execution_time_ms: 1340,
//   credits_used: 1
// }</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

result = requests.post(
    'https://gositeme.com/api/tools.php?action=execute',
    headers={'Authorization': 'Bearer sess_abc123...'},
    json={
        'tool_name': 'dns_lookup',
        'args': {'domain': 'example.com', 'type': 'MX'}
    }
).json()

for record in result['result']['records']:
    print(f"{record['type']}: {record['value']} (priority: {record.get('priority', 'N/A')})")</code></pre>
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
        'tool_name' => 'generate_invoice',
        'args' => [
            'client' => 'Acme Corp',
            'items' => [
                ['description' => 'Web Development', 'amount' => 5000],
                ['description' => 'SEO Audit', 'amount' => 1500]
            ],
            'currency' => 'CAD',
            'due_date' => '2026-04-01'
        ]
    ]),
    CURLOPT_RETURNTRANSFER => true
]);
$result = json_decode(curl_exec($ch), true);
curl_close($ch);
echo "Invoice URL: " . $result['result']['pdf_url'];</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>curl -X POST "https://gositeme.com/api/tools.php?action=execute" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer sess_abc123..." \
  -d '{
    "tool_name": "website_screenshot",
    "args": {
      "url": "https://example.com",
      "width": 1920,
      "height": 1080,
      "format": "png"
    }
  }'</code></pre>
        </div>

        <h3>Discovering Tool Arguments</h3>
        <p>Each tool has a defined argument schema. Query it before calling:</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const toolInfo = await fetch('https://gositeme.com/api/tools.php?action=info&tool=summarize_text')
  .then(r => r.json());

console.log(toolInfo);
// {
//   name: "summarize_text",
//   category: "productivity",
//   description: "Summarize long text into concise bullet points or paragraphs",
//   args: [
//     { name: "text", type: "string", required: true, description: "Text to summarize" },
//     { name: "max_length", type: "integer", required: false, description: "Max output length" },
//     { name: "format", type: "string", required: false, description: "Output format", enum: ["paragraph","bullet_points","numbered"] }
//   ]
// }</code></pre>
        </div>

        <!-- ==================== CALLING VIA VOICE ==================== -->
        <h2 id="calling-voice"><i class="fas fa-microphone-alt"></i> Calling Tools via Voice</h2>
        <p>Call any tool naturally by voice — Alfred understands intent and maps it to the right tool automatically. No special syntax needed.</p>

        <h3>Voice Tool Call Examples</h3>
        <table class="doc-params">
            <thead><tr><th>What You Say</th><th>Tool Called</th><th>Category</th></tr></thead>
            <tbody>
                <tr><td>"Look up the DNS records for example.com"</td><td><code>dns_lookup</code></td><td>DevOps</td></tr>
                <tr><td>"Summarize this article for me"</td><td><code>summarize_text</code></td><td>Productivity</td></tr>
                <tr><td>"Draft a cease and desist letter"</td><td><code>draft_legal_letter</code></td><td>Legal</td></tr>
                <tr><td>"What's the weather in Montreal?"</td><td><code>weather_lookup</code></td><td>Utilities</td></tr>
                <tr><td>"Create an invoice for $5,000"</td><td><code>generate_invoice</code></td><td>Finance</td></tr>
                <tr><td>"Scan example.com for security issues"</td><td><code>security_scan</code></td><td>Security</td></tr>
                <tr><td>"Generate a QR code for my website"</td><td><code>qr_generator</code></td><td>Utilities</td></tr>
                <tr><td>"Help me with my calculus homework"</td><td><code>homework_helper</code></td><td>Education</td></tr>
            </tbody>
        </table>

        <div class="doc-info tip">
            <i class="fas fa-lightbulb"></i>
            <div><strong>Natural Language:</strong> You don't need to know tool names. Just describe what you want and Alfred will select the right tool. Say "I need to check if my website is loading fast" and Alfred will run performance analysis tools.</div>
        </div>

        <h3>Voice + Multi-Tool</h3>
        <p>Alfred can chain multiple tools in a single voice request:</p>
        <ul>
            <li><strong>"Check my website's DNS and run a security scan"</strong> → <code>dns_lookup</code> + <code>security_scan</code></li>
            <li><strong>"Draft a contract and generate a PDF"</strong> → <code>draft_contract</code> + <code>generate_pdf</code></li>
            <li><strong>"Research competitors and write a summary report"</strong> → <code>competitor_analysis</code> + <code>summarize_text</code> + <code>generate_report</code></li>
        </ul>

        <!-- ==================== TOOL CHAINING ==================== -->
        <h2 id="chaining"><i class="fas fa-link"></i> Tool Chaining</h2>
        <p>Chain multiple tools together so the output of one becomes the input of the next. Tool chaining lets you build complex workflows with a single API call.</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const result = await fetch('https://gositeme.com/api/tools.php?action=chain', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    chain: [
      {
        tool: 'website_scraper',
        args: { url: 'https://competitor.com' },
        output_as: 'scraped_content'
      },
      {
        tool: 'summarize_text',
        args: { text: '{{scraped_content.text}}', max_length: 500 },
        output_as: 'summary'
      },
      {
        tool: 'generate_report',
        args: {
          title: 'Competitor Analysis',
          content: '{{summary}}',
          format: 'pdf'
        },
        output_as: 'report'
      }
    ]
  })
}).then(r => r.json());

console.log(result.chain_results);
// Each step's output, plus final report PDF URL</code></pre>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

result = requests.post(
    'https://gositeme.com/api/tools.php?action=chain',
    headers={'Authorization': 'Bearer sess_abc123...'},
    json={
        'chain': [
            {
                'tool': 'dns_lookup',
                'args': {'domain': 'example.com', 'type': 'A'},
                'output_as': 'dns'
            },
            {
                'tool': 'ssl_check',
                'args': {'domain': 'example.com'},
                'output_as': 'ssl'
            },
            {
                'tool': 'performance_test',
                'args': {'url': 'https://example.com'},
                'output_as': 'perf'
            },
            {
                'tool': 'generate_report',
                'args': {
                    'title': 'Website Health Report',
                    'sections': {
                        'dns': '{{dns}}',
                        'ssl': '{{ssl}}',
                        'performance': '{{perf}}'
                    }
                },
                'output_as': 'report'
            }
        ]
    }
).json()

print(f"Report: {result['chain_results']['report']['pdf_url']}")</code></pre>
        </div>

        <h3>Chain Variable Syntax</h3>
        <p>Use <code>{{step_name}}</code> to reference the output of a previous step, or <code>{{step_name.field}}</code> to reference a specific field:</p>
        <table class="doc-params">
            <thead><tr><th>Syntax</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>{{step_name}}</code></td><td>Entire output of step</td></tr>
                <tr><td><code>{{step_name.field}}</code></td><td>Specific field from output</td></tr>
                <tr><td><code>{{step_name.records[0].value}}</code></td><td>Array index + nested field</td></tr>
            </tbody>
        </table>

        <!-- ==================== PLAYBOOKS ==================== -->
        <h2 id="playbooks"><i class="fas fa-book-open"></i> Playbooks</h2>
        <p>Playbooks are saved tool chains that you can reuse. Create a playbook once and run it repeatedly with different inputs.</p>

        <h3>Create a Playbook</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const playbook = await fetch('https://gositeme.com/api/tools.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'create_playbook',
    name: 'Website Audit',
    description: 'Full website health check including DNS, SSL, performance, and SEO',
    inputs: [
      { name: 'domain', type: 'string', required: true, description: 'Domain to audit' }
    ],
    chain: [
      { tool: 'dns_lookup', args: { domain: '{{domain}}', type: 'ALL' }, output_as: 'dns' },
      { tool: 'ssl_check', args: { domain: '{{domain}}' }, output_as: 'ssl' },
      { tool: 'performance_test', args: { url: 'https://{{domain}}' }, output_as: 'perf' },
      { tool: 'seo_audit', args: { url: 'https://{{domain}}' }, output_as: 'seo' },
      { tool: 'generate_report', args: {
          title: 'Website Audit: {{domain}}',
          sections: { dns: '{{dns}}', ssl: '{{ssl}}', performance: '{{perf}}', seo: '{{seo}}' }
        }, output_as: 'report'
      }
    ]
  })
}).then(r => r.json());

console.log(playbook.id); // "pb_websiteaudit_123"</code></pre>
        </div>

        <h3>Run a Playbook</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">cURL</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>curl -X POST "https://gositeme.com/api/tools.php?action=run_playbook" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer sess_abc123..." \
  -d '{
    "playbook_id": "pb_websiteaudit_123",
    "inputs": { "domain": "gositeme.com" }
  }'</code></pre>
        </div>

        <!-- ==================== CUSTOM TOOLS ==================== -->
        <h2 id="custom"><i class="fas fa-puzzle-piece"></i> Creating Custom Tools</h2>
        <p>Create your own tools and register them with Alfred. Custom tools can wrap external APIs, run custom logic, or combine existing tools in new ways.</p>

        <h3>Tool Definition Schema</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JSON</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>{
  "name": "my_custom_tool",
  "display_name": "My Custom Tool",
  "description": "What this tool does in one sentence",
  "category": "utilities",
  "args": [
    {
      "name": "input_text",
      "type": "string",
      "required": true,
      "description": "The text to process"
    },
    {
      "name": "format",
      "type": "string",
      "required": false,
      "description": "Output format",
      "enum": ["json", "text", "html"],
      "default": "text"
    }
  ],
  "webhook_url": "https://yourserver.com/api/my-tool",
  "auth": {
    "type": "bearer",
    "token_env": "MY_TOOL_API_KEY"
  },
  "rate_limit": 60,
  "timeout_ms": 30000
}</code></pre>
        </div>

        <h3>Register a Custom Tool</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>const tool = await fetch('https://gositeme.com/api/tools.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'register_tool',
    name: 'inventory_check',
    display_name: 'Inventory Checker',
    description: 'Check product inventory levels across warehouses',
    category: 'productivity',
    args: [
      { name: 'product_id', type: 'string', required: true, description: 'Product SKU or ID' },
      { name: 'warehouse', type: 'string', required: false, description: 'Specific warehouse code' }
    ],
    webhook_url: 'https://yourapi.com/inventory/check',
    auth: { type: 'api_key', header: 'X-API-Key' }
  })
}).then(r => r.json());

console.log(tool);
// { success: true, tool_id: "tool_custom_inv123", status: "active" }</code></pre>
        </div>

        <h3>Webhook Implementation</h3>
        <p>Your webhook receives POST requests from Alfred when the tool is called:</p>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">PHP</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>&lt;?php
// Your server: /api/inventory/check
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['product_id'] ?? '';
$warehouse = $input['warehouse'] ?? 'all';

// Your business logic here
$inventory = getInventoryLevels($product_id, $warehouse);

echo json_encode([
    'success' => true,
    'result' => [
        'product_id' => $product_id,
        'total_stock' => $inventory['total'],
        'warehouses' => $inventory['by_warehouse'],
        'last_updated' => date('c')
    ]
]);
?&gt;</code></pre>
        </div>

        <div class="doc-info warn">
            <i class="fas fa-exclamation-triangle"></i>
            <div><strong>Webhook Security:</strong> Alfred includes a <code>X-Alfred-Signature</code> header with each request. Verify this HMAC signature server-side to ensure requests originate from Alfred. See <a href="/docs/api-reference#auth">Authentication docs</a>.</div>
        </div>

        <!-- ==================== MARKETPLACE ==================== -->
        <h2 id="marketplace"><i class="fas fa-store"></i> Tool Marketplace</h2>
        <p>Publish your custom tools to the <a href="/marketplace.php">Alfred Marketplace</a> for other users to discover and use. Monetize your tools with per-call pricing or subscriptions.</p>

        <h3>Publish a Tool</h3>
        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>await fetch('https://gositeme.com/api/tools.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    action: 'publish_tool',
    tool_id: 'tool_custom_inv123',
    listing: {
      title: 'Inventory Checker Pro',
      description: 'Real-time inventory tracking across multiple warehouses',
      icon: 'fas fa-boxes',
      pricing: {
        model: 'per_call',        // per_call, monthly, free
        price: 0.01,              // $0.01 per call
        currency: 'USD',
        free_tier: 100            // 100 free calls/month
      },
      tags: ['inventory', 'warehouse', 'e-commerce', 'tracking'],
      documentation_url: 'https://yoursite.com/docs/inventory-checker'
    }
  })
});</code></pre>
        </div>

        <h3>Marketplace Review Process</h3>
        <ol>
            <li><strong>Submit</strong> — Publish your tool listing for review</li>
            <li><strong>Testing</strong> — Our team tests the tool for reliability and security</li>
            <li><strong>Approval</strong> — Tool goes live in the marketplace (24–48 hours)</li>
            <li><strong>Analytics</strong> — Track usage, revenue, and user feedback in your dashboard</li>
        </ol>

        <!-- ==================== EXAMPLE WORKFLOWS ==================== -->
        <h2 id="workflows"><i class="fas fa-project-diagram"></i> Example Workflows</h2>
        <p>Here are practical multi-tool workflows showing how Alfred's tools work together for real-world tasks.</p>

        <div class="workflow-card">
            <h4><i class="fas fa-globe" style="color: var(--doc-blue);"></i> Deploy a Website</h4>
            <p style="margin-top: 0;">End-to-end website deployment from domain registration to going live.</p>
            <div class="workflow-steps">
                <span class="workflow-step">domain_check</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">domain_register</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">dns_configure</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">ssl_provision</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">deploy_site</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">performance_test</span>
            </div>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>// Deploy a Website workflow
const result = await fetch('https://gositeme.com/api/tools.php?action=chain', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    chain: [
      { tool: 'domain_check', args: { domain: 'mysite.com' }, output_as: 'avail' },
      { tool: 'domain_register', args: { domain: 'mysite.com', years: 1 }, output_as: 'reg' },
      { tool: 'dns_configure', args: { domain: 'mysite.com', records: [
          { type: 'A', value: '{{server_ip}}' },
          { type: 'CNAME', name: 'www', value: 'mysite.com' }
        ]}, output_as: 'dns' },
      { tool: 'ssl_provision', args: { domain: 'mysite.com' }, output_as: 'ssl' },
      { tool: 'deploy_site', args: { domain: 'mysite.com', source: 'git://repo.git' }, output_as: 'deploy' },
      { tool: 'performance_test', args: { url: 'https://mysite.com' }, output_as: 'perf' }
    ]
  })
}).then(r => r.json());</code></pre>
        </div>

        <div class="workflow-card">
            <h4><i class="fas fa-search" style="color: var(--doc-green);"></i> Analyze Competitors</h4>
            <p style="margin-top: 0;">Research competitors and generate a comprehensive analysis report.</p>
            <div class="workflow-steps">
                <span class="workflow-step">website_scraper</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">seo_audit</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">social_analysis</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">pricing_comparison</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">generate_report</span>
            </div>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">Python</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>import requests

competitors = ['competitor1.com', 'competitor2.com', 'competitor3.com']

for domain in competitors:
    result = requests.post(
        'https://gositeme.com/api/tools.php?action=chain',
        headers={'Authorization': 'Bearer sess_abc123...'},
        json={
            'chain': [
                {'tool': 'website_scraper', 'args': {'url': f'https://{domain}'}, 'output_as': 'content'},
                {'tool': 'seo_audit', 'args': {'url': f'https://{domain}'}, 'output_as': 'seo'},
                {'tool': 'summarize_text', 'args': {'text': '{{content.text}}', 'max_length': 300}, 'output_as': 'summary'}
            ]
        }
    ).json()

    print(f"\n=== {domain} ===")
    print(f"Summary: {result['chain_results']['summary']}")
    print(f"SEO Score: {result['chain_results']['seo']['score']}/100")</code></pre>
        </div>

        <div class="workflow-card">
            <h4><i class="fas fa-gavel" style="color: var(--doc-orange);"></i> Generate Legal Documents</h4>
            <p style="margin-top: 0;">Draft, review, and finalize legal documents with AI assistance.</p>
            <div class="workflow-steps">
                <span class="workflow-step">case_research</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">draft_motion</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">legal_review</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">format_document</span>
                <span class="workflow-arrow">→</span>
                <span class="workflow-step">generate_pdf</span>
            </div>
        </div>

        <div class="doc-code-wrap">
            <div class="doc-code-header">
                <span class="doc-code-lang">JavaScript</span>
                <button class="doc-copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <pre class="doc-code-block"><code>// Generate Legal Documents workflow
const legalDoc = await fetch('https://gositeme.com/api/tools.php?action=chain', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer sess_abc123...'
  },
  body: JSON.stringify({
    chain: [
      {
        tool: 'case_research',
        args: { topic: 'breach of contract', jurisdiction: 'Quebec' },
        output_as: 'research'
      },
      {
        tool: 'draft_motion',
        args: {
          type: 'motion_to_compel',
          facts: 'Defendant failed to provide discovery documents within 30 days...',
          case_law: '{{research.relevant_cases}}',
          jurisdiction: 'Quebec Superior Court'
        },
        output_as: 'draft'
      },
      {
        tool: 'legal_review',
        args: { document: '{{draft.text}}', check_citations: true },
        output_as: 'review'
      },
      {
        tool: 'generate_pdf',
        args: {
          content: '{{review.final_text}}',
          template: 'legal_motion',
          title: 'Motion to Compel Discovery'
        },
        output_as: 'pdf'
      }
    ]
  })
}).then(r => r.json());

console.log(`PDF ready: ${legalDoc.chain_results.pdf.url}`);</code></pre>
        </div>

        <div class="doc-info tip">
            <i class="fas fa-lightbulb"></i>
            <div><strong>Voice Workflows:</strong> All example workflows above can also be triggered by voice. Say "Run my website audit playbook on example.com" and Alfred handles the rest.</div>
        </div>

        <div class="doc-info note">
            <i class="fas fa-headset"></i>
            <div><strong>Need Help?</strong> Visit the <a href="/docs/api-reference">API Reference</a> for complete endpoint documentation, or contact <strong>support@gositeme.com</strong> for assistance.</div>
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
