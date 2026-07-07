<?php
$article_meta = [
    'title' => 'How to Set Up an AI Voice Agent in 10 Minutes',
    'description' => 'Step-by-step tutorial for setting up an AI voice agent using Alfred. Includes code examples, configuration tips, and best practices for voice AI deployment.',
    'date' => '2026-02-22',
    'author' => 'Alfred AI Team',
    'category' => 'tutorials',
    'read_time' => '14 min read',
    'featured_image' => '/assets/img/blog/placeholder.png',
    'tags' => ['voice AI', 'tutorial', 'AI setup', 'voice agent', 'developer guide'],
    'slug' => 'ai-voice-agent-setup-guide',
];

ob_start();
?>

<div class="article-cta" style="background:linear-gradient(135deg,rgba(108,92,231,0.1),rgba(162,155,254,0.05));border:1px solid rgba(108,92,231,0.2);border-radius:16px;padding:24px 28px;margin-bottom:32px;">
    <h3 style="margin-top:0;font-size:1.1rem;">What You'll Build</h3>
    <ul style="margin-bottom:0;">
        <li>A fully functional AI voice agent that answers phone calls</li>
        <li>Custom knowledge base with your business information</li>
        <li>Phone number forwarding to your AI agent</li>
        <li>Real-time call monitoring dashboard</li>
    </ul>
</div>

<h2>Prerequisites</h2>

<p>Before you start, you'll need:</p>

<ul>
    <li>An <a href="/alfred-landing.php">Alfred AI account</a> (free trial available)</li>
    <li>A business phone number (or Alfred can provide one)</li>
    <li>Your business information: hours, services, FAQ content</li>
    <li>10 minutes of focused time</li>
</ul>

<p>No coding is required for basic setup. If you want to use the API for advanced customization, we'll cover that too.</p>

<h2>Step 1: Create Your Voice Agent (2 minutes)</h2>

<p>Log into your Alfred dashboard and navigate to <strong>Voice AI → Create Agent</strong>. You'll configure three things:</p>

<h3>Choose Your Voice</h3>
<p>Alfred offers 24 neural voices across different accents, genders, and personality styles. For most businesses, we recommend starting with a warm, professional voice. You can always change it later.</p>

<p>Popular choices:</p>
<ul>
    <li><strong>Sarah</strong> — Warm, professional, North American English</li>
    <li><strong>James</strong> — Confident, business-oriented, mid-Atlantic accent</li>
    <li><strong>Priya</strong> — Friendly, clear, neutral accent</li>
</ul>

<h3>Set Your Greeting</h3>
<p>This is what callers hear first. Keep it concise and professional:</p>

<pre><code>"Thank you for calling [Your Business Name]. This is Alfred, your AI assistant. How can I help you today?"</code></pre>

<p>Avoid long greetings. Callers want to state their purpose quickly. Under 8 seconds is ideal.</p>

<h3>Define Your Agent's Role</h3>
<p>Write a brief system prompt that tells the AI who it is and what it should do. Example:</p>

<pre><code>You are an AI receptionist for Brightside Dental Clinic.
Your primary tasks:
- Schedule appointments (hygiene, exam, emergency)
- Answer questions about services and hours
- Collect new patient information
- Transfer urgent calls to the on-call dentist

Hours: Monday-Friday 8AM-5PM, Saturday 9AM-1PM
Address: 123 Main Street, Suite 200
Emergency line: 555-0199</code></pre>

<h2>Step 2: Build Your Knowledge Base (3 minutes)</h2>

<p>Your voice agent is only as good as its knowledge. There are three ways to add information:</p>

<h3>Option A: Upload Documents</h3>
<p>Upload PDFs, Word docs, or text files containing your business information. Alfred automatically extracts and indexes the content. Great for existing FAQ documents, service menus, or policy papers.</p>

<h3>Option B: Add Q&A Pairs</h3>
<p>Manually enter questions and answers for the most common inquiries. This gives you the most control over responses:</p>

<pre><code>Q: What are your hours?
A: We're open Monday through Friday, 8 AM to 5 PM,
   and Saturday from 9 AM to 1 PM. We're closed on Sundays.

