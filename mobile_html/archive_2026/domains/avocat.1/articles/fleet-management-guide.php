<?php
$article_meta = [
    'title' => 'AI Fleet Management: Running 100 Agents from One Dashboard',
    'description' => 'Learn how to deploy, manage, and monitor up to 100 concurrent AI agents using Alfred Fleet Management. A complete tutorial for agencies and power users.',
    'date' => '2026-02-05',
    'author' => 'GoSiteMe Team',
    'category' => 'tutorials',
    'read_time' => '10 min read',
    'featured_image' => '/assets/img/blog/placeholder.png',
    'tags' => ['fleet-management', 'AI-agents', 'tutorial', 'agencies', 'automation'],
    'slug' => 'fleet-management-guide',
];

ob_start();
?>

<h2>What Is Fleet Management?</h2>
<p>Alfred Fleet Management is GoSiteMe's enterprise-grade system for running multiple AI agents simultaneously from a single dashboard. Think of it as mission control for your AI workforce: each agent operates independently with its own context, tools, and tasks, while you maintain complete visibility and control over all of them from one interface.</p>

<p>For digital agencies, development teams, and power users managing multiple projects or clients, Fleet Management transforms Alfred from a personal assistant into a scalable AI team. You can have one agent writing content for Client A, another auditing Client B's website, a third generating code for an internal project, and dozens more running automated workflows — all at the same time.</p>

<h2>Setting Up Your First Fleet</h2>
<p>Getting started with Fleet Management requires a GoSiteMe Pro or Enterprise plan. Here's the setup process:</p>

<h3>Step 1: Access Fleet Dashboard</h3>
<p>Navigate to your Alfred dashboard and click "Fleet" in the left sidebar. The Fleet Dashboard shows a real-time overview of all active agents, their current tasks, token consumption, and status indicators. On first visit, you'll see an empty fleet with a "Create Agent" button.</p>

<h3>Step 2: Create Your First Agent</h3>
<p>Click "Create Agent" and configure:</p>
<ul>
    <li><strong>Agent Name:</strong> Give it a descriptive name like "Content-Writer-ClientA" or "SEO-Auditor-Q1"</li>
    <li><strong>Agent Role:</strong> Select a pre-configured role (Content Writer, Code Developer, SEO Specialist, Data Analyst, etc.) or create a custom role</li>
    <li><strong>Tool Access:</strong> Specify which of the 1,220+ tools this agent can use. A content writing agent doesn't need DevOps tools, and restricting access improves performance and security</li>
    <li><strong>Token Budget:</strong> Set a maximum token allocation per session or per day to control costs</li>
    <li><strong>Context Window:</strong> Upload project files, brand guidelines, or reference materials that the agent should use</li>
</ul>

<h3>Step 3: Deploy and Monitor</h3>
<p>Once configured, click "Deploy" to activate the agent. It appears on your Fleet Dashboard with a green status indicator. You can now assign tasks via the command line, API, or by chatting with the agent directly.</p>

<h2>Fleet Architecture</h2>
<p>Understanding the architecture helps you design efficient fleets:</p>

<pre><code>Fleet Controller (Your Dashboard)
├── Agent Group: Content Team
│   ├── Blog Writer Agent      [Active] → 3 tasks queued
│   ├── Social Media Agent     [Active] → Running task
│   └── Email Newsletter Agent [Idle]
├── Agent Group: Development
│   ├── Frontend Dev Agent     [Active] → Building component
│   ├── Backend API Agent      [Active] → Running tests
│   └── QA Testing Agent       [Idle]   → Awaiting deployment
└── Agent Group: Client Work
    ├── ClientA SEO Agent      [Active] → Site audit
    ├── ClientB Content Agent  [Active] → Writing articles
    └── ClientC Design Agent   [Paused] → Awaiting approval
</code></pre>

<p>Each agent group can share context and communicate with each other. The Frontend Dev Agent can pass completed components to the QA Testing Agent automatically, creating a pipeline that mirrors real development workflows.</p>

<h2>Advanced Fleet Patterns</h2>

