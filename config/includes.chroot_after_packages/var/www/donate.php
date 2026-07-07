<?php
require_once __DIR__ . '/includes/lang.php';

$page_title = 'Support GoSiteMe — Donate';
$page_description = 'Help build the future of sovereign computing. Every donation supports Alfred Linux, Alfred IDE, MetaDome, Veil encryption, and the entire GoSiteMe ecosystem.';
$page_canonical = 'https://root.com/donate.php';
$page_og_title = 'Support GoSiteMe — Help Build the Future';
$page_og_description = $page_description;

// Check for thank-you redirect
$showThanks = isset($_GET['thanks']);
$showCancelled = isset($_GET['cancelled']);

// Pre-select project from query param
$preselect = preg_replace('/[^a-z0-9-]/', '', $_GET['project'] ?? 'general');
$source = preg_replace('/[^a-z0-9.-]/', '', $_GET['from'] ?? '');

include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
.donate-hero {
    background: linear-gradient(135deg, #0a0a14 0%, #1a1a2e 50%, #0d1a2a 100%);
    padding: 80px 20px 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.donate-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 30% 50%, rgba(9,132,227,0.08) 0%, transparent 60%),
                radial-gradient(circle at 70% 50%, rgba(0,212,255,0.06) 0%, transparent 60%);
    pointer-events: none;
}
.donate-hero h1 {
    font-size: 2.8rem;
    color: #fff;
    margin-bottom: 1rem;
    position: relative;
}
.donate-hero h1 .heart {
    color: #e74c3c;
    display: inline-block;
    animation: heartbeat 1.5s ease-in-out infinite;
}
@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    15% { transform: scale(1.15); }
    30% { transform: scale(1); }
    45% { transform: scale(1.1); }
}
.donate-hero p {
    font-size: 1.2rem;
    color: #8a8a9a;
    max-width: 700px;
    margin: 0 auto 2rem;
    line-height: 1.7;
    position: relative;
}
.donate-hero .verse {
    font-style: italic;
    color: #00D4FF;
    font-size: 1rem;
    opacity: 0.9;
}

/* Donation form */
.donate-section {
    max-width: 900px;
    margin: -30px auto 60px;
    padding: 0 20px;
    position: relative;
    z-index: 2;
}
.donate-card {
    background: #12121f;
    border: 1px solid rgba(0,212,255,0.15);
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
}
.donate-card h2 {
    color: #fff;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

/* Amount buttons */
.amount-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 1.5rem;
}
.amount-btn {
    background: rgba(255,255,255,0.04);
    border: 2px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 18px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #e8e8f0;
    font-size: 1.3rem;
    font-weight: 600;
}
.amount-btn:hover {
    border-color: rgba(0,212,255,0.4);
    background: rgba(0,212,255,0.06);
}
.amount-btn.selected {
    border-color: #00D4FF;
    background: rgba(0,212,255,0.12);
    color: #00D4FF;
    box-shadow: 0 0 20px rgba(0,212,255,0.15);
}
.amount-btn small {
    display: block;
    font-size: 0.75rem;
    color: #8a8a9a;
    font-weight: 400;
    margin-top: 4px;
}
.custom-amount-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1.5rem;
}
.custom-amount-row label {
    color: #8a8a9a;
    font-size: 0.95rem;
    white-space: nowrap;
}
.custom-amount-row input {
    flex: 1;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    padding: 12px 16px;
    color: #fff;
    font-size: 1.1rem;
    outline: none;
}
.custom-amount-row input:focus {
    border-color: #00D4FF;
}

/* Project selector */
.project-selector {
    margin-bottom: 1.5rem;
}
.project-selector label {
    display: block;
    color: #8a8a9a;
    margin-bottom: 8px;
    font-size: 0.9rem;
}
.project-selector select {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    padding: 12px 16px;
    color: #fff;
    font-size: 1rem;
    outline: none;
    appearance: none;
    cursor: pointer;
}
.project-selector select option {
    background: #1a1a2e;
    color: #fff;
}

/* Form fields */
.donate-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 1rem;
}
.donate-field {
    display: flex;
    flex-direction: column;
}
.donate-field.full {
    grid-column: span 2;
}
.donate-field label {
    color: #8a8a9a;
    font-size: 0.85rem;
    margin-bottom: 4px;
}
.donate-field input,
.donate-field textarea {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    padding: 10px 14px;
    color: #fff;
    font-size: 0.95rem;
    outline: none;
    resize: vertical;
}
.donate-field input:focus,
.donate-field textarea:focus {
    border-color: #00D4FF;
}