Q: Do you accept walk-ins?
A: We accept walk-ins for emergencies, but we recommend
   scheduling an appointment for the best experience.
   I can help you book one right now if you'd like.

Q: What insurance do you accept?
A: We accept most major insurance plans including
   Delta Dental, Cigna, Aetna, Blue Cross, and MetLife.
   I can verify your specific plan before your visit.</code></pre>

<h3>Option C: Connect Your Website</h3>
<p>Point Alfred at your website URL. It crawls your pages and extracts business information, services, pricing, and FAQs automatically. This is the fastest option for businesses with comprehensive websites.</p>

<h2>Step 3: Configure Call Handling (2 minutes)</h2>

<h3>Set Business Hours Behavior</h3>
<p>Configure how Alfred handles calls during and after business hours:</p>

<ul>
    <li><strong>During hours:</strong> Answer, assist, schedule, transfer to staff if needed</li>
    <li><strong>After hours:</strong> Answer, provide info, schedule for next business day, handle emergencies</li>
</ul>

<h3>Define Transfer Rules</h3>
<p>Tell Alfred when to transfer calls to a human. Examples:</p>

<pre><code>Transfer to human when:
- Caller explicitly asks for a person
- Issue involves a complaint or legal matter
- Emergency situations (medical, billing dispute)
- Caller is angry or frustrated after 2 attempts

Transfer destination:
- General: (555) 123-4567
- Emergency: (555) 123-4568
- Billing: (555) 123-4569</code></pre>

<h3>Enable Call Recording</h3>
<p>Turn on call recording for quality assurance. All recordings are encrypted and stored securely. You can review them in your dashboard to improve your AI's performance over time.</p>

<h2>Step 4: Connect Your Phone Number (2 minutes)</h2>

<p>You have three options for routing calls to your AI agent:</p>

<h3>Option A: Forward Your Existing Number</h3>
<p>Set up call forwarding from your existing business phone to your Alfred number. Most carriers support "forward on no answer" — meaning the AI only picks up if no one else does.</p>

<p>How to set up forwarding (most carriers):</p>
<pre><code># Forward all calls
Dial: *72 + [your Alfred number]

# Forward on no answer (after 3 rings)
Dial: *71 + [your Alfred number]

# Cancel forwarding
Dial: *73</code></pre>

<h3>Option B: Get a New Number</h3>
<p>Alfred can provision a new phone number for your AI agent. Choose local or toll-free. This is ideal for adding a dedicated AI line alongside your existing number.</p>

<h3>Option C: SIP/VoIP Integration</h3>
<p>For businesses using VoIP systems (RingCentral, 8x8, Vonage), Alfred integrates via SIP trunk. This requires some technical setup but provides the most seamless experience.</p>

<h2>Step 5: Test Your Agent (1 minute)</h2>

<p>Before going live, test your agent thoroughly:</p>

<h3>Quick Test</h3>
<p>Click the "Test Call" button in your dashboard. Alfred will call your personal phone so you can interact with your agent as a customer would.</p>

<h3>What to Test</h3>
<ul>
    <li>Greeting — does it sound right?</li>
    <li>Common questions — are answers accurate?</li>
    <li>Scheduling — does it book appointments correctly?</li>
    <li>Edge cases — what happens with unusual requests?</li>
    <li>Transfer — does it transfer when it should?</li>
    <li>After-hours — does behavior change correctly?</li>
</ul>

<p>Make adjustments to your knowledge base and system prompt based on test results. Most agents need 2-3 rounds of testing before they're ready for production.</p>

<div class="article-cta">
    <h3>Ready to Build Your Voice Agent?</h3>
    <p>Sign up for Alfred and have your AI voice agent answering calls in under 10 minutes.</p>
    <a href="/alfred-landing.php" class="btn"><i class="fas fa-microphone"></i> Create Your Agent</a>
    <a href="/voice.php" class="btn" style="background:transparent;border:1px solid rgba(108,92,231,0.4);margin-left:12px;"><i class="fas fa-play"></i> See Voice Demo</a>
</div>

