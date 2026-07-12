<?php
$article_meta = [
    'title' => 'Getting Started with Alfred AI API: A Developer Guide',
    'description' => 'Step-by-step tutorial for developers integrating Alfred AI API. Covers authentication, tool execution, agent management, and webhooks with code examples in JavaScript, Python, and PHP.',
    'date' => '2026-02-08',
    'author' => 'GoSiteMe Team',
    'category' => 'tutorials',
    'read_time' => '20 min read',
    'featured_image' => '/assets/img/blog/placeholder.png',
    'tags' => ['API', 'developer', 'tutorial', 'JavaScript', 'Python', 'PHP', 'integration'],
    'slug' => 'developer-api-getting-started',
];

ob_start();
?>

<h2>What You Can Build with the Alfred API</h2>
<p>The Alfred AI API gives developers programmatic access to 1,220+ AI tools, agent management, fleet orchestration, and voice capabilities. Whether you are building an internal automation platform, integrating AI into a customer-facing product, or creating custom workflows that chain multiple tools together, the API provides the foundation.</p>

<p>Common integrations include:</p>
<ul>
    <li><strong>Content pipelines:</strong> Automated systems that generate, optimize, and publish content on schedule</li>
    <li><strong>Customer support automation:</strong> AI-powered ticket resolution integrated with your helpdesk</li>
    <li><strong>Development workflows:</strong> Code generation, review, and documentation integrated into CI/CD pipelines</li>
    <li><strong>Data processing:</strong> Batch analysis, report generation, and insight extraction from business data</li>
    <li><strong>Voice applications:</strong> Custom voice agents with telephony integration</li>
</ul>

<p>This guide walks you through everything from API key generation to building a complete integration, with code examples in JavaScript, Python, and PHP.</p>

<h2>Prerequisites</h2>
<p>Before you begin, you need:</p>
<ol>
    <li>A GoSiteMe account with an active subscription (free tier works for development)</li>
    <li>API access enabled on your account (available on all paid plans)</li>
    <li>Basic familiarity with REST APIs and HTTP requests</li>
</ol>

<h2>Step 1: Generate Your API Key</h2>
<p>Navigate to the <a href="/developer-portal.php">Developer Portal</a> and click "Create API Key." Each key has configurable properties:</p>

<pre><code>{
    "name": "Production Integration",
    "permissions": ["tools.execute", "agents.manage", "usage.read"],
    "rate_limit": 100,           // requests per minute
    "allowed_ips": ["203.0.113.0/24"],  // optional IP whitelist
    "expires": "2027-02-08"      // optional expiration
}</code></pre>

<p>Your API key is displayed once at creation. Store it securely — in environment variables, a secrets manager, or your deployment platform's secret store. Never commit API keys to version control.</p>

<pre><code># Environment variable (recommended)
export ALFRED_API_KEY="ak_live_abc123def456ghi789"

# .env file (add to .gitignore)
ALFRED_API_KEY=ak_live_abc123def456ghi789</code></pre>

<h2>Step 2: Make Your First API Call</h2>
<p>The API base URL is <code>https://api.gositeme.com/v1</code>. All requests require the <code>Authorization</code> header with your API key.</p>

<h3>JavaScript (Node.js)</h3>
<pre><code>const response = await fetch('https://api.gositeme.com/v1/tools', {
    headers: {
        'Authorization': 'Bearer ' + process.env.ALFRED_API_KEY
    }
});

const tools = await response.json();
console.log(`Available tools: ${tools.total}`);
console.log('Categories:', tools.categories.join(', '));</code></pre>

<h3>Python</h3>
<pre><code>import os
import requests

api_key = os.environ['ALFRED_API_KEY']
headers = {'Authorization': f'Bearer {api_key}'}

response = requests.get('https://api.gositeme.com/v1/tools', headers=headers)
tools = response.json()

print(f"Available tools: {tools['total']}")
print(f"Categories: {', '.join(tools['categories'])}")</code></pre>

<h3>PHP</h3>
<pre><code>$api_key = getenv('ALFRED_API_KEY');

