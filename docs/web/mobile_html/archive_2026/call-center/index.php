<?php
/**
 * AI Call Center — Sales Funnel Landing Page
 * Products: Starter ($99), Pro Dialer ($299), Inbound ($499), Collections ($249), Appointment ($149), Enterprise ($999)
 * PIDs: 53, 54, 56, 58, 57, 55
 */
$pageTitle = "AI Call Center — Replace Your Entire Phone Team";
$pageDesc  = "AI-powered outbound dialing, inbound call center, appointment setting, and collections. Scale from 1 to 1,000 agents instantly.";

try {
    require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME, GOSITEME_DB_USER, GOSITEME_DB_PASS);
    $clientCount = $pdo->query("SELECT COUNT(*) FROM tblclients WHERE status='Active'")->fetchColumn();
} catch (Exception $e) { $clientCount = 22; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
<link rel="canonical" href="https://gositeme.com/call-center/">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0a0a0f;color:#e0e0e0;overflow-x:hidden}
a{color:#ff6b35;text-decoration:none}
.container{max-width:1200px;margin:0 auto;padding:0 24px}

.topbar{background:#000;padding:8px 0;text-align:center;font-size:13px;color:#888;border-bottom:1px solid #1a1a2e}
.topbar .live{color:#ff6b35;font-weight:600}

nav{background:rgba(10,10,15,0.95);backdrop-filter:blur(20px);padding:16px 0;position:sticky;top:0;z-index:100;border-bottom:1px solid rgba(255,107,53,0.1)}
nav .container{display:flex;align-items:center;justify-content:space-between}
nav .logo{font-size:22px;font-weight:800;background:linear-gradient(135deg,#ff6b35,#ff3366);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
nav .links{display:flex;gap:28px;font-size:14px}
nav .links a{color:#999;transition:color .2s}
nav .links a:hover{color:#fff}
.cta-btn{background:linear-gradient(135deg,#ff6b35,#ff3366);color:#fff!important;padding:10px 24px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;transition:all .3s;border:none;cursor:pointer;-webkit-text-fill-color:#fff}
.cta-btn:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(255,107,53,.3)}
.cta-btn-lg{padding:16px 40px;font-size:18px;border-radius:12px}

/* Hero */
.hero{padding:100px 0 80px;text-align:center;position:relative}
.hero::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(255,107,53,.12),transparent 60%);pointer-events:none}
.hero .badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,107,53,.1);border:1px solid rgba(255,107,53,.3);color:#ff6b35;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:24px}
.hero h1{font-size:clamp(36px,5vw,60px);font-weight:900;line-height:1.1;margin-bottom:20px;background:linear-gradient(135deg,#fff,#ff6b35);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:20px;color:#888;max-width:700px;margin:0 auto 40px;line-height:1.6}

/* ROI Calculator */
.roi{max-width:600px;margin:40px auto 0;background:#111;border:1px solid #222;border-radius:16px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.roi h3{text-align:center;font-size:20px;font-weight:800;margin-bottom:24px;color:#ff6b35}
.roi-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #1a1a2e}
.roi-row .label{font-size:14px;color:#bbb}
.roi-row .value{font-size:16px;font-weight:700;color:#fff}
.roi-row .value.bad{color:#ff4444}
.roi-row .value.good{color:#00ff88}
.roi-row .value.great{color:#ff6b35;font-size:24px}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;padding:60px 0;border-bottom:1px solid #1a1a2e}
.stat{text-align:center;padding:24px}
.stat .num{font-size:36px;font-weight:900;background:linear-gradient(135deg,#ff6b35,#ff3366);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat .label{color:#888;font-size:14px;margin-top:6px}

/* Use Cases */
.usecases{padding:80px 0;background:#0d0d14}
.usecases h2{text-align:center;font-size:36px;font-weight:900;margin-bottom:16px}
.usecases .sub{text-align:center;color:#888;font-size:18px;margin-bottom:48px}
.uc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
.uc-card{background:#111;border:1px solid #222;border-radius:16px;padding:32px;transition:all .3s}
.uc-card:hover{border-color:rgba(255,107,53,.3);transform:translateY(-4px)}
.uc-card .icon{font-size:36px;margin-bottom:16px}
.uc-card h3{font-size:20px;font-weight:700;margin-bottom:8px;color:#ff6b35}
.uc-card p{color:#888;font-size:14px;line-height:1.6;margin-bottom:16px}
.uc-card .price-tag{color:#ff6b35;font-weight:700;font-size:18px}

/* How It Works */
.how{padding:80px 0}
.how h2{text-align:center;font-size:36px;font-weight:900;margin-bottom:48px}
.steps{display:flex;gap:24px;max-width:900px;margin:0 auto;flex-wrap:wrap;justify-content:center}
.step{flex:1;min-width:200px;text-align:center;padding:32px 20px;position:relative}
.step .num{width:48px;height:48px;background:linear-gradient(135deg,#ff6b35,#ff3366);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#fff;margin:0 auto 16px}
.step h3{font-size:16px;font-weight:700;margin-bottom:8px}
.step p{color:#888;font-size:13px;line-height:1.5}

/* Pricing */
.pricing{padding:80px 0;background:#0d0d14}
.pricing h2{text-align:center;font-size:36px;font-weight:900;margin-bottom:16px}
.pricing .sub{text-align:center;color:#888;font-size:18px;margin-bottom:48px}
.price-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px}
.price-card{background:#111;border:1px solid #222;border-radius:16px;padding:32px;transition:all .3s;position:relative}
.price-card:hover{border-color:rgba(255,107,53,.3);transform:translateY(-4px)}
.price-card.featured{border-color:rgba(255,107,53,.4);background:linear-gradient(180deg,rgba(255,107,53,.08),#111)}
.price-card.featured::before{content:'HIGHEST ROI';position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#ff6b35,#ff3366);color:#fff;padding:4px 16px;border-radius:12px;font-size:11px;font-weight:800}
.price-card .tier{font-size:14px;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
.price-card .use{color:#ff6b35;font-size:13px;font-weight:600;margin-bottom:12px}
.price-card .amount{font-size:48px;font-weight:900;color:#fff;margin-bottom:4px}
.price-card .amount sup{font-size:20px;color:#888}
.price-card .period{color:#666;font-size:14px;margin-bottom:24px}
.price-card ul{list-style:none;margin-bottom:24px}
.price-card ul li{padding:8px 0;font-size:14px;color:#bbb;border-bottom:1px solid #1a1a2e;display:flex;align-items:center;gap:8px}
.price-card ul li::before{content:'✓';color:#00ff88;font-weight:700}

/* CTA */
.cta-section{padding:80px 0;text-align:center}
.cta-section h2{font-size:36px;font-weight:900;margin-bottom:16px}
.cta-section p{color:#888;font-size:18px;margin-bottom:32px;max-width:600px;margin-left:auto;margin-right:auto}

footer{padding:32px 0;text-align:center;color:#555;font-size:13px;border-top:1px solid #1a1a2e}
footer a{color:#888}

@media(max-width:768px){
.stats{grid-template-columns:repeat(2,1fr)}
.price-grid{grid-template-columns:1fr}
nav .links{display:none}
.steps{flex-direction:column}
}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <span class="live">📞</span> AI agents handle 10,000+ calls/day — <strong>you handle zero</strong> &nbsp;|&nbsp; <a href="#pricing" style="color:#ff6b35">See Plans →</a>
</div>

<!-- Navigation -->
<nav>
    <div class="container">
        <div class="logo">Alfred Call Center</div>
        <div class="links">
            <a href="#usecases">Solutions</a>
            <a href="#how">How It Works</a>
            <a href="#pricing">Pricing</a>
            <a href="https://gositeme.com/gohostme/" target="_blank">Dashboard</a>
        </div>
        <a href="#pricing" class="cta-btn">Start Free Trial</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="badge">🤖 Powered by Claude AI — Sounds 100% Human</div>
        <h1>Your AI Call Center<br>Costs 90% Less Than Human Agents</h1>
        <p>Outbound dialing, inbound reception, collections, appointment setting — all handled by AI agents that never call in sick, never quit, and never need training.</p>
        <a href="#pricing" class="cta-btn cta-btn-lg">See All Solutions →</a>

        <!-- ROI Calculator -->
        <div class="roi">
            <h3>💰 Your Cost Comparison</h3>
            <div class="roi-row"><span class="label">5 Human Call Center Agents (avg.)</span><span class="value bad">$15,000/mo</span></div>
            <div class="roi-row"><span class="label">Office Space + Equipment</span><span class="value bad">$3,000/mo</span></div>
            <div class="roi-row"><span class="label">Training, HR, Benefits</span><span class="value bad">$4,000/mo</span></div>
            <div class="roi-row"><span class="label"><strong>Total Human Cost</strong></span><span class="value bad">$22,000/mo</span></div>
            <div class="roi-row"><span class="label">Alfred AI Call Center (Pro)</span><span class="value good">$299/mo</span></div>
            <div class="roi-row"><span class="label"><strong>Your Monthly Savings</strong></span><span class="value great">$21,701/mo</span></div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="stats">
    <div class="container" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px">
        <div class="stat"><div class="num">10,000+</div><div class="label">Calls/Day Capacity</div></div>
        <div class="stat"><div class="num">90%</div><div class="label">Cost Reduction</div></div>
        <div class="stat"><div class="num">24/7</div><div class="label">No Breaks. Ever.</div></div>
        <div class="stat"><div class="num">&lt;1s</div><div class="label">Response Time</div></div>
    </div>
</section>

<!-- Use Cases -->
<section class="usecases" id="usecases">
    <div class="container">
        <h2>Six AI Call Center Solutions</h2>
        <div class="sub">From outbound sales to debt collection — one platform, infinite scale</div>
        <div class="uc-grid">
            <div class="uc-card">
                <div class="icon">📞</div>
                <h3>AI Outbound Dialer</h3>
                <p>Blast through lead lists. AI calls prospects, qualifies them, and schedules meetings with your sales team. Handles objections like a senior rep.</p>
                <div class="price-tag">from $99/mo</div>
            </div>
            <div class="uc-card">
                <div class="icon">📥</div>
                <h3>AI Inbound Call Center</h3>
                <p>Replace your entire inbound team. AI agents handle unlimited concurrent calls — customer service, technical support, order status, returns. All automated.</p>
                <div class="price-tag">$499/mo</div>
            </div>
            <div class="uc-card">
                <div class="icon">📅</div>
                <h3>AI Appointment Setter</h3>
                <p>Book meetings while you sleep. AI calls leads, presents your value prop, handles objections, and books directly into your calendar. Show rate tracking included.</p>
                <div class="price-tag">$149/mo</div>
            </div>
            <div class="uc-card">
                <div class="icon">💳</div>
                <h3>AI Collections Agent</h3>
                <p>Respectful, FDCPA-compliant collections calls. AI contacts debtors, negotiates payment plans, and processes payments — all with documented compliance.</p>
                <div class="price-tag">$249/mo</div>
            </div>
            <div class="uc-card">
                <div class="icon">📊</div>
                <h3>AI Lead Qualification</h3>
                <p>Stop wasting sales time on bad leads. AI calls every inbound lead within 30 seconds, qualifies them (BANT/MEDDIC), and routes hot prospects to closers.</p>
                <div class="price-tag">from $99/mo</div>
            </div>
            <div class="uc-card">
                <div class="icon">🎯</div>
                <h3>AI Survey & Research</h3>
                <p>Conduct phone surveys at scale. Customer satisfaction (CSAT), market research, political polling, event follow-ups. Structured data delivered to your dashboard.</p>
                <div class="price-tag">from $99/mo</div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="how" id="how">
    <div class="container">
        <h2>Go Live in 15 Minutes</h2>
        <div class="steps">
            <div class="step">
                <div class="num">1</div>
                <h3>Upload Your Script</h3>
                <p>Paste your call script or let AI generate one from your business description.</p>
            </div>
            <div class="step">
                <div class="num">2</div>
                <h3>Add Your Contacts</h3>
                <p>Upload a CSV of leads or connect your CRM. We support Salesforce, HubSpot, and more.</p>
            </div>
            <div class="step">
                <div class="num">3</div>
                <h3>Choose Your Voice</h3>
                <p>Pick from 50+ natural voices or clone your own. Male, female, multiple languages.</p>
            </div>
            <div class="step">
                <div class="num">4</div>
                <h3>Launch & Scale</h3>
                <p>Hit go. AI dials your list, handles conversations, logs results, and books meetings. Scale to 1,000+ simultaneous calls.</p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section class="pricing" id="pricing">
    <div class="container">
        <h2>Call Center Plans</h2>
        <div class="sub">Start with any solution. Scale to all of them. No contracts.</div>
        <div class="price-grid">
            <!-- Outbound Starter -->
            <div class="price-card">
                <div class="tier">Outbound Starter</div>
                <div class="use">Cold Calling & Lead Gen</div>
                <div class="amount"><sup>$</sup>99<sup>/mo</sup></div>
                <div class="period">Get your feet wet</div>
                <ul>
                    <li>1 AI Dialer Agent</li>
                    <li>500 outbound calls/month</li>
                    <li>1 Phone Number</li>
                    <li>Custom call scripts</li>
                    <li>Lead qualification</li>
                    <li>Call recordings & transcripts</li>
                    <li>Dashboard & analytics</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=53" class="cta-btn" style="width:100%;text-align:center">Get Started</a>
            </div>
            <!-- Appointment Setter -->
            <div class="price-card">
                <div class="tier">Appointment Setter</div>
                <div class="use">Book Meetings On Autopilot</div>
                <div class="amount"><sup>$</sup>149<sup>/mo</sup></div>
                <div class="period">Fill your calendar</div>
                <ul>
                    <li>2 AI Booking Agents</li>
                    <li>1,000 calls/month</li>
                    <li>Calendar integration</li>
                    <li>Confirmation texts</li>
                    <li>No-show follow-ups</li>
                    <li>Show rate tracking</li>
                    <li>CRM sync</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=57" class="cta-btn" style="width:100%;text-align:center">Get Started</a>
            </div>
            <!-- Collections -->
            <div class="price-card">
                <div class="tier">Collections Agent</div>
                <div class="use">FDCPA-Compliant Recovery</div>
                <div class="amount"><sup>$</sup>249<sup>/mo</sup></div>
                <div class="period">Recover revenue</div>
                <ul>
                    <li>3 AI Collection Agents</li>
                    <li>2,000 calls/month</li>
                    <li>FDCPA compliance built-in</li>
                    <li>Payment plan negotiation</li>
                    <li>Payment processing</li>
                    <li>Dispute handling</li>
                    <li>Full audit trail</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=58" class="cta-btn" style="width:100%;text-align:center">Get Started</a>
            </div>
            <!-- Pro Dialer -->
            <div class="price-card featured">
                <div class="tier">Pro Dialer</div>
                <div class="use">Outbound Sales Machine</div>
                <div class="amount"><sup>$</sup>299<sup>/mo</sup></div>
                <div class="period">Scale your outreach</div>
                <ul>
                    <li>5 AI Dialer Agents</li>
                    <li>5,000 calls/month</li>
                    <li>5 Phone Numbers</li>
                    <li>A/B script testing</li>
                    <li>Warm transfer to sales</li>
                    <li>CRM integration</li>
                    <li>Priority support</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=54" class="cta-btn" style="width:100%;text-align:center">Get Started</a>
            </div>
            <!-- Inbound -->
            <div class="price-card">
                <div class="tier">Inbound Center</div>
                <div class="use">Full Customer Service</div>
                <div class="amount"><sup>$</sup>499<sup>/mo</sup></div>
                <div class="period">Replace your support team</div>
                <ul>
                    <li>10 Concurrent AI Agents</li>
                    <li>Unlimited inbound calls</li>
                    <li>Multi-queue routing</li>
                    <li>Knowledge base integration</li>
                    <li>Escalation rules</li>
                    <li>Multi-language support</li>
                    <li>Custom IVR flows</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=56" class="cta-btn" style="width:100%;text-align:center">Get Started</a>
            </div>
            <!-- Enterprise -->
            <div class="price-card">
                <div class="tier">Enterprise</div>
                <div class="use">Unlimited Everything</div>
                <div class="amount"><sup>$</sup>999<sup>/mo</sup></div>
                <div class="period">Full call center replacement</div>
                <ul>
                    <li>Unlimited AI Agents</li>
                    <li>Unlimited calls</li>
                    <li>Unlimited phone numbers</li>
                    <li>All solutions included</li>
                    <li>White-label option</li>
                    <li>API access</li>
                    <li>Dedicated account team</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=55" class="cta-btn" style="width:100%;text-align:center">Contact Sales</a>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Stop Paying $22,000/mo for What AI Does for $299</h2>
        <p>Join <?= number_format($clientCount) ?>+ businesses automating their phones. Start your free trial today.</p>
        <a href="https://gositeme.com/cart.php?a=add&pid=53" class="cta-btn cta-btn-lg">Start Free Trial →</a>
    </div>
</section>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> GoSiteMe — Alfred AI Call Center &nbsp;|&nbsp; <a href="https://gositeme.com/">Home</a> &nbsp;|&nbsp; <a href="https://gositeme.com/voice-ai/">AI Voice</a> &nbsp;|&nbsp; <a href="https://gositeme.com/hosting/">Hosting</a> &nbsp;|&nbsp; <a href="https://gositeme.com/ide/">Alfred IDE</a></p>
    </div>
</footer>
</body>
</html>
