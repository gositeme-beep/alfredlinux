<?php
/**
 * Mine GSM — Marketing Landing Page for Browser Mining
 * Convinces users to start mining GSM tokens while browsing
 */
$page_title = 'Mine GSM Tokens — Earn While You Browse | GoSiteMe';
$page_description = 'Turn your browsing time into real value. Mine GSM tokens effortlessly while using the GoSiteMe browser — zero setup, zero cost, 80% goes directly to you.';
$page_canonical = 'https://root.com/mine';
require_once __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
:root {
    --m-void: #030308;
    --m-surface: #0c0c1a;
    --m-card: rgba(255,215,0,0.03);
    --m-border: rgba(255,215,0,0.1);
    --m-gold: #FFD700;
    --m-amber: #f59e0b;
    --m-green: #00e676;
    --m-purple: #6C5CE7;
    --m-cyan: #18ffff;
    --m-text: rgba(255,255,255,0.88);
    --m-muted: rgba(255,255,255,0.5);
    --m-grad: linear-gradient(135deg, #FFD700 0%, #f59e0b 50%, #ef4444 100%);
    --m-radius: 16px;
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--m-void);color:var(--m-text);line-height:1.6;overflow-x:hidden}
a{color:var(--m-gold);text-decoration:none}

/* Hero */
.mine-hero{position:relative;padding:80px 20px 60px;text-align:center;overflow:hidden;min-height:70vh;display:flex;flex-direction:column;align-items:center;justify-content:center}
.mine-hero::before{content:'';position:absolute;top:-200px;left:50%;transform:translateX(-50%);width:1000px;height:1000px;background:radial-gradient(circle,rgba(255,215,0,.08) 0%,transparent 60%);pointer-events:none;animation:goldPulse 8s ease-in-out infinite}
@keyframes goldPulse{0%,100%{opacity:.3;transform:translateX(-50%) scale(1)}50%{opacity:.6;transform:translateX(-50%) scale(1.15)}}

.mine-hero .badge{display:inline-block;background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.2);color:var(--m-gold);padding:6px 20px;border-radius:50px;font-size:.85rem;font-weight:600;margin-bottom:20px;letter-spacing:.05em;text-transform:uppercase}