$ch = curl_init('https://api.gositeme.com/v1/tools');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $api_key,
    ],
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Available tools: " . $response['total'] . "\n";
echo "Categories: " . implode(', ', $response['categories']) . "\n";</code></pre>

<h2>Step 3: Execute Your First Tool</h2>
<p>Tool execution is the core of the Alfred API. You send a tool name and input parameters, and receive the AI-generated output.</p>

<h3>JavaScript</h3>
<pre><code>const result = await fetch('https://api.gositeme.com/v1/tools/execute', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + process.env.ALFRED_API_KEY,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        tool: 'seo-meta-generator',
        input: {
            url: 'https://example.com/product/widget-pro',
            title: 'Widget Pro — Enterprise Widget Solution',
            description: 'The most advanced widget platform for enterprise teams',
            keywords: ['widget software', 'enterprise widgets', 'widget management']
        },
        options: {
            format: 'json',
            include_og_tags: true,
            include_schema: true
        }
    })
});

const output = await result.json();
console.log('Meta Title:', output.result.meta_title);
console.log('Meta Description:', output.result.meta_description);
console.log('OG Tags:', JSON.stringify(output.result.og_tags, null, 2));</code></pre>

<h3>Python</h3>
<pre><code>result = requests.post(
    'https://api.gositeme.com/v1/tools/execute',
    headers=headers,
    json={
        'tool': 'content-writer',
        'input': {
            'topic': 'Benefits of edge computing for IoT applications',
            'length': 1500,
            'tone': 'technical but accessible',
            'audience': 'IT decision makers',
            'include_sections': ['introduction', 'benefits', 'challenges', 'conclusion']
        },
        'options': {
            'format': 'markdown',
            'seo_optimize': True,
            'reading_level': 'professional'
        }
    }
)

article = result.json()
print(f"Word count: {article['result']['word_count']}")
print(f"SEO score: {article['result']['seo_score']}/100")
print(f"\n{article['result']['content']}")</code></pre>

<h3>PHP</h3>
<pre><code>$ch = curl_init('https://api.gositeme.com/v1/tools/execute');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'tool' => 'code-generator',
        'input' => [
            'language' => 'php',
            'description' => 'A function that validates Canadian postal codes and returns the province',
            'include_tests' => true,
            'include_docs' => true
        ]
    ])
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo $response['result']['code'];
echo "\n\n// Tests:\n";
echo $response['result']['tests'];</code></pre>

<h2>Step 4: Handle Responses and Errors</h2>
<p>The API uses standard HTTP status codes and returns structured error responses:</p>

<pre><code>// Success response (200)
{
    "status": "success",
    "result": { ... },
    "usage": {
        "input_tokens": 245,
        "output_tokens": 1820,
        "total_tokens": 2065,
        "cost_usd": 0.0041
    },
    "metadata": {
        "tool": "content-writer",
        "model": "claude-3.5-sonnet",
        "duration_ms": 4200,
        "request_id": "req_abc123"
    }
}

// Error response (4xx/5xx)
{
    "status": "error",
    "error": {
        "code": "rate_limit_exceeded",
        "message": "Rate limit of 100 requests/minute exceeded. Retry after 12 seconds.",
        "retry_after": 12
    },
    "request_id": "req_def456"
}</code></pre>

<h3>Robust Error Handling (JavaScript)</h3>
<pre><code>async function executeTool(tool, input, options = {}) {
    const maxRetries = 3;
    let lastError;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            const response = await fetch('https://api.gositeme.com/v1/tools/execute', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${process.env.ALFRED_API_KEY}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tool, input, options })
            });

            if (response.status === 429) {
                const retryAfter = response.headers.get('Retry-After') || 10;
                console.log(`Rate limited. Retrying in ${retryAfter}s...`);
                await new Promise(r => setTimeout(r, retryAfter * 1000));
                continue;
            }

            if (!response.ok) {
                const err = await response.json();
                throw new Error(`API error: ${err.error.code} — ${err.error.message}`);
            }

            return await response.json();
        } catch (error) {
            lastError = error;
            if (attempt < maxRetries) {
                await new Promise(r => setTimeout(r, 1000 * attempt));
            }
        }
    }
    throw lastError;
}</code></pre>