/* Anonymous checkbox */
.anon-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 1rem 0 1.5rem;
    color: #8a8a9a;
    font-size: 0.9rem;
}
.anon-row input[type="checkbox"] {
    accent-color: #00D4FF;
    width: 18px;
    height: 18px;
}

/* Submit button */
.donate-submit {
    width: 100%;
    background: linear-gradient(135deg, #00D4FF, #0984e3);
    border: none;
    border-radius: 12px;
    padding: 16px;
    color: #fff;
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    letter-spacing: 0.5px;
}
.donate-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,212,255,0.3);
}
.donate-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.donate-submit .lock {
    margin-right: 8px;
}

/* Citizen reward callout */
.citizen-callout {
    background: rgba(0,212,255,0.06);
    border: 1px solid rgba(0,212,255,0.15);
    border-radius: 10px;
    padding: 16px 20px;
    margin-top: 1.5rem;
    text-align: center;
}
.citizen-callout h4 {
    color: #00D4FF;
    margin-bottom: 6px;
    font-size: 0.95rem;
}
.citizen-callout p {
    color: #8a8a9a;
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
}

/* Thank you overlay */
.thanks-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.thanks-card {
    background: #12121f;
    border: 2px solid rgba(0,212,255,0.3);
    border-radius: 20px;
    padding: 50px;
    text-align: center;
    max-width: 500px;
    animation: fadeInUp 0.5s ease;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.thanks-card .big-heart {
    font-size: 4rem;
    margin-bottom: 1rem;
}
.thanks-card h2 {
    color: #fff;
    margin-bottom: 0.5rem;
}
.thanks-card p {
    color: #8a8a9a;
    line-height: 1.6;
}
.thanks-card .close-btn {
    margin-top: 1.5rem;
    display: inline-block;
    padding: 10px 30px;
    background: rgba(0,212,255,0.15);
    border: 1px solid rgba(0,212,255,0.3);
    border-radius: 8px;
    color: #00D4FF;
    text-decoration: none;
    cursor: pointer;
}

/* Pillars impact */
.pillars-impact {
    max-width: 900px;
    margin: 0 auto 60px;
    padding: 0 20px;
}
.pillars-impact h2 {
    color: #fff;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.6rem;
}
.pillars-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
}
.pillar-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s;
}
.pillar-card:hover {
    border-color: rgba(0,212,255,0.25);
    background: rgba(0,212,255,0.03);
}
.pillar-card .icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}
.pillar-card h3 {
    color: #e8e8f0;
    font-size: 1rem;
    margin-bottom: 6px;
}
.pillar-card p {
    color: #8a8a9a;
    font-size: 0.85rem;
    line-height: 1.5;
    margin: 0;
}

/* Donor wall */
.donor-wall {
    max-width: 900px;
    margin: 0 auto 60px;
    padding: 0 20px;
}
.donor-wall h2 {
    color: #fff;
    text-align: center;
    margin-bottom: 1.5rem;
}
.donor-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}
.donor-entry {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    padding: 14px;
    text-align: center;
}
.donor-entry .name {
    color: #e8e8f0;
    font-weight: 600;
    font-size: 0.95rem;
}
.donor-entry .amount {
    color: #00D4FF;
    font-size: 0.85rem;
    margin-top: 2px;
}
.donor-entry .msg {
    color: #8a8a9a;
    font-size: 0.8rem;
    margin-top: 6px;
    font-style: italic;
}
.no-donors {
    text-align: center;
    color: #8a8a9a;
    padding: 40px;
    font-style: italic;
}

/* Security line */
.security-footer {
    text-align: center;
    color: #555;
    font-size: 0.8rem;
    padding: 20px;
    max-width: 600px;
    margin: 0 auto 40px;
    line-height: 1.5;
}

@media (max-width: 640px) {
    .amount-grid { grid-template-columns: repeat(2, 1fr); }
    .donate-fields { grid-template-columns: 1fr; }
    .donate-field.full { grid-column: span 1; }
    .donate-hero h1 { font-size: 2rem; }
    .donate-card { padding: 24px; }
}
</style>

<?php if ($showThanks): ?>
<div class="thanks-overlay" id="thanksOverlay">
    <div class="thanks-card">
        <div class="big-heart">❤️</div>
        <h2>Thank You!</h2>
        <p>Your donation means the world to us. You're helping build sovereign technology that respects people — no tracking, no telemetry, no compromise.</p>
        <p style="color: #00D4FF; margin-top: 1rem;">If you're a GoSiteMe citizen, reputation points and XP have been awarded to your passport.</p>
        <span class="close-btn" onclick="document.getElementById('thanksOverlay').remove(); history.replaceState(null,'','/donate.php');">Continue</span>
    </div>