.mine-hero h1{font-size:clamp(2.5rem,6vw,4.5rem);font-weight:900;letter-spacing:-.03em;line-height:1.1;max-width:900px;margin-bottom:20px}
.mine-hero h1 .gold{background:linear-gradient(135deg,var(--m-gold),var(--m-amber));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

.mine-hero .subtitle{font-size:1.2rem;color:var(--m-muted);max-width:700px;margin-bottom:40px}

.mine-hero .hero-stats{display:flex;gap:40px;justify-content:center;flex-wrap:wrap;margin-bottom:40px}
.mine-hero .hero-stat{text-align:center}
.mine-hero .hero-stat .val{font-size:2rem;font-weight:800;font-family:'JetBrains Mono',monospace}
.mine-hero .hero-stat .lbl{font-size:.8rem;color:var(--m-muted);text-transform:uppercase;letter-spacing:.04em}

.mine-cta{display:inline-flex;align-items:center;gap:10px;background:var(--m-grad);color:#000;padding:16px 40px;border-radius:50px;font-size:1.1rem;font-weight:800;text-transform:uppercase;letter-spacing:.02em;transition:transform .2s,box-shadow .2s;cursor:pointer;border:none}
.mine-cta:hover{transform:translateY(-3px);box-shadow:0 10px 40px rgba(255,215,0,.25);color:#000}
.mine-cta-secondary{background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.2);color:var(--m-gold);margin-left:12px}
.mine-cta-secondary:hover{background:rgba(255,215,0,.15)}

/* Sections */
.mine-section{padding:80px 20px;max-width:1200px;margin:0 auto}
.mine-section h2{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:800;text-align:center;margin-bottom:16px;letter-spacing:-.02em}
.mine-section .section-sub{text-align:center;color:var(--m-muted);font-size:1.05rem;max-width:700px;margin:0 auto 50px}

/* How It Works */
.how-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:30px}
.how-card{background:var(--m-surface);border:1px solid var(--m-border);border-radius:var(--m-radius);padding:40px 30px;text-align:center;position:relative;overflow:hidden;transition:border-color .3s}
.how-card:hover{border-color:rgba(255,215,0,.25)}
.how-card .step{position:absolute;top:16px;right:20px;font-size:3rem;font-weight:900;color:rgba(255,215,0,.06);font-family:'JetBrains Mono',monospace}
.how-card .icon{font-size:2.5rem;margin-bottom:20px;color:var(--m-gold)}
.how-card h3{font-size:1.2rem;font-weight:700;margin-bottom:10px}
.how-card p{color:var(--m-muted);font-size:.9rem}

/* Trust Section */
.trust-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.trust-card{background:var(--m-surface);border:1px solid var(--m-border);border-radius:12px;padding:30px 20px;text-align:center}
.trust-card .icon{font-size:2rem;margin-bottom:12px;color:var(--m-green)}
.trust-card h4{font-size:.95rem;font-weight:700;margin-bottom:6px}
.trust-card p{font-size:.8rem;color:var(--m-muted)}

/* Earnings Calculator */
.calc-wrap{max-width:600px;margin:0 auto;background:var(--m-surface);border:1px solid var(--m-border);border-radius:var(--m-radius);padding:40px;text-align:center}
.calc-wrap label{font-size:.85rem;color:var(--m-muted);text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:6px}
.calc-slider{width:100%;-webkit-appearance:none;height:6px;background:#1a1a2e;border-radius:3px;outline:none;margin-bottom:8px}
.calc-slider::-webkit-slider-thumb{-webkit-appearance:none;width:22px;height:22px;border-radius:50%;background:var(--m-gold);cursor:pointer}
.calc-result{margin-top:30px;padding:20px;background:rgba(255,215,0,.05);border-radius:12px;border:1px solid rgba(255,215,0,.15)}
.calc-result .big{font-size:2.5rem;font-weight:800;color:var(--m-gold);font-family:'JetBrains Mono',monospace}
.calc-result .label{font-size:.8rem;color:var(--m-muted)}

/* Tokenomics */
.token-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;text-align:center}
.token-item{background:var(--m-surface);border:1px solid var(--m-border);border-radius:12px;padding:24px 16px}
.token-item .pct{font-size:1.8rem;font-weight:800;font-family:'JetBrains Mono',monospace;color:var(--m-gold)}
.token-item .name{font-size:.8rem;color:var(--m-muted);margin-top:4px}

/* FAQ */
.faq-list{max-width:800px;margin:0 auto}
.faq-item{border:1px solid var(--m-border);border-radius:12px;margin-bottom:12px;overflow:hidden}
.faq-q{padding:18px 24px;font-weight:700;cursor:pointer;display:flex;justify-content:space-between;align-items:center;transition:background .2s}
.faq-q:hover{background:rgba(255,215,0,.03)}
.faq-q .arrow{transition:transform .3s;color:var(--m-gold)}
.faq-a{padding:0 24px;max-height:0;overflow:hidden;transition:max-height .3s,padding .3s;color:var(--m-muted);font-size:.9rem}
.faq-item.open .faq-a{max-height:200px;padding:0 24px 18px}
.faq-item.open .arrow{transform:rotate(180deg)}

/* CTA Bottom */
.cta-bottom{text-align:center;padding:80px 20px;background:linear-gradient(180deg,transparent 0%,rgba(255,215,0,.03) 100%)}
.cta-bottom h2{font-size:clamp(2rem,5vw,3rem);font-weight:900;margin-bottom:16px}
.cta-bottom p{color:var(--m-muted);font-size:1.1rem;margin-bottom:30px;max-width:600px;margin-left:auto;margin-right:auto}

/* Responsive */
@media(max-width:768px){
    .how-grid,.trust-grid{grid-template-columns:1fr}
    .token-grid{grid-template-columns:repeat(2,1fr)}
    .mine-hero .hero-stats{gap:20px}
    .mine-hero .hero-stat .val{font-size:1.5rem}
}
</style>

<!-- ═══════ HERO ═══════ -->
<section class="mine-hero">
    <div class="badge"><i class="fas fa-bolt"></i> Now Live — Start Mining Today</div>
    <h1>Browse the Web.<br>Earn <span class="gold">Real Tokens</span>.</h1>
    <p class="subtitle">GoSiteMe turns your everyday browsing into passive income. Our lightweight mining technology runs silently in the background — using minimal resources — while you earn GSM tokens with every page you visit.</p>

    <div class="hero-stats">
        <div class="hero-stat"><div class="val" style="color:var(--m-gold)" id="liveMiners">—</div><div class="lbl">Active Miners</div></div>
        <div class="hero-stat"><div class="val" style="color:var(--m-green)" id="liveGSM">—</div><div class="lbl">GSM Distributed</div></div>
        <div class="hero-stat"><div class="val" style="color:var(--m-cyan)">80%</div><div class="lbl">Goes to You</div></div>
        <div class="hero-stat"><div class="val" style="color:var(--m-purple)">$0</div><div class="lbl">Setup Cost</div></div>
    </div>

    <div>
        <a href="/search.php" class="mine-cta"><i class="fas fa-rocket"></i> Start Mining Now</a>
        <a href="/alfred-browser" class="mine-cta mine-cta-secondary"><i class="fas fa-download"></i> Get the App</a>
    </div>
</section>

<!-- ═══════ LIVE MINING DASHBOARD ═══════ -->
<?php if (!empty($_SESSION['client_id'])): ?>
<section class="mine-section" id="miningDashboard">
    <h2>Your Mining Dashboard</h2>
    <p class="section-sub">Real-time view of your GSM earnings and mining activity</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1.5rem">
        <div style="background:rgba(255,215,0,0.06);border:1px solid rgba(255,215,0,0.12);border-radius:12px;padding:1.2rem;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#ffd700" id="dashBalance">—</div>
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);margin-top:.3rem">GSM Balance</div>
        </div>
        <div style="background:rgba(0,184,148,0.06);border:1px solid rgba(0,184,148,0.12);border-radius:12px;padding:1.2rem;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#00b894" id="dashMined">—</div>
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);margin-top:.3rem">Total Mined</div>
        </div>
        <div style="background:rgba(108,92,231,0.06);border:1px solid rgba(108,92,231,0.12);border-radius:12px;padding:1.2rem;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#a29bfe" id="dashRate">—</div>
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);margin-top:.3rem">GSM / Hour</div>
        </div>
        <div style="background:rgba(255,140,0,0.06);border:1px solid rgba(255,140,0,0.12);border-radius:12px;padding:1.2rem;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#ff8c00" id="dashRank">—</div>
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);margin-top:.3rem">Miner Rank</div>
        </div>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;justify-content:center;margin-top:1.2rem">
        <a href="/wallet.php" style="padding:.5rem 1rem;border-radius:8px;background:rgba(108,92,231,0.15);color:#a29bfe;font-weight:600;font-size:.8rem;text-decoration:none;border:1px solid rgba(108,92,231,0.2)">💰 Full Wallet</a>
        <a href="/game-leaderboard.php" style="padding:.5rem 1rem;border-radius:8px;background:rgba(255,215,0,0.08);color:#ffd700;font-weight:600;font-size:.8rem;text-decoration:none;border:1px solid rgba(255,215,0,0.12)">🏆 Leaderboard</a>
        <a href="/governance.php" style="padding:.5rem 1rem;border-radius:8px;background:rgba(162,155,254,0.08);color:#a29bfe;font-weight:600;font-size:.8rem;text-decoration:none;border:1px solid rgba(162,155,254,0.12)">🏛️ Governance</a>
    </div>