<h2>Step 5: Working with Agents</h2>
<p>Agents are persistent AI workers that maintain context, follow configured workflows, and use specific tool sets. The API provides full agent lifecycle management.</p>

<h3>Create an Agent</h3>
<pre><code># Python - Create a content agent
agent = requests.post(
    'https://api.gositeme.com/v1/agents',
    headers=headers,
    json={
        'name': 'BlogWriter',
        'role': 'Technical Content Writer',
        'description': 'Writes SEO-optimized technical blog posts for SaaS audiences',
        'tools': ['keyword-research', 'content-writer', 'seo-optimizer', 'meta-tag-generator'],
        'config': {
            'temperature': 0.7,
            'max_tokens': 10000,
            'tone': 'professional, data-driven',
            'language': 'en-US'
        }
    }
).json()

agent_id = agent['id']
print(f"Agent created: {agent_id}")</code></pre>

<h3>Submit a Task to an Agent</h3>
<pre><code># Python - Submit a blog writing task
task = requests.post(
    f'https://api.gositeme.com/v1/agents/{agent_id}/tasks',
    headers=headers,
    json={
        'instruction': 'Write a 1500-word blog post about serverless architecture best practices',
        'workflow': 'blog_post',
        'context': {
            'target_audience': 'backend developers',
            'keywords': ['serverless best practices', 'aws lambda tips', 'serverless architecture'],
            'internal_links': [
                {'text': 'Alfred AI Tools', 'url': 'https://gositeme.com/alfred-tools.php'},
                {'text': 'Developer Portal', 'url': 'https://gositeme.com/developer-portal.php'}
            ]
        },
        'callback_url': 'https://yourapp.com/webhooks/alfred'
    }
).json()

task_id = task['id']
print(f"Task submitted: {task_id}, status: {task['status']}")</code></pre>

<h3>Poll for Task Completion</h3>
<pre><code># Python - Check task status
import time

while True:
    status = requests.get(
        f'https://api.gositeme.com/v1/agents/{agent_id}/tasks/{task_id}',
        headers=headers
    ).json()

    print(f"Status: {status['status']} ({status['steps_completed']}/{status['steps_total']} steps)")

    if status['status'] in ('completed', 'failed'):
        break

    time.sleep(5)

if status['status'] == 'completed':
    print(f"\nWord count: {status['output']['word_count']}")
    print(f"SEO score: {status['output']['seo_score']}")
    print(f"\n{status['output']['content'][:500]}...")</code></pre>

<h2>Step 6: Webhooks for Async Operations</h2>
<p>For production systems, polling is inefficient. Webhooks let Alfred push results to your application as soon as they are ready.</p>

<h3>Setting Up a Webhook Endpoint</h3>
<pre><code>// Express.js webhook handler
const express = require('express');
const crypto = require('crypto');
const app = express();

app.use(express.json());

app.post('/webhooks/alfred', (req, res) => {
    // Verify webhook signature
    const signature = req.headers['x-alfred-signature'];
    const expected = crypto
        .createHmac('sha256', process.env.ALFRED_WEBHOOK_SECRET)
        .update(JSON.stringify(req.body))
        .digest('hex');

    if (signature !== expected) {
        return res.status(401).json({ error: 'Invalid signature' });
    }

    const event = req.body;
    console.log(`Event: ${event.type}, Task: ${event.task_id}`);

    switch (event.type) {
        case 'task.completed':
            handleTaskComplete(event.data);
            break;
        case 'task.failed':
            handleTaskFailed(event.data);
            break;
        case 'task.approval_required':
            handleApprovalRequest(event.data);
            break;
    }

    res.json({ received: true });
});</code></pre>

<h3>Webhook Event Types</h3>
<pre><code>// Webhook payload structure
{
    "id": "evt_abc123",
    "type": "task.completed",
    "timestamp": "2026-02-08T15:30:00Z",
    "task_id": "task_789",
    "agent_id": "agent_456",
    "data": {
        "status": "completed",
        "output": { ... },
        "usage": {
            "tokens": 4200,
            "cost_usd": 0.0084,
            "duration_seconds": 18
        }
    }
}</code></pre>