<h3>The Assembly Line</h3>
<p>Set up agents in a sequential pipeline where each agent's output becomes the next agent's input:</p>
<ol>
    <li><strong>Research Agent</strong> gathers data and creates a brief</li>
    <li><strong>Writer Agent</strong> produces a draft from the brief</li>
    <li><strong>SEO Agent</strong> optimizes the draft for search engines</li>
    <li><strong>Editor Agent</strong> proofreads and polishes the final version</li>
    <li><strong>Social Agent</strong> creates promotional content from the finished piece</li>
</ol>

<p>This pattern is especially powerful for content agencies. One fleet running this pipeline can produce 20-30 polished, SEO-optimized articles per day — work that would typically require a team of 5-8 people.</p>

<h3>The Swarm</h3>
<p>Deploy multiple identical agents to parallelize a large task. Need to audit 500 web pages for accessibility? Deploy 10 Accessibility Auditor agents, each processing 50 pages simultaneously. What would take one agent 8 hours completes in under an hour.</p>

<h3>The Specialist Team</h3>
<p>Create a cross-functional team of specialized agents that collaborate on complex projects. For a website redesign project:</p>
<ul>
    <li><strong>UX Researcher Agent</strong> analyzes current user behavior and competitor sites</li>
    <li><strong>Design System Agent</strong> creates a comprehensive design token system</li>
    <li><strong>Frontend Agent</strong> builds responsive components using the design tokens</li>
    <li><strong>Performance Agent</strong> optimizes assets and code for Core Web Vitals</li>
    <li><strong>Copy Agent</strong> writes all page content optimized for conversion</li>
</ul>

<h2>Monitoring and Analytics</h2>
<p>The Fleet Dashboard provides real-time visibility:</p>
<ul>
    <li><strong>Agent Status Map:</strong> Visual overview of all agents showing active, idle, paused, and error states</li>
    <li><strong>Token Consumption:</strong> Per-agent and per-group token usage with burn rate projections</li>
    <li><strong>Task Queue:</strong> View pending, in-progress, and completed tasks across all agents</li>
    <li><strong>Output Gallery:</strong> Review all agent outputs from one centralized location</li>
    <li><strong>Performance Metrics:</strong> Track average task completion times, quality scores, and efficiency metrics</li>
    <li><strong>Cost Dashboard:</strong> Real-time cost tracking with budget alerts and spending forecasts</li>
</ul>

<h2>Fleet API</h2>
<p>For programmatic control, the Fleet API lets you manage agents from your own applications:</p>

<pre><code>// Create a new agent via API
const response = await fetch('https://gositeme.com/api/fleet', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer YOUR_API_KEY',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: 'SEO-Auditor-March',
        role: 'seo-specialist',
        tools: ['seo-analyzer', 'keyword-research', 'schema-generator'],
        token_budget: 50000,
        context: { project: 'client-website-redesign' }
    })
});

// Assign a task
await fetch('https://gositeme.com/api/fleet/agents/SEO-Auditor-March/tasks', {
    method: 'POST',
    headers: { 'Authorization': 'Bearer YOUR_API_KEY' },
    body: JSON.stringify({
        task: 'Perform a complete SEO audit of https://example.com',
        output_format: 'pdf-report',
        priority: 'high'
    })
});
</code></pre>

<h2>Best Practices for Large Fleets</h2>
<ul>
    <li><strong>Start small, scale gradually.</strong> Begin with 3-5 agents, optimize their configurations, then replicate successful patterns.</li>
    <li><strong>Use agent groups</strong> to organize by client, project, or function. This keeps your dashboard manageable at scale.</li>
    <li><strong>Set token budgets</strong> for every agent. Runaway agents without budget limits can consume resources quickly.</li>
    <li><strong>Review output quality weekly.</strong> Use the Output Gallery to spot-check agent work and refine configurations.</li>
    <li><strong>Archive completed agents</strong> to keep your active fleet focused. Archived agents retain their context for future reuse.</li>
    <li><strong>Use webhooks</strong> for task completion notifications instead of polling the dashboard.</li>
</ul>

<div class="article-cta">
    <h3>Deploy Your AI Fleet</h3>
    <p>Start running multiple AI agents from one dashboard. Scale from 1 to 100 agents with full control and visibility.</p>
    <a href="/fleet-dashboard.php" class="btn"><i class="fas fa-satellite-dish"></i> Open Fleet Dashboard</a>
</div>

<?php
$article_content = ob_get_clean();
include __DIR__ . '/article-template.inc.php';