</section>
<script>
(function(){
    fetch('/api/gsm-economy.php?action=balance', {credentials:'same-origin'})
        .then(r=>r.json()).then(d=>{
            if(d.balance!==undefined) document.getElementById('dashBalance').textContent = parseFloat(d.balance).toLocaleString()+' GSM';
        }).catch(()=>{});
    fetch('/api/mining.php?action=my_stats', {credentials:'same-origin'})
        .then(r=>r.json()).then(d=>{
            if(d.total_mined!==undefined) document.getElementById('dashMined').textContent = parseFloat(d.total_mined).toLocaleString()+' GSM';
            if(d.rate_per_hour!==undefined) document.getElementById('dashRate').textContent = parseFloat(d.rate_per_hour).toFixed(2);
            if(d.rank!==undefined) document.getElementById('dashRank').textContent = '#'+d.rank;
        }).catch(()=>{});
})();
</script>
<?php endif; ?>

<!-- ═══════ HOW IT WORKS ═══════ -->
<section class="mine-section">
    <h2>How It Works</h2>
    <p class="section-sub">Three simple steps. No hardware, no electricity bills, no technical knowledge required.</p>

    <div class="how-grid">
        <div class="how-card">
            <div class="step">01</div>
            <div class="icon"><i class="fas fa-user-plus"></i></div>
            <h3>Create Your Account</h3>
            <p>Sign up for free in 30 seconds. Your mining wallet is created automatically — no crypto experience needed.</p>
        </div>
        <div class="how-card">
            <div class="step">02</div>
            <div class="icon"><i class="fas fa-globe"></i></div>
            <h3>Browse Normally</h3>
            <p>Use GoSiteMe Search or browse the web as you always do. Our mining runs silently in the background using minimal CPU.</p>
        </div>
        <div class="how-card">
            <div class="step">03</div>
            <div class="icon"><i class="fas fa-coins"></i></div>
            <h3>Earn GSM Tokens</h3>
            <p>Watch your GSM balance grow in real-time. Use tokens to pay for services, trade on DEX, or hold as your browsing time becomes value.</p>
        </div>
    </div>