<h2>Advanced: API Integration</h2>

<p>For developers who want programmatic control, Alfred's <a href="/developer-portal.php">Voice API</a> provides full access to agent configuration, call management, and real-time events.</p>

<h3>Create an Agent via API</h3>
<pre><code>curl -X POST https://api.gositeme.com/v1/voice/agents \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Reception Agent",
    "voice": "sarah-warm",
    "greeting": "Thank you for calling. How can I help?",
    "system_prompt": "You are a helpful receptionist...",
    "transfer_number": "+15551234567",
    "hours": {
      "monday": "08:00-17:00",
      "tuesday": "08:00-17:00",
      "wednesday": "08:00-17:00",
      "thursday": "08:00-17:00",
      "friday": "08:00-17:00",
      "saturday": "09:00-13:00"
    }
  }'</code></pre>

<h3>Handle Real-Time Events</h3>
<p>Set up webhooks to receive events during calls — useful for integrating with your CRM, booking system, or custom workflows:</p>

<pre><code>// Webhook payload for appointment booking
{
  "event": "appointment_booked",
  "call_id": "call_abc123",
  "caller": "+15559876543",
  "appointment": {
    "type": "dental_exam",
    "date": "2026-03-15",
    "time": "10:00",
    "provider": "Dr. Smith",
    "notes": "New patient, has dental anxiety"
  }
}</code></pre>

<p>Full API documentation is available at the <a href="/developer-portal.php">Developer Portal</a>.</p>

<h2>Tips for Best Results</h2>

<h3>1. Keep the System Prompt Focused</h3>
<p>Don't try to make your agent do everything. A focused agent that handles 5 tasks well is better than a generalist that handles 20 tasks poorly.</p>

<h3>2. Use Real Caller Language</h3>
<p>Write your Q&A pairs using the words your customers actually use, not industry jargon. If customers say "teeth cleaning" instead of "prophylactic oral hygiene procedure," your knowledge base should use "teeth cleaning."</p>

<h3>3. Listen to Call Recordings</h3>
<p>Review your first 50 calls. You'll discover questions you didn't anticipate, phrasing you didn't expect, and edge cases that need handling. Update your knowledge base accordingly.</p>

<h3>4. Set Up Fallback Behavior</h3>
<p>When the AI doesn't know the answer, it should say so gracefully and offer to transfer. "I don't have that information right now, but let me connect you with someone who does" is always better than making something up.</p>

<h3>5. Iterate Weekly</h3>
<p>The best AI agents improve continuously. Spend 15 minutes each week reviewing call logs, adding new Q&A pairs, and refining responses. After a month, your agent will handle 90%+ of calls flawlessly.</p>

<h2>What's Next?</h2>

<p>Once your basic voice agent is running, explore these advanced capabilities:</p>

<ul>
    <li><strong>Outbound calls:</strong> Alfred can make proactive calls for appointment reminders, follow-ups, and surveys</li>
    <li><strong>Multi-agent fleet:</strong> Create specialized agents for different departments via the <a href="/fleet-dashboard.php">Fleet Dashboard</a></li>
    <li><strong>Custom integrations:</strong> Connect to your CRM, calendar, or booking system via webhook or API</li>
    <li><strong>Voice cloning:</strong> Clone your own voice or create a custom brand voice with <a href="/voice-cloning.php">Voice Cloning</a></li>
    <li><strong>Analytics:</strong> Deep dive into call patterns, resolution rates, and customer satisfaction metrics</li>
</ul>

<div class="article-cta">
    <h3>Build Your AI Voice Agent Now</h3>
    <p>No credit card required. Your first 50 calls are free. Go from zero to AI receptionist in 10 minutes.</p>
    <a href="/alfred-landing.php" class="btn"><i class="fas fa-rocket"></i> Start Free Trial</a>
    <a href="/voice-products.php" class="btn" style="background:transparent;border:1px solid rgba(108,92,231,0.4);margin-left:12px;"><i class="fas fa-list"></i> Voice Products</a>
</div>

<?php
$article_content = ob_get_clean();
include __DIR__ . '/article-template.inc.php';
