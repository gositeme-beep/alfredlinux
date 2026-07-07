<?php
$page_title = 'Help Center - Alfred AI';
$page_description = 'Find answers, tutorials, and guides for Alfred AI. Browse our knowledge base or contact support.';
$page_canonical = 'https://gositeme.com/help';
$noGlobalMain = true;
require_once 'includes/site-header.inc.php';

// ── Article Data ──────────────────────────────────────────────────────
$categories = [
    'getting-started' => [
        'title' => 'Getting Started',
        'icon'  => 'fa-rocket',
        'color' => '#6c5ce7',
        'articles' => [
            [
                'id'      => 'gs-first-agent',
                'title'   => 'Creating Your First Agent',
                'tags'    => 'agent create new setup beginner onboarding',
                'updated' => '2026-02-28',
                'content' => '<p>Getting your first Alfred AI agent up and running takes just a few minutes. Follow these steps to create a fully functional AI agent.</p>
<h4>Step 1: Access the Dashboard</h4>
<p>Log in to your account at <a href="/dashboard">gositeme.com/dashboard</a>. If you don\'t have an account yet, <a href="/pricing.php">sign up for a plan</a> first.</p>
<h4>Step 2: Create a New Agent</h4>
<ol>
<li>Click the <strong>"+ New Agent"</strong> button in the top-right corner of your dashboard.</li>
<li>Choose a name for your agent (e.g., "Customer Support Bot").</li>
<li>Select a base template or start from scratch.</li>
<li>Choose your agent\'s primary language.</li>
</ol>
<h4>Step 3: Configure Your Agent</h4>
<p>Set the agent\'s personality, tone, and knowledge base. You can upload documents, link URLs, or type in custom instructions.</p>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Start with a template — you can fully customize it later. Templates give you a head start with proven configurations.</div>
<h4>Step 4: Test &amp; Deploy</h4>
<p>Use the built-in chat preview to test your agent. When you\'re satisfied, click <strong>"Deploy"</strong> to make it live. You can embed it on your website, connect it to a phone number, or integrate via API.</p>
<p>For more, see our <a href="/docs/getting-started">Getting Started documentation</a>.</p>'
            ],
            [
                'id'      => 'gs-dashboard',
                'title'   => 'Understanding the Dashboard',
                'tags'    => 'dashboard navigation overview UI interface',
                'updated' => '2026-02-25',
                'content' => '<p>The Alfred AI dashboard is your command center for managing agents, monitoring performance, and configuring settings.</p>
<h4>Dashboard Layout</h4>
<ul>
<li><strong>Left Sidebar:</strong> Navigation to Agents, Fleet, Voice, Analytics, Settings, and more.</li>
<li><strong>Top Bar:</strong> Quick search, notifications, and account menu.</li>
<li><strong>Main Area:</strong> Content panels that change based on where you navigate.</li>
<li><strong>Status Bar:</strong> Current plan, usage meters, and quick actions.</li>
</ul>
<h4>Key Sections</h4>
<p><strong>Agents Panel:</strong> View, create, edit, and delete your AI agents. Each card shows status (active/paused), conversation count, and satisfaction score.</p>
<p><strong>Analytics:</strong> Track usage, conversations, token consumption, and customer satisfaction in real time. Access via <a href="/analytics.php">Analytics</a>.</p>
<p><strong>Quick Actions:</strong> Use the <kbd>Ctrl+K</kbd> (or <kbd>⌘+K</kbd>) shortcut to open the command palette for rapid navigation.</p>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Dashboard widgets can be rearranged by dragging them. Right-click to pin or hide components.</div>'
            ],
            [
                'id'      => 'gs-voice-setup',
                'title'   => 'Setting Up Voice',
                'tags'    => 'voice phone number setup telephony call',
                'updated' => '2026-03-01',
                'content' => '<p>Alfred AI supports voice interactions out of the box. Here\'s how to set up voice for your agent.</p>
<h4>Step 1: Get a Phone Number</h4>
<p>Navigate to <a href="/voice-products.php">Voice &amp; AI Products</a> and purchase a phone number. Numbers are available in 60+ countries.</p>
<h4>Step 2: Assign to an Agent</h4>
<ol>
<li>Go to your agent\'s settings in the <a href="/dashboard">Dashboard</a>.</li>
<li>Click the <strong>"Voice"</strong> tab.</li>
<li>Select your purchased phone number from the dropdown.</li>
<li>Choose a voice (default or <a href="/voice-cloning.php">clone your own</a>).</li>
</ol>
<h4>Step 3: Configure Voice Settings</h4>
<p>Set greeting message, call timeout, transfer number for escalation, and working hours. You can also enable voicemail and call recording.</p>
<h4>Step 4: Test It</h4>
<p>Call your assigned number to test the experience. Use the <a href="/voice-portal.php">Voice Portal</a> to monitor live calls.</p>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Enable call recording during testing so you can review and refine your agent\'s responses.</div>'
            ],
            [
                'id'      => 'gs-channels',
                'title'   => 'Connecting Channels',
                'tags'    => 'channels integrations embed widget website slack',
                'updated' => '2026-02-20',
                'content' => '<p>Alfred AI agents can be deployed across multiple channels to meet your customers wherever they are.</p>
<h4>Available Channels</h4>
<ul>
<li><strong>Web Widget:</strong> Embed on any website with a single script tag.</li>
<li><strong>Phone / Voice:</strong> Assign a phone number for voice interactions.</li>
<li><strong>API:</strong> Connect via REST API for custom integrations.</li>
<li><strong>Webhooks:</strong> Receive real-time events in your applications.</li>
<li><strong>White-Label:</strong> Fully branded experience for your customers — see <a href="/white-label.php">White-Label</a>.</li>
</ul>
<h4>Embedding the Web Widget</h4>
<pre><code>&lt;script src="https://gositeme.com/assets/js/alfred-widget.js"
  data-agent-id="YOUR_AGENT_ID"
  data-theme="dark"&gt;&lt;/script&gt;</code></pre>
<p>Place this code before the closing <code>&lt;/body&gt;</code> tag on your website. The widget will appear in the bottom-right corner by default.</p>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> You can customize the widget\'s position, colors, greeting, and behavior from your agent\'s settings page.</div>'
            ],
            [
                'id'      => 'gs-first-api',
                'title'   => 'Your First API Call',
                'tags'    => 'API curl first call request response',
                'updated' => '2026-03-02',
                'content' => '<p>Alfred AI provides a powerful REST API. Here\'s how to make your first API call.</p>
<h4>Step 1: Get Your API Key</h4>
<p>Go to <a href="/developer-portal.php">Developer Portal</a> → API Keys → <strong>"Create New Key"</strong>. Copy and store your key securely — it won\'t be shown again.</p>
<h4>Step 2: Make a Request</h4>
<pre><code>curl -X POST https://gositeme.com/api/v1/chat \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d \'{"agent_id": "ag_abc123", "message": "Hello!"}\'</code></pre>
<h4>Step 3: Handle the Response</h4>
<pre><code>{
  "status": "success",
  "response": "Hello! How can I help you today?",
  "conversation_id": "conv_xyz789",
  "tokens_used": 42
}</code></pre>
<div class="help-callout help-callout-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Never expose your API key in client-side code. Always call the API from your server backend.</div>
<p>For full API documentation, visit the <a href="/docs/api-reference">API Reference</a>.</p>'
            ],
        ]
    ],
    'account-billing' => [
        'title' => 'Account & Billing',
        'icon'  => 'fa-credit-card',
        'color' => '#0984e3',
        'articles' => [
            [
                'id'      => 'ab-subscription',
                'title'   => 'Managing Your Subscription',
                'tags'    => 'subscription plan manage change downgrade',
                'updated' => '2026-02-28',
                'content' => '<p>You can manage your Alfred AI subscription at any time from your account settings.</p>
<h4>Viewing Your Current Plan</h4>
<p>Go to <a href="/dashboard">Dashboard</a> → <strong>Settings</strong> → <strong>Subscription</strong>. Here you\'ll see your current plan, renewal date, and usage summary.</p>
<h4>Changing Your Plan</h4>
<ol>
<li>Click <strong>"Change Plan"</strong> from the subscription page.</li>
<li>Compare available plans on the <a href="/pricing.php">Pricing page</a>.</li>
<li>Select your desired plan and confirm.</li>
<li>If upgrading, the prorated difference is charged immediately.</li>
<li>If downgrading, the change takes effect at your next billing cycle.</li>
</ol>
<h4>Cancellation</h4>
<p>To cancel, go to Settings → Subscription → <strong>"Cancel Subscription"</strong>. Your account will remain active until the end of your current billing period. All data is retained for 30 days after cancellation.</p>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Enterprise plans require contacting your account manager for changes.</div>'
            ],
            [
                'id'      => 'ab-usage-limits',
                'title'   => 'Understanding Usage Limits',
                'tags'    => 'usage limits tokens conversations quota overage',
                'updated' => '2026-02-22',
                'content' => '<p>Each Alfred AI plan includes specific usage allocations. Understanding these limits helps you choose the right plan.</p>
<h4>What Counts as Usage?</h4>
<ul>
<li><strong>Tokens:</strong> Every AI interaction consumes tokens. A typical conversation uses 500–2,000 tokens.</li>
<li><strong>Conversations:</strong> A conversation is a complete session from start to finish.</li>
<li><strong>Voice Minutes:</strong> Inbound and outbound call minutes are tracked separately.</li>
<li><strong>Agents:</strong> Number of active agents you can run simultaneously.</li>
</ul>
<h4>Monitoring Usage</h4>
<p>Track your usage in real time at <a href="/analytics.php">Analytics</a>. The dashboard shows daily/monthly trends with visual progress bars.</p>
<h4>What Happens at the Limit?</h4>
<p>When you reach 80% of your allocation, you\'ll receive an email notification. At 100%, agents pause until the next billing cycle unless you purchase a <a href="/pricing.php">Token Pack</a> add-on.</p>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Enable auto-refill in Settings to automatically purchase token packs and avoid interruptions.</div>'
            ],
            [
                'id'      => 'ab-upgrade',
                'title'   => 'Upgrading Your Plan',
                'tags'    => 'upgrade plan pro enterprise features',
                'updated' => '2026-03-01',
                'content' => '<p>Upgrading unlocks more agents, higher token limits, advanced features, and priority support.</p>
<h4>How to Upgrade</h4>
<ol>
<li>Visit the <a href="/pricing.php">Pricing page</a> or go to Dashboard → Settings → Subscription.</li>
<li>Click <strong>"Upgrade"</strong> next to your desired plan.</li>
<li>Review the feature comparison.</li>
<li>Confirm payment — you\'ll only be charged the prorated amount for the remainder of your current billing cycle.</li>
</ol>
<h4>Plan Comparison</h4>
<ul>
<li><strong>Starter ($3.99/mo):</strong> 1 agent, 10K tokens, basic tools.</li>
<li><strong>Pro ($19.99/mo):</strong> 5 agents, 100K tokens, voice, analytics.</li>
<li><strong>Business ($49.99/mo):</strong> 25 agents, 500K tokens, fleet management, API access.</li>
<li><strong>Enterprise (Custom):</strong> Unlimited agents, dedicated support, SLA, SSO, RBAC.</li>
</ul>
<p>See full details on the <a href="/compare.php">Compare page</a>.</p>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Annual billing saves you up to 20%. You can switch from monthly to annual at any time.</div>'
            ],
            [
                'id'      => 'ab-payment',
                'title'   => 'Payment Methods',
                'tags'    => 'payment credit card paypal billing method',
                'updated' => '2026-02-18',
                'content' => '<p>Alfred AI accepts multiple payment methods for your convenience.</p>
<h4>Accepted Payment Methods</h4>
<ul>
<li><strong>Credit/Debit Cards:</strong> Visa, Mastercard, American Express, Discover.</li>
<li><strong>PayPal:</strong> Link your PayPal account for automatic billing.</li>
<li><strong>Bank Transfer:</strong> Available for Enterprise plans (annual billing only).</li>
<li><strong>Crypto:</strong> Bitcoin and Ethereum accepted for annual plans.</li>
</ul>
<h4>Updating Your Payment Method</h4>
<ol>
<li>Go to Dashboard → Settings → <strong>Billing</strong>.</li>
<li>Click <strong>"Update Payment Method"</strong>.</li>
<li>Enter your new card details or link PayPal.</li>
<li>Click <strong>"Save"</strong>.</li>
</ol>
<h4>Payment Security</h4>
<p>All payments are processed through Stripe with PCI-DSS Level 1 compliance. We never store your full card number on our servers.</p>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> If a payment fails, we\'ll retry 3 times over 7 days. Your service continues during this grace period.</div>'
            ],
            [
                'id'      => 'ab-invoices',
                'title'   => 'Invoices and Receipts',
                'tags'    => 'invoice receipt download PDF tax billing history',
                'updated' => '2026-02-15',
                'content' => '<p>Access and download invoices for all your Alfred AI payments.</p>
<h4>Viewing Invoices</h4>
<ol>
<li>Go to Dashboard → Settings → <strong>Billing</strong> → <strong>"Invoice History"</strong>.</li>
<li>You\'ll see a list of all past invoices with date, amount, and status.</li>
<li>Click any invoice to view details or download a PDF.</li>
</ol>
<h4>Invoice Contents</h4>
<p>Each invoice includes: plan name, billing period, usage breakdown, taxes, total amount, and payment method used.</p>
<h4>Tax Information</h4>
<p>If you need to add a VAT/Tax ID to your invoices:</p>
<ol>
<li>Go to Settings → Billing → <strong>"Tax Information"</strong>.</li>
<li>Enter your company name and tax number.</li>
<li>All future invoices will include this information. You can also regenerate past invoices.</li>
</ol>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Enable email invoices in your notification settings to receive a PDF copy automatically each billing cycle.</div>'
            ],
        ]
    ],
    'agents-fleets' => [
        'title' => 'Agents & Fleets',
        'icon'  => 'fa-robot',
        'color' => '#00b894',
        'articles' => [
            [
                'id'      => 'af-custom-agents',
                'title'   => 'Creating Custom Agents',
                'tags'    => 'agent custom create personality prompt instructions',
                'updated' => '2026-03-01',
                'content' => '<p>Alfred AI lets you build custom agents tailored to your specific business needs. Here\'s a complete guide.</p>
<h4>Agent Configuration Options</h4>
<ul>
<li><strong>Name &amp; Avatar:</strong> Give your agent a memorable identity.</li>
<li><strong>System Prompt:</strong> Define the agent\'s personality, tone, and boundaries.</li>
<li><strong>Knowledge Base:</strong> Upload documents (PDF, DOCX, TXT) or provide URLs for the agent to learn from.</li>
<li><strong>Tools:</strong> Enable specific <a href="/alfred-tools.php">tools</a> the agent can use (web search, calculations, etc.).</li>
<li><strong>Behavior Rules:</strong> Set guardrails for topics the agent should avoid.</li>
</ul>
<h4>Writing Effective System Prompts</h4>
<p>A good system prompt includes:</p>
<ol>
<li><strong>Role:</strong> "You are a customer support specialist for [Company]."</li>
<li><strong>Tone:</strong> "Be friendly, professional, and concise."</li>
<li><strong>Knowledge scope:</strong> "Only answer questions about our products."</li>
<li><strong>Escalation:</strong> "If unsure, offer to transfer to a human agent."</li>
</ol>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Use the <a href="/agent-templates.php">Agent Templates</a> gallery for inspiration — each template can be fully customized after cloning.</div>'
            ],
            [
                'id'      => 'af-templates',
                'title'   => 'Using Agent Templates',
                'tags'    => 'templates marketplace clone pre-built agent',
                'updated' => '2026-02-26',
                'content' => '<p>Agent templates are pre-configured agents you can deploy instantly and customize to your needs.</p>
<h4>Finding Templates</h4>
<p>Browse templates at <a href="/agent-templates.php">Agent Templates</a> or from your dashboard under <strong>"New Agent" → "From Template"</strong>.</p>
<h4>Available Template Categories</h4>
<ul>
<li><strong>Customer Support:</strong> Handle FAQs, troubleshoot issues, process returns.</li>
<li><strong>Sales:</strong> Qualify leads, book demos, answer product questions.</li>
<li><strong>Healthcare:</strong> Appointment scheduling, patient intake, symptom triage.</li>
<li><strong>Real Estate:</strong> Property inquiries, showing scheduling, mortgage calculators.</li>
<li><strong>Legal:</strong> Initial consultations, document preparation, case intake.</li>
</ul>
<h4>Customizing a Template</h4>
<ol>
<li>Click <strong>"Use Template"</strong> on your chosen template.</li>
<li>The template creates a new agent with all pre-configured settings.</li>
<li>Edit the system prompt, tools, and knowledge base to match your business.</li>
<li>Test and deploy when ready.</li>
</ol>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> You can also share your custom agents as templates in the <a href="/marketplace.php">Marketplace</a>.</div>'
            ],
            [
                'id'      => 'af-fleet',
                'title'   => 'Fleet Management',
                'tags'    => 'fleet manage multiple agents team coordinate',
                'updated' => '2026-03-02',
                'content' => '<p>Fleets allow you to coordinate multiple agents working together as a team. This is ideal for complex workflows.</p>
<h4>What Is a Fleet?</h4>
<p>A fleet is a group of agents assigned roles that collaborate to handle tasks. For example, a support fleet might have a triage agent, a technical agent, and a billing agent.</p>
<h4>Creating a Fleet</h4>
<ol>
<li>Go to <a href="/fleet-dashboard.php">Fleet Dashboard</a>.</li>
<li>Click <strong>"Create Fleet"</strong>.</li>
<li>Name your fleet and define its purpose.</li>
<li>Add agents and assign roles (Leader, Specialist, Support).</li>
<li>Configure routing rules for how conversations are distributed.</li>
</ol>
<h4>Fleet Routing</h4>
<ul>
<li><strong>Intent-Based:</strong> Route based on detected customer intent.</li>
<li><strong>Round-Robin:</strong> Distribute evenly across agents.</li>
<li><strong>Priority:</strong> Route to the best-matched agent first.</li>
<li><strong>Escalation Chain:</strong> Cascade to next agent if first can\'t resolve.</li>
</ul>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Use the Fleet Dashboard\'s live view to monitor all agents in real time and see conversation handoffs as they happen.</div>'
            ],
            [
                'id'      => 'af-roles',
                'title'   => 'Agent Roles and Tasks',
                'tags'    => 'roles tasks assignment permissions agent config',
                'updated' => '2026-02-20',
                'content' => '<p>Each agent in a fleet can be assigned specific roles and tasks to optimize performance.</p>
<h4>Built-in Roles</h4>
<ul>
<li><strong>Leader:</strong> Receives all incoming conversations first, triages and delegates to specialists.</li>
<li><strong>Specialist:</strong> Handles specific topic areas (billing, technical, sales).</li>
<li><strong>Support:</strong> Assists other agents and handles overflow.</li>
<li><strong>Observer:</strong> Monitors conversations for quality assurance without interacting.</li>
</ul>
<h4>Assigning Tasks</h4>
<p>Tasks define what an agent can do beyond conversation:</p>
<ul>
<li>Send emails and follow-up messages</li>
<li>Create support tickets</li>
<li>Update CRM records</li>
<li>Schedule appointments</li>
<li>Process payments</li>
</ul>
<h4>Task Configuration</h4>
<p>In your agent\'s settings, go to <strong>"Tasks"</strong> tab → <strong>"Add Task"</strong>. Select from available task types and configure the required parameters (API endpoints, credentials, templates).</p>
<div class="help-callout help-callout-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Tasks that modify external systems (CRM updates, payments) should be tested thoroughly in sandbox mode before deploying to production.</div>'
            ],
            [
                'id'      => 'af-monitoring',
                'title'   => 'Monitoring Agent Performance',
                'tags'    => 'monitoring analytics performance metrics satisfaction KPI',
                'updated' => '2026-03-03',
                'content' => '<p>Track how your agents are performing with Alfred AI\'s built-in analytics and monitoring tools.</p>
<h4>Key Metrics</h4>
<ul>
<li><strong>Response Time:</strong> Average time to first response.</li>
<li><strong>Resolution Rate:</strong> Percentage of conversations resolved without escalation.</li>
<li><strong>Satisfaction Score:</strong> Based on user thumbs-up/down feedback.</li>
<li><strong>Token Efficiency:</strong> Tokens used per conversation (lower is better).</li>
<li><strong>Conversation Volume:</strong> Total interactions over time.</li>
</ul>
<h4>Accessing Analytics</h4>
<p>Navigate to <a href="/analytics.php">Analytics</a> for platform-wide metrics, or click an individual agent to see agent-specific data.</p>
<h4>Setting Up Alerts</h4>
<ol>
<li>Go to Settings → <strong>Notifications</strong> → <strong>"Performance Alerts"</strong>.</li>
<li>Set thresholds (e.g., alert if satisfaction drops below 80%).</li>
<li>Choose notification channel: email, SMS, or webhook.</li>
</ol>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Schedule weekly performance reports to be emailed to your team. Go to Analytics → <strong>"Scheduled Reports"</strong>.</div>'
            ],
        ]
    ],
    'voice-calls' => [
        'title' => 'Voice & Calls',
        'icon'  => 'fa-phone-volume',
        'color' => '#e17055',
        'articles' => [
            [
                'id'      => 'vc-voice-agents',
                'title'   => 'Setting Up Voice Agents',
                'tags'    => 'voice agent setup phone telephony inbound outbound',
                'updated' => '2026-03-01',
                'content' => '<p>Voice agents handle phone calls automatically with natural-sounding AI conversations.</p>
<h4>Prerequisites</h4>
<ul>
<li>An active Alfred AI account (Pro plan or higher).</li>
<li>A purchased phone number from <a href="/voice-products.php">Voice Products</a>.</li>
<li>At least one configured agent.</li>
</ul>
<h4>Setup Steps</h4>
<ol>
<li>Go to <a href="/voice-portal.php">Voice Portal</a>.</li>
<li>Click <strong>"New Voice Agent"</strong>.</li>
<li>Select the AI agent that will handle calls.</li>
<li>Assign your phone number.</li>
<li>Choose a voice: select from 30+ premade voices or <a href="/voice-cloning.php">clone your own</a>.</li>
<li>Set the greeting message and call flow.</li>
<li>Configure business hours and after-hours behavior.</li>
</ol>
<h4>Voice Settings</h4>
<ul>
<li><strong>Speech Speed:</strong> Adjust how fast the agent speaks (0.5x–2x).</li>
<li><strong>Silence Timeout:</strong> How long to wait for caller response (default: 5 seconds).</li>
<li><strong>Max Call Duration:</strong> Set a maximum call length.</li>
<li><strong>Transfer Number:</strong> Number to forward to if caller requests a human.</li>
</ul>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Voice agents support both inbound and outbound calls. See <a href="#vc-campaigns">Call Campaigns</a> for outbound setup.</div>'
            ],
            [
                'id'      => 'vc-ivr',
                'title'   => 'IVR Builder Guide',
                'tags'    => 'IVR interactive voice response menu builder flow',
                'updated' => '2026-02-25',
                'content' => '<p>The <a href="/ivr-builder.php">IVR Builder</a> lets you create interactive voice menus with a drag-and-drop visual editor.</p>
<h4>What Is an IVR?</h4>
<p>An Interactive Voice Response (IVR) system greets callers and routes them through menu options using voice or keypad input. Example: "Press 1 for Sales, Press 2 for Support."</p>
<h4>Creating an IVR Flow</h4>
<ol>
<li>Open the <a href="/ivr-builder.php">IVR Builder</a>.</li>
<li>Drag a <strong>"Greeting"</strong> node onto the canvas.</li>
<li>Add <strong>"Menu"</strong> nodes with options (1–9, or voice keywords).</li>
<li>Connect nodes to actions: transfer to agent, play message, collect input, or hang up.</li>
<li>Set fallback behavior for unrecognized inputs.</li>
</ol>
<h4>Advanced Features</h4>
<ul>
<li><strong>AI-Powered Routing:</strong> Let Alfred understand natural language instead of rigid menus.</li>
<li><strong>Time-Based Routing:</strong> Different flows for business hours vs. after-hours.</li>
<li><strong>Data Collection:</strong> Gather caller info (account number, case ID) before routing.</li>
<li><strong>CRM Integration:</strong> Look up caller in your CRM for personalized greetings.</li>
</ul>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Keep IVR menus simple — research shows callers prefer 3–4 options maximum. Consider using AI routing for a more natural experience.</div>'
            ],
            [
                'id'      => 'vc-cloning',
                'title'   => 'Voice Cloning',
                'tags'    => 'voice cloning custom clone audio training',
                'updated' => '2026-02-28',
                'content' => '<p><a href="/voice-cloning.php">Voice Cloning</a> lets you create a custom AI voice that sounds like a specific person — great for brand consistency.</p>
<h4>How It Works</h4>
<ol>
<li>Record or upload a voice sample (minimum 30 seconds, recommended 3+ minutes).</li>
<li>Our AI analyzes pitch, tone, cadence, and pronunciation patterns.</li>
<li>A custom voice model is generated (takes 5–15 minutes).</li>
<li>Assign the cloned voice to any of your agents.</li>
</ol>
<h4>Recording Tips</h4>
<ul>
<li>Use a quiet room with minimal echo.</li>
<li>Speak naturally at your normal pace.</li>
<li>Read varied content (not just one sentence repeated).</li>
<li>Use a good quality microphone (USB condenser recommended).</li>
</ul>
<h4>Legal Requirements</h4>
<p>You must have explicit consent from the person whose voice is being cloned. When creating a voice clone, you\'ll need to confirm:</p>
<ul>
<li>You are the person, or have written consent from them.</li>
<li>The voice will be used for legitimate business purposes.</li>
<li>You agree to our voice cloning terms of service.</li>
</ul>
<div class="help-callout help-callout-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Cloning someone\'s voice without their consent is prohibited and may violate laws in your jurisdiction.</div>'
            ],
            [
                'id'      => 'vc-campaigns',
                'title'   => 'Call Campaigns',
                'tags'    => 'call campaign outbound bulk dialer list',
                'updated' => '2026-03-02',
                'content' => '<p><a href="/call-campaigns.php">Call Campaigns</a> let you make automated outbound calls at scale — perfect for appointments, reminders, surveys, and sales outreach.</p>
<h4>Creating a Campaign</h4>
<ol>
<li>Go to <a href="/call-campaigns.php">Call Campaigns</a>.</li>
<li>Click <strong>"New Campaign"</strong>.</li>
<li>Name your campaign and select the agent that will handle calls.</li>
<li>Upload a contact list (CSV with name, phone, optional custom fields).</li>
<li>Set the call script — the agent\'s opening message and conversation flow.</li>
<li>Configure calling hours and timezone rules.</li>
</ol>
<h4>Campaign Settings</h4>
<ul>
<li><strong>Concurrency:</strong> Number of simultaneous calls (1–50).</li>
<li><strong>Retry Logic:</strong> How many times to retry unanswered calls.</li>
<li><strong>Caller ID:</strong> Which number appears on the recipient\'s phone.</li>
<li><strong>Compliance:</strong> Auto-skip numbers on do-not-call lists.</li>
</ul>
<h4>Monitoring Results</h4>
<p>Track campaign progress in real time: calls completed, answered, voicemail left, outcomes, and conversion rates.</p>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> All outbound campaigns must comply with TCPA, GDPR, and local telecommunications regulations. Ensure you have proper consent before calling.</div>'
            ],
            [
                'id'      => 'vc-conference',
                'title'   => 'Conference Rooms',
                'tags'    => 'conference room multi-party call meeting group',
                'updated' => '2026-02-22',
                'content' => '<p><a href="/conference-room.php">Conference Rooms</a> enable multi-party calls with AI agents — great for meetings where AI assists in real time.</p>
<h4>Use Cases</h4>
<ul>
<li><strong>AI-Assisted Meetings:</strong> An agent joins your conference to take notes, summarize decisions, and create action items.</li>
<li><strong>Training Sessions:</strong> Multiple new users learn with an AI trainer on the call.</li>
<li><strong>Customer Calls:</strong> Your team + AI agent collaborate on complex customer issues.</li>
<li><strong>Interviews:</strong> AI assists with question prompts and candidate evaluation.</li>
</ul>
<h4>Setting Up a Conference Room</h4>
<ol>
<li>Go to <a href="/conference-room.php">Conference Rooms</a>.</li>
<li>Click <strong>"Create Room"</strong>.</li>
<li>Set the room name, PIN (optional), and max participants.</li>
<li>Choose which AI agent(s) to include.</li>
<li>Share the dial-in number and PIN with participants.</li>
</ol>
<h4>During the Conference</h4>
<p>The AI agent can be configured to:</p>
<ul>
<li>Listen silently and generate a summary afterward.</li>
<li>Actively participate and answer questions.</li>
<li>Provide real-time fact-checking and data lookups.</li>
</ul>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Enable <strong>"Post-Call Summary"</strong> to automatically receive meeting notes via email after the conference ends.</div>'
            ],
        ]
    ],
    'api-development' => [
        'title' => 'API & Development',
        'icon'  => 'fa-code',
        'color' => '#fdcb6e',
        'articles' => [
            [
                'id'      => 'ad-auth',
                'title'   => 'Authentication (API Keys & OAuth)',
                'tags'    => 'authentication API key OAuth token bearer auth',
                'updated' => '2026-03-02',
                'content' => '<p>Alfred AI supports two authentication methods: API Keys (for server-to-server) and OAuth 2.0 (for user-authorized applications).</p>
<h4>API Key Authentication</h4>
<ol>
<li>Go to <a href="/developer-portal.php">Developer Portal</a> → <strong>API Keys</strong>.</li>
<li>Click <strong>"Create New Key"</strong>.</li>
<li>Name your key (e.g., "Production Server") and set permissions.</li>
<li>Copy the key immediately — it\'s shown only once.</li>
</ol>
<p>Use the key in requests:</p>
<pre><code>Authorization: Bearer sk_live_abc123def456</code></pre>
<h4>OAuth 2.0</h4>
<p>For applications that act on behalf of users:</p>
<ol>
<li>Register your app in the Developer Portal → <strong>OAuth Apps</strong>.</li>
<li>Set your redirect URI(s).</li>
<li>Use the authorization code flow to obtain access tokens.</li>
</ol>
<pre><code>GET /api/oauth/authorize?client_id=YOUR_ID&amp;redirect_uri=YOUR_URI&amp;response_type=code&amp;scope=agents:read+agents:write</code></pre>
<h4>Key Security Best Practices</h4>
<ul>
<li>Rotate keys every 90 days.</li>
<li>Use environment variables — never hardcode keys.</li>
<li>Set minimum required permissions for each key.</li>
<li>Use separate keys for development and production.</li>
</ul>
<div class="help-callout help-callout-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> If a key is compromised, revoke it immediately from the Developer Portal and create a new one.</div>'
            ],
            [
                'id'      => 'ad-rest',
                'title'   => 'REST API Quickstart',
                'tags'    => 'REST API quickstart endpoints CRUD request response',
                'updated' => '2026-03-01',
                'content' => '<p>The Alfred AI REST API lets you programmatically manage agents, conversations, and voice features.</p>
<h4>Base URL</h4>
<pre><code>https://gositeme.com/api/v1/</code></pre>
<h4>Common Endpoints</h4>
<table class="help-table">
<thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
<tbody>
<tr><td>GET</td><td>/agents</td><td>List all agents</td></tr>
<tr><td>POST</td><td>/agents</td><td>Create an agent</td></tr>
<tr><td>GET</td><td>/agents/{id}</td><td>Get agent details</td></tr>
<tr><td>POST</td><td>/chat</td><td>Send a message</td></tr>
<tr><td>GET</td><td>/conversations</td><td>List conversations</td></tr>
<tr><td>GET</td><td>/usage</td><td>Get usage stats</td></tr>
</tbody>
</table>
<h4>Example: List Agents</h4>
<pre><code>curl https://gositeme.com/api/v1/agents \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>
<h4>Response Format</h4>
<p>All responses are JSON with this structure:</p>
<pre><code>{
  "status": "success",
  "data": { ... },
  "meta": { "page": 1, "total": 42 }
}</code></pre>
<p>For complete API documentation, visit the <a href="/docs/api-reference">API Reference</a>.</p>'
            ],
            [
                'id'      => 'ad-sdks',
                'title'   => 'SDKs (Node.js, Python, PHP)',
                'tags'    => 'SDK Node.js Python PHP library package npm pip composer',
                'updated' => '2026-02-28',
                'content' => '<p>Official SDKs make integrating Alfred AI into your application faster and easier. Available for <a href="/sdks">three languages</a>.</p>
<h4>Node.js</h4>
<pre><code>npm install @gositeme/alfred-sdk</code></pre>
<pre><code>const Alfred = require(\'@gositeme/alfred-sdk\');
const client = new Alfred({ apiKey: process.env.ALFRED_API_KEY });

const response = await client.chat.send({
  agentId: \'ag_abc123\',
  message: \'Hello!\'
});</code></pre>
<h4>Python</h4>
<pre><code>pip install gositeme-alfred</code></pre>
<pre><code>from gositeme_alfred import Alfred

client = Alfred(api_key=os.environ[\'ALFRED_API_KEY\'])
response = client.chat.send(
    agent_id=\'ag_abc123\',
    message=\'Hello!\'
)</code></pre>
<h4>PHP</h4>
<pre><code>composer require gositeme/alfred-sdk</code></pre>
<pre><code>use GoSiteMe\\Alfred\\Client;

$client = new Client(getenv(\'ALFRED_API_KEY\'));
$response = $client->chat->send([
    \'agent_id\' => \'ag_abc123\',
    \'message\'  => \'Hello!\'
]);</code></pre>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> All SDKs support TypeScript/type hints, async/await, automatic retries, and streaming responses. Check the <a href="/sdks">SDK documentation</a> for details.</div>'
            ],
            [
                'id'      => 'ad-webhooks',
                'title'   => 'Webhooks Setup',
                'tags'    => 'webhook event callback notification real-time',
                'updated' => '2026-03-03',
                'content' => '<p><a href="/webhooks.php">Webhooks</a> let you receive real-time notifications when events occur in your Alfred AI account.</p>
<h4>Setting Up Webhooks</h4>
<ol>
<li>Go to <a href="/developer-portal.php">Developer Portal</a> → <strong>Webhooks</strong>.</li>
<li>Click <strong>"Add Endpoint"</strong>.</li>
<li>Enter your HTTPS callback URL.</li>
<li>Select the events you want to subscribe to.</li>
<li>Save and copy the webhook secret for signature verification.</li>
</ol>
<h4>Available Events</h4>
<ul>
<li><code>conversation.started</code> — New conversation began</li>
<li><code>conversation.ended</code> — Conversation completed</li>
<li><code>message.received</code> — New message in a conversation</li>
<li><code>agent.status_changed</code> — Agent went online/offline</li>
<li><code>call.started</code> / <code>call.ended</code> — Voice call events</li>
<li><code>usage.threshold</code> — Usage limit approaching</li>
</ul>
<h4>Verifying Webhook Signatures</h4>
<pre><code>const crypto = require(\'crypto\');
const signature = req.headers[\'x-alfred-signature\'];
const expected = crypto
  .createHmac(\'sha256\', WEBHOOK_SECRET)
  .update(req.body)
  .digest(\'hex\');
const valid = crypto.timingSafeEqual(
  Buffer.from(signature), Buffer.from(expected)
);</code></pre>
<div class="help-callout help-callout-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Always verify webhook signatures to prevent spoofed requests. Respond with 200 within 5 seconds to avoid retries.</div>'
            ],
            [
                'id'      => 'ad-rate-limits',
                'title'   => 'Rate Limits & Errors',
                'tags'    => 'rate limit throttle error code HTTP status 429',
                'updated' => '2026-02-25',
                'content' => '<p>The Alfred AI API enforces rate limits to ensure fair usage and platform stability.</p>
<h4>Rate Limits by Plan</h4>
<table class="help-table">
<thead><tr><th>Plan</th><th>Requests/min</th><th>Requests/day</th></tr></thead>
<tbody>
<tr><td>Starter</td><td>60</td><td>5,000</td></tr>
<tr><td>Pro</td><td>300</td><td>50,000</td></tr>
<tr><td>Business</td><td>1,000</td><td>500,000</td></tr>
<tr><td>Enterprise</td><td>Custom</td><td>Custom</td></tr>
</tbody>
</table>
<h4>Rate Limit Headers</h4>
<p>Every API response includes these headers:</p>
<pre><code>X-RateLimit-Limit: 300
X-RateLimit-Remaining: 287
X-RateLimit-Reset: 1709510460</code></pre>
<h4>Common Error Codes</h4>
<table class="help-table">
<thead><tr><th>Code</th><th>Meaning</th><th>Action</th></tr></thead>
<tbody>
<tr><td>400</td><td>Bad Request</td><td>Check your request body/params</td></tr>
<tr><td>401</td><td>Unauthorized</td><td>Check your API key</td></tr>
<tr><td>403</td><td>Forbidden</td><td>Insufficient permissions</td></tr>
<tr><td>404</td><td>Not Found</td><td>Check the resource ID</td></tr>
<tr><td>429</td><td>Rate Limited</td><td>Wait and retry with exponential backoff</td></tr>
<tr><td>500</td><td>Server Error</td><td>Retry or contact support</td></tr>
</tbody>
</table>
<h4>Handling Rate Limits</h4>
<pre><code>async function apiCall(url, options, retries = 3) {
  const res = await fetch(url, options);
  if (res.status === 429 &amp;&amp; retries > 0) {
    const wait = parseInt(res.headers.get(\'Retry-After\') || \'5\');
    await new Promise(r => setTimeout(r, wait * 1000));
    return apiCall(url, options, retries - 1);
  }
  return res.json();
}</code></pre>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Use our official SDKs — they handle rate limiting, retries, and error parsing automatically.</div>'
            ],
        ]
    ],
    'enterprise' => [
        'title' => 'Enterprise',
        'icon'  => 'fa-building',
        'color' => '#a29bfe',
        'articles' => [
            [
                'id'      => 'en-org-setup',
                'title'   => 'Organization Setup',
                'tags'    => 'organization setup enterprise company account',
                'updated' => '2026-03-01',
                'content' => '<p>Enterprise organizations get a dedicated workspace with advanced management features.</p>
<h4>Initial Setup</h4>
<ol>
<li>After signing your Enterprise agreement, you\'ll receive an activation email.</li>
<li>Click the activation link to set up your organization.</li>
<li>Configure: organization name, logo, primary domain, and admin account.</li>
<li>Set up billing (invoice or credit card).</li>
</ol>
<h4>Organization Settings</h4>
<p>Access at <a href="/enterprise-admin.php">Enterprise Admin</a>:</p>
<ul>
<li><strong>General:</strong> Name, logo, contact info, and default timezone.</li>
<li><strong>Security:</strong> Password policies, 2FA requirements, IP allowlists.</li>
<li><strong>Compliance:</strong> Data retention policies, export controls, audit settings.</li>
<li><strong>Integrations:</strong> SSO, SCIM provisioning, API access controls.</li>
</ul>
<h4>Multi-Environment Support</h4>
<p>Enterprise plans include separate environments:</p>
<ul>
<li><strong>Production:</strong> Live agents serving customers.</li>
<li><strong>Staging:</strong> For testing changes before going live.</li>
<li><strong>Development:</strong> For building and experimenting.</li>
</ul>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Contact your account manager or email <a href="mailto:enterprise@gositeme.com">enterprise@gositeme.com</a> for setup assistance.</div>'
            ],
            [
                'id'      => 'en-team-rbac',
                'title'   => 'Team Management & RBAC',
                'tags'    => 'team management RBAC roles permissions members users',
                'updated' => '2026-02-28',
                'content' => '<p>Role-Based Access Control (RBAC) lets you define exactly what each team member can see and do.</p>
<h4>Managing Team Members</h4>
<ol>
<li>Go to <a href="/enterprise-admin.php">Enterprise Admin</a> → <strong>Team</strong>.</li>
<li>Click <strong>"Invite Member"</strong>.</li>
<li>Enter their email and assign a role.</li>
<li>They\'ll receive an invitation to join your organization.</li>
</ol>
<h4>Built-in Roles</h4>
<table class="help-table">
<thead><tr><th>Role</th><th>Permissions</th></tr></thead>
<tbody>
<tr><td>Owner</td><td>Full access — billing, members, all agents &amp; settings</td></tr>
<tr><td>Admin</td><td>Manage agents, members, and settings (no billing)</td></tr>
<tr><td>Developer</td><td>Create/edit agents, API keys, integrations</td></tr>
<tr><td>Analyst</td><td>View analytics, reports, and conversation logs</td></tr>
<tr><td>Viewer</td><td>Read-only access to dashboards</td></tr>
</tbody>
</table>
<h4>Custom Roles</h4>
<p>Create custom roles with granular permissions:</p>
<ol>
<li>Go to Team → <strong>"Manage Roles"</strong> → <strong>"Create Custom Role"</strong>.</li>
<li>Name the role and toggle individual permissions (agents, analytics, voice, billing, API, admin).</li>
<li>Assign to team members as needed.</li>
</ol>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Follow the principle of least privilege — give users only the permissions they need.</div>'
            ],
            [
                'id'      => 'en-sso',
                'title'   => 'SSO Configuration',
                'tags'    => 'SSO single sign-on SAML Okta Azure AD identity provider',
                'updated' => '2026-02-25',
                'content' => '<p>Enterprise plans support Single Sign-On (SSO) via SAML 2.0, allowing your team to log in with your existing identity provider.</p>
<h4>Supported Identity Providers</h4>
<ul>
<li>Okta</li>
<li>Azure Active Directory</li>
<li>Google Workspace</li>
<li>OneLogin</li>
<li>Any SAML 2.0-compatible provider</li>
</ul>
<h4>Setup Steps</h4>
<ol>
<li>Go to <a href="/enterprise-admin.php">Enterprise Admin</a> → <strong>Security</strong> → <strong>"SSO Configuration"</strong>.</li>
<li>Select your identity provider or choose "Custom SAML".</li>
<li>Enter the SSO URL, Entity ID, and upload the X.509 certificate from your IdP.</li>
<li>Configure attribute mappings (email, name, role).</li>
<li>Set the ACS (Assertion Consumer Service) URL in your IdP: <code>https://gositeme.com/api/oauth/saml/callback</code></li>
<li>Test the connection before enabling for all users.</li>
</ol>
<h4>SSO Policies</h4>
<ul>
<li><strong>Enforce SSO:</strong> Require all members to log in via SSO (disable password login).</li>
<li><strong>Auto-Provisioning:</strong> Automatically create accounts for new SSO users.</li>
<li><strong>Role Mapping:</strong> Map IdP groups to Alfred AI roles.</li>
</ul>
<div class="help-callout help-callout-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Always keep at least one admin with password access as a break-glass account in case SSO is misconfigured.</div>'
            ],
            [
                'id'      => 'en-white-label',
                'title'   => 'White-Label Setup',
                'tags'    => 'white label branding custom domain logo reseller',
                'updated' => '2026-02-22',
                'content' => '<p><a href="/white-label.php">White-Label</a> lets you rebrand Alfred AI completely and offer it to your own customers under your brand.</p>
<h4>What You Can Customize</h4>
<ul>
<li><strong>Brand:</strong> Logo, colors, fonts, favicon.</li>
<li><strong>Domain:</strong> Use your own domain (e.g., ai.yourcompany.com).</li>
<li><strong>Email:</strong> Send notifications from your domain.</li>
<li><strong>Interface:</strong> Custom dashboard layout and terminology.</li>
<li><strong>Documentation:</strong> Branded docs and help pages.</li>
</ul>
<h4>Setup Steps</h4>
<ol>
<li>Go to <a href="/enterprise-admin.php">Enterprise Admin</a> → <strong>White-Label</strong>.</li>
<li>Upload your logo (SVG or PNG, 200×50 recommended).</li>
<li>Set your brand colors (primary, secondary, accent).</li>
<li>Add your custom domain and configure DNS (CNAME record).</li>
<li>Customize email templates with your branding.</li>
<li>Set up your billing (you set pricing for your customers).</li>
</ol>
<h4>DNS Configuration</h4>
<pre><code>CNAME  ai.yourcompany.com  →  whitelabel.gositeme.com</code></pre>
<p>SSL is automatically provisioned for your custom domain.</p>
<div class="help-callout help-callout-tip"><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Use the preview mode to see exactly what your customers will experience before going live.</div>'
            ],
            [
                'id'      => 'en-audit',
                'title'   => 'Audit Logging',
                'tags'    => 'audit log compliance tracking activity security',
                'updated' => '2026-03-03',
                'content' => '<p>Audit logging provides a complete record of all actions taken in your organization for compliance and security purposes.</p>
<h4>What\'s Logged</h4>
<ul>
<li><strong>Authentication:</strong> Logins, logouts, failed attempts, SSO events.</li>
<li><strong>Agent Changes:</strong> Created, updated, deleted, deployed agents.</li>
<li><strong>Team Changes:</strong> Members added/removed, role changes.</li>
<li><strong>API Activity:</strong> Key creation, key usage, OAuth grants.</li>
<li><strong>Settings:</strong> Configuration changes, billing updates.</li>
<li><strong>Data Access:</strong> Conversation exports, report downloads.</li>
</ul>
<h4>Viewing Audit Logs</h4>
<ol>
<li>Go to <a href="/enterprise-admin.php">Enterprise Admin</a> → <strong>Audit Logs</strong>.</li>
<li>Filter by: date range, user, action type, or resource.</li>
<li>Click any entry to see full details including IP address and user agent.</li>
</ol>
<h4>Exporting Logs</h4>
<p>Export audit logs in CSV or JSON format for external analysis or compliance reporting:</p>
<ul>
<li>Click <strong>"Export"</strong> in the top-right of the audit log view.</li>
<li>Select format, date range, and filters.</li>
<li>Logs can also be streamed to your SIEM via webhook.</li>
</ul>
<h4>Retention</h4>
<p>Enterprise audit logs are retained for 1 year by default. Extended retention (up to 7 years) is available for compliance-regulated industries.</p>
<div class="help-callout help-callout-info"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Audit logs are immutable — they cannot be modified or deleted, even by organization owners.</div>'
            ],
        ]
    ],
];
?>

<!-- Schema.org FAQPage markup -->
<script type="application/ld+json">
<?php
$faq_items = [];
foreach ($categories as $cat) {
    foreach ($cat['articles'] as $art) {
        $faq_items[] = [
            '@type' => 'Question',
            'name' => $art['title'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => strip_tags($art['content'])
            ]
        ];
    }
}
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $faq_items
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
</script>

<style>
/* ===== Help Center Styles ===== */
:root {
    --help-bg: #0a0a14;
    --help-surface: #12121e;
    --help-surface-2: #1a1a2e;
    --help-surface-3: #22223a;
    --help-border: rgba(255,255,255,0.08);
    --help-accent: #6c5ce7;
    --help-accent-light: #a29bfe;
    --help-blue: #0984e3;
    --help-green: #00b894;
    --help-orange: #fdcb6e;
    --help-fire: #e17055;
    --help-pink: #fd79a8;
    --help-cyan: #00cec9;
    --help-text: #e8e8f0;
    --help-text-muted: #8a8a9a;
    --help-radius: 12px;
    --help-sidebar-w: 260px;
}

/* Hero */
.help-hero {
    padding: 140px 20px 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 50% 0%, #1a1033 0%, var(--help-bg) 70%);
}
.help-hero::before {
    content: '';
    position: absolute;
    top: -40%; left: -20%;
    width: 140%; height: 180%;
    background:
        radial-gradient(circle at 30% 25%, rgba(108,92,231,0.14) 0%, transparent 50%),
        radial-gradient(circle at 70% 65%, rgba(9,132,227,0.1) 0%, transparent 50%);
    pointer-events: none;
}
.help-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 16px;
    position: relative;
}
.help-hero p {
    color: var(--help-text-muted);
    font-size: 1.1rem;
    margin-bottom: 32px;
    position: relative;
}

/* Search */
.help-search-wrap {
    max-width: 620px;
    margin: 0 auto 24px;
    position: relative;
}
.help-search-wrap i.fa-search {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--help-text-muted);
    font-size: 1.1rem;
    transition: color .3s;
    pointer-events: none;
}
.help-search {
    width: 100%;
    padding: 16px 20px 16px 50px;
    border-radius: 50px;
    border: 2px solid var(--help-border);
    background: var(--help-surface);
    color: var(--help-text);
    font-size: 1.05rem;
    font-family: 'Inter', sans-serif;
    outline: none;
    transition: border-color .3s, box-shadow .3s;
}
.help-search:focus {
    border-color: var(--help-accent);
    box-shadow: 0 0 0 4px rgba(108,92,231,0.2);
}
.help-search:focus + i.fa-search,
.help-search-wrap:focus-within i.fa-search {
    color: var(--help-accent);
}
.help-search::placeholder {
    color: var(--help-text-muted);
}
.help-popular {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    position: relative;
}
.help-popular span {
    color: var(--help-text-muted);
    font-size: .85rem;
    margin-right: 4px;
}
.help-popular a {
    padding: 4px 14px;
    border-radius: 20px;
    background: var(--help-surface-2);
    color: var(--help-accent-light);
    font-size: .85rem;
    text-decoration: none;
    border: 1px solid var(--help-border);
    transition: background .2s, border-color .2s;
}
.help-popular a:hover {
    background: var(--help-surface-3);
    border-color: var(--help-accent);
}

/* Layout */
.help-layout {
    display: flex;
    max-width: 1300px;
    margin: 0 auto;
    padding: 40px 20px 80px;
    gap: 40px;
}

/* Sidebar */
.help-sidebar {
    width: var(--help-sidebar-w);
    flex-shrink: 0;
    position: sticky;
    top: 100px;
    align-self: flex-start;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}
.help-sidebar-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}
.help-sidebar-nav li {
    margin-bottom: 4px;
}
.help-sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 8px;
    color: var(--help-text-muted);
    text-decoration: none;
    font-size: .92rem;
    transition: background .2s, color .2s;
}
.help-sidebar-nav a:hover,
.help-sidebar-nav a.active {
    background: var(--help-surface-2);
    color: #fff;
}
.help-sidebar-nav a.active {
    border-left: 3px solid var(--help-accent);
    padding-left: 11px;
}
.help-sidebar-nav i {
    width: 20px;
    text-align: center;
    font-size: .95rem;
}
.help-sidebar-nav .help-nav-count {
    margin-left: auto;
    font-size: .75rem;
    background: var(--help-surface-3);
    padding: 2px 8px;
    border-radius: 10px;
    color: var(--help-text-muted);
}

/* Main content */
.help-main {
    flex: 1;
    min-width: 0;
}

/* Category cards grid */
.help-categories-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 60px;
}
.help-cat-card {
    background: var(--help-surface);
    border: 1px solid var(--help-border);
    border-radius: var(--help-radius);
    padding: 28px 24px;
    cursor: pointer;
    transition: transform .2s, border-color .2s, box-shadow .2s;
    text-decoration: none;
    display: block;
}
.help-cat-card:hover {
    transform: translateY(-3px);
    border-color: var(--help-accent);
    box-shadow: 0 8px 30px rgba(108,92,231,0.12);
}
.help-cat-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 16px;
}
.help-cat-card h3 {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 8px;
    font-family: 'Space Grotesk', sans-serif;
}
.help-cat-card p {
    color: var(--help-text-muted);
    font-size: .85rem;
    margin: 0;
    line-height: 1.5;
}
.help-cat-card .help-article-count {
    display: inline-block;
    margin-top: 12px;
    font-size: .8rem;
    color: var(--help-accent-light);
}

/* Category section */
.help-category-section {
    margin-bottom: 60px;
    scroll-margin-top: 100px;
}
.help-category-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--help-border);
}
.help-category-header .cat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.help-category-header h2 {
    color: #fff;
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0;
    font-family: 'Space Grotesk', sans-serif;
}

/* Breadcrumb */
.help-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .85rem;
    color: var(--help-text-muted);
    margin-bottom: 8px;
}
.help-breadcrumb a {
    color: var(--help-accent-light);
    text-decoration: none;
}
.help-breadcrumb a:hover { text-decoration: underline; }
.help-breadcrumb i { font-size: .65rem; }

/* Accordion */
.help-accordion {
    border: 1px solid var(--help-border);
    border-radius: var(--help-radius);
    margin-bottom: 12px;
    overflow: hidden;
    background: var(--help-surface);
    transition: border-color .2s;
}
.help-accordion.open {
    border-color: rgba(108,92,231,0.3);
}
.help-accordion-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    background: transparent;
    border: none;
    color: var(--help-text);
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    text-align: left;
    font-family: 'Inter', sans-serif;
    gap: 12px;
    transition: color .2s;
}
.help-accordion-header:hover { color: #fff; }
.help-accordion-header .acc-chevron {
    transition: transform .3s;
    font-size: .8rem;
    color: var(--help-text-muted);
    flex-shrink: 0;
}
.help-accordion.open .acc-chevron {
    transform: rotate(180deg);
    color: var(--help-accent);
}
.help-accordion-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height .4s ease, padding .3s;
    padding: 0 22px;
}
.help-accordion.open .help-accordion-body {
    max-height: 3000px;
    padding: 0 22px 22px;
}
.help-accordion-body h4 {
    color: #fff;
    font-size: .95rem;
    font-weight: 600;
    margin: 18px 0 8px;
}
.help-accordion-body h4:first-child { margin-top: 0; }
.help-accordion-body p {
    color: var(--help-text);
    line-height: 1.7;
    margin: 0 0 12px;
    font-size: .92rem;
}
.help-accordion-body ul, .help-accordion-body ol {
    color: var(--help-text);
    padding-left: 20px;
    margin: 0 0 12px;
    line-height: 1.8;
    font-size: .92rem;
}
.help-accordion-body li { margin-bottom: 4px; }
.help-accordion-body a {
    color: var(--help-accent-light);
    text-decoration: none;
}
.help-accordion-body a:hover { text-decoration: underline; }
.help-accordion-body pre {
    background: var(--help-surface-2);
    border: 1px solid var(--help-border);
    border-radius: 8px;
    padding: 14px 18px;
    overflow-x: auto;
    margin: 0 0 12px;
}
.help-accordion-body code {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: .85rem;
    color: var(--help-accent-light);
}
.help-accordion-body pre code { color: #e8e8f0; }
.help-accordion-body kbd {
    background: var(--help-surface-3);
    border: 1px solid var(--help-border);
    border-radius: 4px;
    padding: 2px 6px;
    font-size: .8rem;
    font-family: 'Inter', sans-serif;
    color: var(--help-text);
}

/* Tables */
.help-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 12px;
    font-size: .88rem;
}
.help-table th, .help-table td {
    padding: 10px 14px;
    border: 1px solid var(--help-border);
    color: var(--help-text);
    text-align: left;
}
.help-table th {
    background: var(--help-surface-2);
    font-weight: 600;
    color: #fff;
}
.help-table tr:nth-child(even) td {
    background: rgba(255,255,255,0.02);
}

/* Callouts */
.help-callout {
    padding: 14px 18px;
    border-radius: 8px;
    margin: 12px 0;
    font-size: .9rem;
    line-height: 1.6;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.help-callout i { margin-top: 3px; flex-shrink: 0; }
.help-callout-tip {
    background: rgba(0,184,148,0.1);
    border-left: 3px solid var(--help-green);
    color: var(--help-green);
}
.help-callout-warning {
    background: rgba(225,112,85,0.1);
    border-left: 3px solid var(--help-fire);
    color: var(--help-fire);
}
.help-callout-info {
    background: rgba(9,132,227,0.1);
    border-left: 3px solid var(--help-blue);
    color: var(--help-blue);
}

/* Article meta */
.help-article-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid var(--help-border);
}
.help-article-updated {
    font-size: .8rem;
    color: var(--help-text-muted);
}
.help-article-feedback {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: .85rem;
    color: var(--help-text-muted);
}
.help-feedback-btn {
    background: var(--help-surface-2);
    border: 1px solid var(--help-border);
    border-radius: 6px;
    padding: 5px 12px;
    color: var(--help-text-muted);
    cursor: pointer;
    font-size: .85rem;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background .2s, color .2s, border-color .2s;
}
.help-feedback-btn:hover {
    background: var(--help-surface-3);
    color: #fff;
    border-color: var(--help-accent);
}
.help-feedback-btn.voted {
    background: rgba(108,92,231,0.15);
    border-color: var(--help-accent);
    color: var(--help-accent-light);
    pointer-events: none;
}

/* Still need help */
.help-contact {
    background: var(--help-surface);
    border: 1px solid var(--help-border);
    border-radius: var(--help-radius);
    padding: 50px 40px;
    text-align: center;
    margin-top: 60px;
}
.help-contact h2 {
    color: #fff;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0 0 10px;
    font-family: 'Space Grotesk', sans-serif;
}
.help-contact > p {
    color: var(--help-text-muted);
    margin: 0 0 30px;
    font-size: 1rem;
}
.help-contact-options {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
}
.help-contact-card {
    background: var(--help-surface-2);
    border: 1px solid var(--help-border);
    border-radius: 10px;
    padding: 24px 28px;
    text-decoration: none;
    width: 220px;
    transition: border-color .2s, transform .2s;
}
.help-contact-card:hover {
    border-color: var(--help-accent);
    transform: translateY(-2px);
}
.help-contact-card i {
    font-size: 1.5rem;
    color: var(--help-accent);
    margin-bottom: 12px;
    display: block;
}
.help-contact-card h4 {
    color: #fff;
    font-size: .95rem;
    margin: 0 0 6px;
}
.help-contact-card p {
    color: var(--help-text-muted);
    font-size: .82rem;
    margin: 0;
    line-height: 1.5;
}

/* Search results count */
.help-search-status {
    text-align: center;
    color: var(--help-text-muted);
    padding: 20px;
    font-size: .95rem;
    display: none;
}
.help-search-status.visible { display: block; }

/* No results */
.help-no-results {
    text-align: center;
    padding: 60px 20px;
    display: none;
}
.help-no-results.visible { display: block; }
.help-no-results i {
    font-size: 3rem;
    color: var(--help-text-muted);
    margin-bottom: 16px;
}
.help-no-results h3 {
    color: #fff;
    margin: 0 0 8px;
}
.help-no-results p {
    color: var(--help-text-muted);
}

/* Print styles */
@media print {
    .help-hero, .help-sidebar, .help-search-wrap, .help-popular,
    .help-categories-grid, .help-contact, .help-article-feedback,
    .navbar, .footer { display: none !important; }
    .help-layout { display: block !important; }
    .help-accordion { break-inside: avoid; border: 1px solid #ccc; }
    .help-accordion-body { max-height: none !important; padding: 10px 22px 22px !important; }
    .help-accordion-header { color: #000 !important; }
    .help-accordion-body, .help-accordion-body p, .help-accordion-body li {
        color: #222 !important;
    }
}

/* Responsive */
@media (max-width: 1024px) {
    .help-categories-grid { grid-template-columns: repeat(2, 1fr); }
    .help-sidebar { display: none; }
    .help-layout { padding: 20px 16px 60px; }
}
@media (max-width: 640px) {
    .help-categories-grid { grid-template-columns: 1fr; }
    .help-hero { padding: 100px 16px 40px; }
    .help-search { padding: 14px 16px 14px 44px; font-size: .95rem; }
    .help-contact { padding: 30px 20px; }
    .help-contact-options { flex-direction: column; align-items: center; }
    .help-accordion-header { padding: 14px 16px; font-size: .93rem; }
    .help-accordion-body { padding: 0 16px; }
    .help-accordion.open .help-accordion-body { padding: 0 16px 16px; }
}
</style>

<!-- Hero Section -->
<section class="help-hero">
    <h1>How can we help?</h1>
    <p>Search our knowledge base or browse categories below</p>
    <div class="help-search-wrap">
        <input type="search" class="help-search" id="helpSearch" placeholder="Search for answers..." aria-label="Search help articles" autocomplete="off">
        <i class="fas fa-search" aria-hidden="true"></i>
    </div>
    <div class="help-popular">
        <span>Popular:</span>
        <a href="#getting-started" data-search="getting started">getting started</a>
        <a href="#api-development" data-search="API keys">API keys</a>
        <a href="#voice-calls" data-search="voice setup">voice setup</a>
        <a href="#account-billing" data-search="pricing">pricing</a>
        <a href="#account-billing" data-search="billing">billing</a>
    </div>
</section>

<div class="help-layout">
    <!-- Sidebar navigation -->
    <aside class="help-sidebar" aria-label="Help categories navigation">
        <ul class="help-sidebar-nav" id="helpSidebarNav">
            <?php foreach ($categories as $slug => $cat): ?>
            <li>
                <a href="#<?php echo $slug; ?>" data-category="<?php echo $slug; ?>">
                    <i class="fas <?php echo $cat['icon']; ?>" style="color:<?php echo $cat['color']; ?>" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($cat['title']); ?>
                    <span class="help-nav-count"><?php echo count($cat['articles']); ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <!-- Main content -->
    <main class="help-main" id="helpMain">
        <!-- Category cards grid -->
        <div class="help-categories-grid" id="helpCatGrid">
            <?php foreach ($categories as $slug => $cat): ?>
            <a href="#<?php echo $slug; ?>" class="help-cat-card" data-category="<?php echo $slug; ?>">
                <div class="help-cat-card-icon" style="background:<?php echo $cat['color']; ?>20; color:<?php echo $cat['color']; ?>">
                    <i class="fas <?php echo $cat['icon']; ?>" aria-hidden="true"></i>
                </div>
                <h3><?php echo htmlspecialchars($cat['title']); ?></h3>
                <p>
                    <?php
                    $titles = array_column($cat['articles'], 'title');
                    echo htmlspecialchars(implode(' · ', array_slice($titles, 0, 3))) . '…';
                    ?>
                </p>
                <span class="help-article-count"><i class="fas fa-file-alt" aria-hidden="true"></i> <?php echo count($cat['articles']); ?> articles</span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Search status -->
        <div class="help-search-status" id="helpSearchStatus" aria-live="polite"></div>

        <!-- No results -->
        <div class="help-no-results" id="helpNoResults">
            <i class="fas fa-search" aria-hidden="true"></i>
            <h3>No results found</h3>
            <p>Try a different search term or browse the categories above.</p>
        </div>

        <!-- Category sections with articles -->
        <?php foreach ($categories as $slug => $cat): ?>
        <section class="help-category-section" id="<?php echo $slug; ?>" data-category-section="<?php echo $slug; ?>">
            <nav class="help-breadcrumb" aria-label="Breadcrumb">
                <a href="/help">Help Center</a>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($cat['title']); ?></span>
            </nav>
            <div class="help-category-header">
                <div class="cat-icon" style="background:<?php echo $cat['color']; ?>20; color:<?php echo $cat['color']; ?>">
                    <i class="fas <?php echo $cat['icon']; ?>" aria-hidden="true"></i>
                </div>
                <h2><?php echo htmlspecialchars($cat['title']); ?></h2>
            </div>

            <?php foreach ($cat['articles'] as $article): ?>
            <div class="help-accordion" data-article-id="<?php echo $article['id']; ?>" data-tags="<?php echo htmlspecialchars($article['tags']); ?>" data-category="<?php echo $slug; ?>">
                <button class="help-accordion-header" aria-expanded="false" aria-controls="body-<?php echo $article['id']; ?>" id="header-<?php echo $article['id']; ?>">
                    <span><?php echo htmlspecialchars($article['title']); ?></span>
                    <i class="fas fa-chevron-down acc-chevron" aria-hidden="true"></i>
                </button>
                <div class="help-accordion-body" id="body-<?php echo $article['id']; ?>" role="region" aria-labelledby="header-<?php echo $article['id']; ?>">
                    <?php echo $article['content']; ?>
                    <div class="help-article-meta">
                        <span class="help-article-updated"><i class="fas fa-clock" aria-hidden="true"></i> Updated <?php echo date('M j, Y', strtotime($article['updated'])); ?></span>
                        <div class="help-article-feedback">
                            <span>Was this helpful?</span>
                            <button class="help-feedback-btn" data-article="<?php echo $article['id']; ?>" data-helpful="1" aria-label="Yes, this was helpful">
                                <i class="fas fa-thumbs-up" aria-hidden="true"></i> Yes
                            </button>
                            <button class="help-feedback-btn" data-article="<?php echo $article['id']; ?>" data-helpful="0" aria-label="No, this was not helpful">
                                <i class="fas fa-thumbs-down" aria-hidden="true"></i> No
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>

        <!-- Still need help -->
        <div class="help-contact">
            <h2>Still need help?</h2>
            <p>Our team is here for you. Choose the best way to reach us.</p>
            <div class="help-contact-options">
                <a href="mailto:support@gositeme.com" class="help-contact-card">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <h4>Email Support</h4>
                    <p>support@gositeme.com<br>Response within 24 hours</p>
                </a>
                <a href="javascript:void(0)" class="help-contact-card" id="helpOpenChat">
                    <i class="fas fa-comments" aria-hidden="true"></i>
                    <h4>Live Chat</h4>
                    <p>Chat with Alfred AI<br>Available 24/7</p>
                </a>
                <a href="/docs/" class="help-contact-card">
                    <i class="fas fa-book" aria-hidden="true"></i>
                    <h4>Documentation</h4>
                    <p>API reference, guides<br>& tutorials</p>
                </a>
            </div>
            <p style="margin-top:24px;color:var(--help-text-muted);font-size:.85rem;">
                <i class="fas fa-users" aria-hidden="true"></i> Community Forum — <em>Coming Soon</em>
            </p>
        </div>
    </main>
</div>

<script src="/assets/js/help-engine.js"></script>

<?php require_once 'includes/site-footer.inc.php'; ?>