</section>

<!-- ═══════ TRUST SECTION ═══════ -->
<section class="mine-section">
    <h2>Why Trust GoSiteMe Mining?</h2>
    <p class="section-sub">We built this differently. Transparency, fairness, and user-first design are non-negotiable.</p>

    <div class="trust-grid">
        <div class="trust-card">
            <div class="icon"><i class="fas fa-shield-halved"></i></div>
            <h4>Open Source Mining Code</h4>
            <p>Our mining worker is fully inspectable. No hidden processes, no malware, no surprises.</p>
        </div>
        <div class="trust-card">
            <div class="icon"><i class="fas fa-chart-pie"></i></div>
            <h4>80/20 Fair Split</h4>
            <p>You keep 80% of everything you mine. Only 20% goes to the platform — and that funds the entire ecosystem.</p>
        </div>
        <div class="trust-card">
            <div class="icon"><i class="fas fa-bolt"></i></div>
            <h4>Lightweight &lt; 5% CPU</h4>
            <p>Our mining uses minimal resources. No throttling your device, no fans spinning. You won't even notice it's running.</p>
        </div>
        <div class="trust-card">
            <div class="icon"><i class="fas fa-toggle-on"></i></div>
            <h4>Full Control</h4>
            <p>Pause or stop mining anytime with one click. Set your own intensity level. Your device, your rules.</p>
        </div>
    </div>
</section>

<!-- ═══════ EARNINGS CALCULATOR ═══════ -->
<section class="mine-section">
    <h2>Estimate Your Earnings</h2>
    <p class="section-sub">See how much you could earn based on your daily browsing time.</p>

    <div class="calc-wrap">
        <label>Daily Browsing Time</label>
        <input type="range" class="calc-slider" id="calcHours" min="1" max="16" value="4" oninput="updateCalc()">
        <div style="font-size:1.3rem;font-weight:700;color:var(--m-gold);font-family:'JetBrains Mono',monospace"><span id="calcHoursVal">4</span> hours/day</div>

        <div class="calc-result">
            <div class="big" id="calcDaily">—</div>
            <div class="label">GSM per day</div>
            <div style="margin-top:12px;display:flex;justify-content:center;gap:30px">
                <div><div style="font-size:1.2rem;font-weight:700;color:var(--m-green)" id="calcWeekly">—</div><div class="label">per week</div></div>
                <div><div style="font-size:1.2rem;font-weight:700;color:var(--m-cyan)" id="calcMonthly">—</div><div class="label">per month</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════ TOKENOMICS ═══════ -->
<section class="mine-section">
    <h2>GSM Token Distribution</h2>
    <p class="section-sub">1 Billion GSM tokens. Fixed supply. Fair distribution designed for long-term ecosystem health.</p>

    <div class="token-grid">
        <div class="token-item"><div class="pct">30%</div><div class="name">Platform Reserve</div></div>
        <div class="token-item"><div class="pct">25%</div><div class="name">Mining Rewards</div></div>
        <div class="token-item"><div class="pct">20%</div><div class="name">Community</div></div>
        <div class="token-item"><div class="pct">15%</div><div class="name">Development</div></div>
        <div class="token-item"><div class="pct">10%</div><div class="name">DEX Liquidity</div></div>
    </div>