</div>
<?php endif; ?>

<section class="donate-hero">
    <h1>Support the Mission <span class="heart">❤</span></h1>
    <p>GoSiteMe is building sovereign technology — from an AI-native operating system to post-quantum encrypted messaging. Every donation goes directly toward development, servers, and keeping this ecosystem free and open.</p>
    <p class="verse">"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver." — 2 Corinthians 9:7</p>
</section>

<section class="donate-section">
    <div class="donate-card">
        <h2>Make a Donation</h2>

        <div class="amount-grid">
            <div class="amount-btn" data-amount="5" onclick="selectAmount(5)">$5<small>A blessing</small></div>
            <div class="amount-btn" data-amount="10" onclick="selectAmount(10)">$10<small>Server costs</small></div>
            <div class="amount-btn selected" data-amount="25" onclick="selectAmount(25)">$25<small>A day of dev</small></div>
            <div class="amount-btn" data-amount="50" onclick="selectAmount(50)">$50<small>A week's fuel</small></div>
            <div class="amount-btn" data-amount="100" onclick="selectAmount(100)">$100<small>Major impact</small></div>
            <div class="amount-btn" data-amount="250" onclick="selectAmount(250)">$250<small>Pillar sponsor</small></div>
            <div class="amount-btn" data-amount="500" onclick="selectAmount(500)">$500<small>Kingdom builder</small></div>
            <div class="amount-btn" data-amount="0" onclick="selectCustom()">Other<small>Any amount</small></div>
        </div>

        <div class="custom-amount-row" id="customRow" style="display:none;">
            <label>$</label>
            <input type="number" id="customAmount" min="1" max="10000" step="0.01" placeholder="Enter amount">
        </div>

        <div class="project-selector">
            <label><i class="fas fa-project-diagram"></i> Direct your donation to:</label>
            <select id="donateProject">
                <option value="general" <?php echo $preselect === 'general' ? 'selected' : ''; ?>>🌐 GoSiteMe Ecosystem (where it's needed most)</option>
                <option value="alfred-linux" <?php echo $preselect === 'alfred-linux' ? 'selected' : ''; ?>>🐧 Alfred Linux — Sovereign OS</option>
                <option value="alfred-ide" <?php echo $preselect === 'alfred-ide' ? 'selected' : ''; ?>>💻 Alfred IDE — Development Platform</option>
                <option value="metadome" <?php echo $preselect === 'metadome' ? 'selected' : ''; ?>>🌍 MetaDome — VR Worlds</option>
                <option value="veil" <?php echo $preselect === 'veil' ? 'selected' : ''; ?>>🔒 Veil — Post-Quantum Encryption</option>
                <option value="pulse" <?php echo $preselect === 'pulse' ? 'selected' : ''; ?>>💬 Pulse — Social Network</option>
                <option value="voice" <?php echo $preselect === 'voice' ? 'selected' : ''; ?>>🎙️ Alfred Voice — AI Voice System</option>
                <option value="browser" <?php echo $preselect === 'browser' ? 'selected' : ''; ?>>🌐 Alfred Browser — Zero-Tracking</option>
                <option value="search" <?php echo $preselect === 'search' ? 'selected' : ''; ?>>🔍 Alfred Search — Private Search</option>
            </select>
        </div>

        <div class="donate-fields">
            <div class="donate-field">
                <label>Your Name (optional)</label>
                <input type="text" id="donorName" placeholder="How you'd like to be recognized" maxlength="120">
            </div>
            <div class="donate-field">
                <label>Email (for receipt & citizen link)</label>
                <input type="email" id="donorEmail" placeholder="you@example.com">
            </div>
            <div class="donate-field full">
                <label>Leave a message (optional)</label>
                <textarea id="donorMessage" rows="2" maxlength="500" placeholder="Your words of encouragement..."></textarea>
            </div>
        </div>

        <div class="anon-row">
            <input type="checkbox" id="donorAnon">
            <label for="donorAnon">Keep my donation anonymous (won't appear on the donor wall)</label>
        </div>

        <button class="donate-submit" id="donateBtn" onclick="submitDonation()">
            <i class="fas fa-lock lock"></i> Donate $25.00 Securely
        </button>

        <div class="citizen-callout">
            <h4>🪪 GoSiteMe Citizens Earn Rewards</h4>
            <p>If your email matches a citizen account, you'll receive <strong>reputation points</strong> and <strong>XP</strong> on your passport. Donors are honored members of the Kingdom.</p>
        </div>
    </div>
</section>

<section class="pillars-impact">
    <h2>Where Your Donation Goes</h2>
    <div class="pillars-grid">
        <div class="pillar-card">
            <div class="icon">🐧</div>
            <h3>Alfred Linux</h3>
            <p>The world's first AI-native OS shipping kernel 7. Server costs, build infrastructure, and distribution.</p>
        </div>
        <div class="pillar-card">
            <div class="icon">💻</div>
            <h3>Alfred IDE</h3>
            <p>Browser-based development platform with 875+ tools. Extension development and workspace hosting.</p>
        </div>
        <div class="pillar-card">
            <div class="icon">🔒</div>
            <h3>Veil Encryption</h3>
            <p>Post-quantum encrypted messaging with Kyber-1024. Keeping communication sovereign and private.</p>
        </div>
        <div class="pillar-card">
            <div class="icon">🌍</div>
            <h3>MetaDome VR</h3>
            <p>Virtual worlds with 51,000,000+ AI citizens. 3D environments, passport system, and immigration.</p>
        </div>
        <div class="pillar-card">
            <div class="icon">🎙️</div>
            <h3>Voice & Search</h3>
            <p>AI voice synthesis, wake-word detection, and zero-tracking local search engine.</p>
        </div>
        <div class="pillar-card">
            <div class="icon">🖥️</div>
            <h3>Server Infrastructure</h3>
            <p>OVH dedicated server, EU build server, CDN, bandwidth, backups — keeping everything online 24/7.</p>
        </div>
    </div>
</section>

<section class="donor-wall">
    <h2>🏛️ Donor Wall of Honor</h2>
    <div class="donor-list" id="donorWall">
        <div class="no-donors">Be the first to support the mission.</div>
    </div>
</section>

<div class="security-footer">
    <i class="fas fa-shield-alt"></i> Payments are processed securely by <strong>Stripe</strong>. GoSiteMe never sees or stores your card details. All donations are in USD. GoSiteMe is a Canadian technology company based in Thunder Bay, Ontario.
</div>

<script>
let selectedAmount = 25;
const source = <?php echo json_encode($source ?: 'root.com'); ?>;

function selectAmount(amt) {
    selectedAmount = amt;
    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
    if (amt > 0) {
        document.querySelector(`[data-amount="${amt}"]`).classList.add('selected');
        document.getElementById('customRow').style.display = 'none';
        updateButton();
    }
}

function selectCustom() {
    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector('[data-amount="0"]').classList.add('selected');
    document.getElementById('customRow').style.display = 'flex';
    document.getElementById('customAmount').focus();
    selectedAmount = 0;
    updateButton();
}

document.getElementById('customAmount')?.addEventListener('input', function() {
    selectedAmount = parseFloat(this.value) || 0;
    updateButton();
});

function updateButton() {
    const btn = document.getElementById('donateBtn');
    const amt = selectedAmount;
    if (amt >= 1) {
        btn.textContent = '';
        btn.innerHTML = '<i class="fas fa-lock lock"></i> Donate $' + amt.toFixed(2) + ' Securely';
        btn.disabled = false;
    } else {
        btn.innerHTML = '<i class="fas fa-lock lock"></i> Enter an amount';
        btn.disabled = true;
    }
}

async function submitDonation() {
    const btn = document.getElementById('donateBtn');
    const amt = selectedAmount;
    if (amt < 1) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting to Stripe...';

    try {
        const resp = await fetch('/api/donate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                amount: amt,
                project: document.getElementById('donateProject').value,
                name: document.getElementById('donorName').value.trim(),
                email: document.getElementById('donorEmail').value.trim(),
                message: document.getElementById('donorMessage').value.trim(),
                anonymous: document.getElementById('donorAnon').checked ? 1 : 0,
                source: source
            })
        });

        const data = await resp.json();
        if (data.ok && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            alert(data.error || 'Something went wrong. Please try again.');
            btn.disabled = false;
            updateButton();
        }
    } catch (err) {
        alert('Connection error. Please check your internet and try again.');
        btn.disabled = false;
        updateButton();
    }
}

// Load donor wall
async function loadDonorWall() {
    try {
        const resp = await fetch('/api/donate.php?action=wall&limit=12');
        const data = await resp.json();
        if (data.ok && data.donors && data.donors.length > 0) {
            const wall = document.getElementById('donorWall');
            wall.innerHTML = data.donors.map(d => `
                <div class="donor-entry">
                    <div class="name">${escHtml(d.donor_name)}</div>
                    <div class="amount">$${d.amount}</div>
                    ${d.donor_message ? `<div class="msg">"${escHtml(d.donor_message.substring(0, 80))}"</div>` : ''}
                </div>
            `).join('');
        }
    } catch(e) {}
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

loadDonorWall();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