<h2>Step 7: Batch Operations</h2>
<p>For high-volume workloads, the batch API lets you submit multiple tool executions in a single request, reducing overhead and improving throughput.</p>

<pre><code>// JavaScript - Batch tool execution
const batch = await fetch('https://api.gositeme.com/v1/tools/batch', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${process.env.ALFRED_API_KEY}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        operations: [
            {
                id: 'meta-homepage',
                tool: 'seo-meta-generator',
                input: { url: 'https://example.com/', title: 'Homepage' }
            },
            {
                id: 'meta-about',
                tool: 'seo-meta-generator',
                input: { url: 'https://example.com/about', title: 'About Us' }
            },
            {
                id: 'meta-pricing',
                tool: 'seo-meta-generator',
                input: { url: 'https://example.com/pricing', title: 'Pricing' }
            }
        ],
        parallel: true  // Execute operations concurrently
    })
});

const results = await batch.json();
results.operations.forEach(op => {
    console.log(`${op.id}: ${op.status} (${op.usage.total_tokens} tokens)`);
});</code></pre>

<h2>Step 8: Streaming Responses</h2>
<p>For long-running tool executions, streaming lets your application display results progressively as they are generated by the AI model.</p>

<pre><code>// JavaScript - Stream a content generation response
const response = await fetch('https://api.gositeme.com/v1/tools/execute', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${process.env.ALFRED_API_KEY}`,
        'Content-Type': 'application/json',
        'Accept': 'text/event-stream'
    },
    body: JSON.stringify({
        tool: 'content-writer',
        input: { topic: 'Cloud migration strategies', length: 2000 },
        stream: true
    })
});

const reader = response.body.getReader();
const decoder = new TextDecoder();
let content = '';

while (true) {
    const { done, value } = await reader.read();
    if (done) break;

    const chunk = decoder.decode(value);
    const lines = chunk.split('\n').filter(l => l.startsWith('data: '));

    for (const line of lines) {
        const data = JSON.parse(line.slice(6));
        if (data.type === 'content') {
            content += data.text;
            process.stdout.write(data.text);  // Display progressively
        }
        if (data.type === 'done') {
            console.log(`\n\nTokens used: ${data.usage.total_tokens}`);
        }
    }
}</code></pre>

<h2>Rate Limits and Best Practices</h2>
<p>Alfred enforces rate limits to ensure fair usage and platform stability:</p>

<table style="width:100%; border-collapse:collapse; margin: 24px 0; font-size: 0.95rem;">
<thead>
<tr style="border-bottom: 2px solid rgba(108,92,231,0.3);">
    <th style="text-align:left; padding:12px; color:#a29bfe;">Plan</th>
    <th style="text-align:center; padding:12px; color:#6c5ce7;">Requests/min</th>
    <th style="text-align:center; padding:12px; color:#6c5ce7;">Tokens/day</th>
    <th style="text-align:center; padding:12px; color:#6c5ce7;">Concurrent</th>
</tr>
</thead>
<tbody>
<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
    <td style="padding:12px; color:#c0c0d8;">Free</td>
    <td style="text-align:center; padding:12px; color:#888;">10</td>
    <td style="text-align:center; padding:12px; color:#888;">5,000</td>
    <td style="text-align:center; padding:12px; color:#888;">2</td>
</tr>
<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
    <td style="padding:12px; color:#c0c0d8;">Pro</td>
    <td style="text-align:center; padding:12px; color:#888;">60</td>
    <td style="text-align:center; padding:12px; color:#888;">100,000</td>
    <td style="text-align:center; padding:12px; color:#888;">10</td>
</tr>
<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
    <td style="padding:12px; color:#c0c0d8;">Business</td>
    <td style="text-align:center; padding:12px; color:#888;">200</td>
    <td style="text-align:center; padding:12px; color:#888;">500,000</td>
    <td style="text-align:center; padding:12px; color:#888;">25</td>
</tr>
<tr>
    <td style="padding:12px; color:#c0c0d8;">Enterprise</td>
    <td style="text-align:center; padding:12px; color:#888;">Custom</td>
    <td style="text-align:center; padding:12px; color:#888;">Custom</td>
    <td style="text-align:center; padding:12px; color:#888;">Custom</td>
</tr>
</tbody>
</table>

<h3>Best Practices</h3>
<ul>
    <li><strong>Cache aggressively:</strong> If you call the same tool with the same inputs repeatedly, cache the results. Alfred returns a <code>cache-key</code> header that you can use for cache invalidation.</li>
    <li><strong>Use batch operations:</strong> When processing multiple items, use the batch API instead of individual requests. It reduces HTTP overhead and often completes faster.</li>
    <li><strong>Implement exponential backoff:</strong> When rate-limited, back off exponentially rather than retrying immediately. The <code>Retry-After</code> header tells you exactly how long to wait.</li>
    <li><strong>Set appropriate timeouts:</strong> Tool executions vary in duration. Content generation may take 5-15 seconds; code generation 3-10 seconds; simple utilities under 1 second. Set HTTP timeouts accordingly.</li>
    <li><strong>Monitor usage:</strong> Use the <code>/v1/usage</code> endpoint to track token consumption and costs. Set up alerts before you hit plan limits.</li>
    <li><strong>Use webhooks for long tasks:</strong> Agent tasks and batch operations can take minutes. Use webhooks instead of long-polling to receive results.</li>
</ul>

<h2>SDK Libraries</h2>
<p>Official SDKs simplify integration:</p>

<pre><code># Python
pip install alfred-ai

# Node.js
npm install @gositeme/alfred-sdk

# PHP
composer require gositeme/alfred-sdk</code></pre>

<h3>Python SDK Example</h3>
<pre><code>import alfred

client = alfred.Client()  # Reads ALFRED_API_KEY from env

# Execute a tool
result = client.tools.execute(
    tool='content-writer',
    input={'topic': 'GraphQL vs REST', 'length': 1200}
)
print(result.content)

# Create and use an agent
agent = client.agents.create(name='Writer', role='Content Writer', tools=['content-writer'])
task = agent.tasks.create(instruction='Write about microservices patterns')
output = task.wait()  # Blocks until complete
print(output.content)</code></pre>

<h3>Node.js SDK Example</h3>
<pre><code>import Alfred from '@gositeme/alfred-sdk';

const client = new Alfred();  // Reads ALFRED_API_KEY from env

// Execute a tool
const result = await client.tools.execute({
    tool: 'code-generator',
    input: { language: 'typescript', description: 'A rate limiter middleware for Express' }
});
console.log(result.code);

// Stream a response
const stream = await client.tools.execute({
    tool: 'content-writer',
    input: { topic: 'Kubernetes best practices' },
    stream: true
});

for await (const chunk of stream) {
    process.stdout.write(chunk.text);
}</code></pre>

<h2>Next Steps</h2>
<p>You now have everything you need to build production integrations with the Alfred API. Here are resources to continue:</p>

<ul>
    <li><a href="/developer-portal.php">Developer Portal</a> — Full API reference, interactive playground, and SDK documentation</li>
    <li><a href="/alfred-tools.php">Tool Directory</a> — Browse all 1,220+ tools with input/output specifications</li>
    <li><a href="/fleet-dashboard.php">Fleet Dashboard</a> — Visual agent and fleet management interface</li>
    <li><a href="/articles/building-ai-agents-guide">Agent Building Guide</a> — Deep dive into agent architectures and patterns</li>
</ul>

<div class="article-cta">
    <h3>Start Building with the Alfred API</h3>
    <p>Get your API key and start executing tools in minutes. Free tier includes 5,000 tokens per day — enough to build and test your integration.</p>
    <a href="/developer-portal.php" class="btn"><i class="fas fa-code"></i> Get Your API Key</a>
    <a href="/pricing.php" class="btn" style="background:transparent;border:1px solid rgba(108,92,231,0.4);margin-left:12px;"><i class="fas fa-tag"></i> View Plans</a>
</div>

<?php
$article_content = ob_get_clean();
include __DIR__ . '/article-template.inc.php';