</section>

<!-- ═══════ WHAT YOU CAN DO WITH GSM ═══════ -->
<section class="mine-section">
    <h2>What Can You Do with GSM?</h2>
    <p class="section-sub">GSM is live on Solana mainnet as an SPL token — mine now, withdraw directly to your wallet on-chain. <a href="/blockchain.php" style="color:var(--m-cyan)">View on Solscan →</a></p>

    <div class="how-grid">
        <div class="how-card">
            <div class="icon"><i class="fas fa-file-invoice-dollar" style="color:var(--m-green)"></i></div>
            <h3>Pay for Services</h3>
            <p>Use GSM to pay for hosting, domains, SSL certificates, and any GoSiteMe service — directly from your mining balance.</p>
        </div>
        <div class="how-card">
            <div class="icon"><i class="fas fa-exchange-alt" style="color:var(--m-cyan)"></i></div>
            <h3>Withdraw to Wallet</h3>
            <p>Link your Solana wallet and withdraw GSM tokens on-chain. DEX trading via Jupiter available at launch. <a href="/blockchain.php" style="color:var(--m-cyan)">Learn more</a></p>
        </div>
        <div class="how-card">
            <div class="icon"><i class="fas fa-robot" style="color:var(--m-purple)"></i></div>
            <h3>Power AI Agents</h3>
            <p>Use GSM to deploy Alfred AI agents, run fleet tasks, and access premium intelligence features.</p>
        </div>
    </div>
</section>

<!-- ═══════ FAQ ═══════ -->
<section class="mine-section">
    <h2>Frequently Asked Questions</h2>
    <p class="section-sub">Everything you need to know about mining GSM tokens.</p>

    <div class="faq-list">
        <div class="faq-item">
            <div class="faq-q" onclick="this.parentElement.classList.toggle('open')">
                Will mining slow down my computer?
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="faq-a">No. Our mining worker uses less than 5% of your CPU and is designed to automatically reduce intensity if it detects your system is under load. You can also set a custom intensity level in your wallet settings.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="this.parentElement.classList.toggle('open')">
                Is this safe? Is it malware?
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="faq-a">Absolutely not malware. Our mining code is fully transparent and inspectable. It runs as a standard Web Worker in your browser with your explicit consent. You start it, you stop it, you control it. No background processes, no persistence without permission.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="this.parentElement.classList.toggle('open')">
                How much can I earn?
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="faq-a">Earnings depend on your browsing time and activity. On average, 4 hours of daily browsing earns roughly 200-400 GSM tokens per day. As the token gains value and the ecosystem grows, early miners benefit the most.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="this.parentElement.classList.toggle('open')">
                Why does GoSiteMe keep 20%?
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="faq-a">The 20% platform share funds the entire ecosystem — server infrastructure, AI agents, intelligence systems, research, development, and community programs. It's what keeps the platform free and growing. You keep 80% of every token mined.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="this.parentElement.classList.toggle('open')">
                Can I use GSM to pay my invoices?
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="faq-a">Yes! You can convert your mined GSM directly to billing credits and pay for any GoSiteMe service — hosting, domains, AI agents, and more. Go to your wallet and click "Apply to Balance" to convert your tokens to account credit.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="this.parentElement.classList.toggle('open')">
                Is GSM a real cryptocurrency?
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="faq-a">GSM is an SPL (Solana Program Library) token deployed on Solana mainnet. It has a fixed supply of 1 billion tokens with 8 decimals. You can mine, stake, and use GSM across the platform. On-chain withdrawals are live, and DEX trading on Jupiter plus NFT minting are next — <a href="/blockchain.php" style="color:var(--m-cyan)">track the full smart contract roadmap here</a>.</div>
        </div>
    </div>
</section>

<!-- ═══════ BOTTOM CTA ═══════ -->
<section class="cta-bottom">
    <h2>Ready to Turn Browsing into <span style="color:var(--m-gold)">Earnings</span>?</h2>
    <p>Join thousands of users who are already earning GSM tokens every day — for free, just by using the internet.</p>
    <a href="/search.php" class="mine-cta"><i class="fas fa-rocket"></i> Start Mining Now — It's Free</a>
</section>

<script src="/assets/js/mine-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
