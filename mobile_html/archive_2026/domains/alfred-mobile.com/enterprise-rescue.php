<?php
$page_title = 'Enterprise Rescue Protocol — Bring Your Organization Into MetaDome';
$page_description = 'Dying companies don\'t need consultants. They need to be absorbed into a system that works. The Enterprise Rescue Protocol migrates Fortune 500 organizations into the MetaDome ecosystem.';
$page_canonical = 'https://gositeme.com/enterprise-rescue.php';
include __DIR__ . '/includes/site-header.inc.php';

require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
$agents = $db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
$depts = 12;
?>

<style>
:root {
    --er-bg: #060612;
    --er-surface: #0e0e1a;
    --er-surface-2: #161628;
    --er-border: rgba(0,212,255,0.1);
    --er-cyan: #00d4ff;
    --er-green: #34d399;
    --er-purple: #8b5cf6;
    --er-gold: #fbbf24;
    --er-red: #f87171;
    --er-text: #e8e8f0;
    --er-muted: #6a7a8a;
    --er-radius: 14px;
}
.er-hero {
    position: relative; padding: 5rem 2rem 3rem; text-align: center; overflow: hidden;
    background: radial-gradient(ellipse at 40% 0%, rgba(0,212,255,0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 100%, rgba(139,92,246,0.08) 0%, transparent 50%),
                var(--er-bg);
}
.er-hero::before {
    content: ''; position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cline x1='0' y1='60' x2='60' y2='0' stroke='%2300d4ff' stroke-opacity='0.03' stroke-width='1'/%3E%3C/svg%3E");
}
.er-hero-badge {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.3rem 0.8rem; border-radius: 20px;
    background: rgba(0,212,255,0.08); border: 1px solid rgba(0,212,255,0.2);
    font-size: 0.75rem; color: var(--er-cyan); position: relative; margin-bottom: 1.5rem;
}
.er-hero h1 {
    font-size: 2.8rem; font-weight: 800; letter-spacing: -0.03em; position: relative;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, var(--er-cyan), var(--er-purple));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.er-hero p { font-size: 1.05rem; color: var(--er-muted); max-width: 700px; margin: 0 auto 2rem; position: relative; line-height: 1.7; }
.er-container { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 3rem; }
.er-section { margin-bottom: 3rem; }
.er-section-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.4rem; }
.er-section-desc { font-size: 0.9rem; color: var(--er-muted); margin-bottom: 1.5rem; line-height: 1.6; }

/* ── Intake Form ── */
.er-form {
    background: var(--er-surface); border: 1px solid var(--er-border);
    border-radius: 20px; padding: 2.5rem; max-width: 700px; margin: 0 auto;
}
.er-form h3 { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; }
.er-form p { font-size: 0.85rem; color: var(--er-muted); margin-bottom: 1.5rem; }
.er-field { margin-bottom: 1.25rem; }
.er-field label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.3rem; color: var(--er-text); }
.er-field input, .er-field select, .er-field textarea {
    width: 100%; padding: 0.7rem 1rem; border-radius: 10px;
    border: 1px solid var(--er-border); background: var(--er-surface-2);
    color: var(--er-text); font-size: 0.9rem; font-family: inherit;
}
.er-field input:focus, .er-field select:focus, .er-field textarea:focus {
    outline: none; border-color: var(--er-cyan);
}
.er-field textarea { min-height: 80px; resize: vertical; }
.er-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.er-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.7rem 1.4rem; border-radius: 10px;
    border: none; background: linear-gradient(135deg, var(--er-cyan), var(--er-purple));
    color: #000; font-size: 0.9rem; cursor: pointer;
    font-weight: 700; transition: all 0.2s; text-decoration: none; width: 100%;
    justify-content: center;
}
.er-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,212,255,0.3); }

/* ── ROI Calculator ── */
.er-roi {
    background: var(--er-surface); border: 1px solid var(--er-border);
    border-radius: var(--er-radius); padding: 2rem;
}
.er-roi-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; text-align: center; }
.er-roi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.er-roi-input { }
.er-roi-input label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.3rem; }
.er-roi-input input {
    width: 100%; padding: 0.6rem 0.8rem; border-radius: 8px;
    border: 1px solid var(--er-border); background: var(--er-surface-2);
    color: var(--er-text); font-size: 0.9rem; font-family: 'JetBrains Mono', monospace;
}
.er-roi-results {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-top: 1rem;
}
.er-roi-result {
    background: var(--er-surface-2); border: 1px solid var(--er-border);
    border-radius: 10px; padding: 1rem; text-align: center;
}
.er-roi-val { font-size: 1.3rem; font-weight: 800; font-family: 'JetBrains Mono', monospace; }
.er-roi-val.green { color: var(--er-green); }
.er-roi-val.cyan { color: var(--er-cyan); }
.er-roi-val.gold { color: var(--er-gold); }
.er-roi-val.purple { color: var(--er-purple); }
.er-roi-label { font-size: 0.7rem; color: var(--er-muted); text-transform: uppercase; margin-top: 0.2rem; }

/* ── Case Scenarios ── */
.er-cases { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; }
.er-case {
    background: var(--er-surface); border: 1px solid var(--er-border);
    border-radius: var(--er-radius); padding: 1.5rem; transition: all 0.3s;
}
.er-case:hover { border-color: var(--er-cyan); transform: translateY(-3px); }
.er-case-icon { font-size: 2rem; margin-bottom: 0.5rem; }
.er-case-title { font-size: 1.05rem; font-weight: 700; margin-bottom: 0.2rem; }
.er-case-industry { font-size: 0.75rem; color: var(--er-cyan); font-weight: 600; margin-bottom: 0.5rem; }
.er-case-desc { font-size: 0.82rem; color: var(--er-muted); line-height: 1.6; }
.er-case-stat { margin-top: 0.8rem; padding-top: 0.6rem; border-top: 1px solid var(--er-border); font-size: 0.78rem; }
.er-case-stat strong { color: var(--er-green); }

@media (max-width: 768px) {
    .er-hero h1 { font-size: 2rem; }
    .er-roi-grid, .er-field-row { grid-template-columns: 1fr; }
}
</style>

<section class="er-hero">
    <div class="er-hero-badge">🏢 Enterprise Division — MetaDome Ecosystem</div>
    <h1>Enterprise Rescue Protocol</h1>
    <p>
        52% of Fortune 500 companies from the year 2000 no longer exist. The rest are bleeding.
        They don't need another consultant. They need to be <strong>absorbed into a system that actually works</strong> —
        <?= number_format($agents) ?> AI agents, <?= $depts ?> departments, democratic governance, and a welfare engine that
        protects everyone.
    </p>
</section>

<div class="er-container">

    <!-- ROI Calculator -->
    <section class="er-section">
        <div class="er-section-title">📊 Rescue ROI Calculator</div>
        <div class="er-section-desc">See what happens when your organization stops paying for legacy systems and consultants, and plugs into MetaDome instead.</div>
        <div class="er-roi">
            <div class="er-roi-title">Enter Your Current Costs</div>
            <div class="er-roi-grid">
                <div class="er-roi-input">
                    <label>Annual IT Infrastructure ($)</label>
                    <input type="number" id="roiInfra" value="5000000" oninput="calcROI()">
                </div>
                <div class="er-roi-input">
                    <label>Annual SaaS Licenses ($)</label>
                    <input type="number" id="roiSaas" value="2000000" oninput="calcROI()">
                </div>
                <div class="er-roi-input">
                    <label>Annual Consulting Fees ($)</label>
                    <input type="number" id="roiConsult" value="3000000" oninput="calcROI()">
                </div>
                <div class="er-roi-input">
                    <label>Employee Count</label>
                    <input type="number" id="roiEmployees" value="5000" oninput="calcROI()">
                </div>
            </div>
            <div class="er-roi-results" id="roiResults">
                <div class="er-roi-result"><div class="er-roi-val green" id="roiSaved">$7.5M</div><div class="er-roi-label">Annual Savings</div></div>
                <div class="er-roi-result"><div class="er-roi-val cyan" id="roiAgents">5,000</div><div class="er-roi-label">AI Agents Deployed</div></div>
                <div class="er-roi-result"><div class="er-roi-val gold" id="roiGsm">25,000</div><div class="er-roi-label">GSM Economy Value</div></div>
                <div class="er-roi-result"><div class="er-roi-val purple" id="roiRecoup">8 mo</div><div class="er-roi-label">Time to Full ROI</div></div>
            </div>
        </div>
    </section>

    <!-- Rescue Scenarios -->
    <section class="er-section">
        <div class="er-section-title">🔮 Rescue Scenarios — Who We Can Save</div>
        <div class="er-section-desc">Every dying company has the same disease: hierarchical bureaucracy in a networked world. Here's what rescue looks like across industries.</div>
        <div class="er-cases">
            <div class="er-case">
                <div class="er-case-icon">🏦</div>
                <div class="er-case-title">Legacy Bank Rescue</div>
                <div class="er-case-industry">FINANCIAL SERVICES</div>
                <div class="er-case-desc">Replace COBOL mainframes, 47 overlapping SaaS tools, and 12 layers of compliance officers with ecosystem-native AI agents operating under transparent governance.</div>
                <div class="er-case-stat"><strong>Typical savings:</strong> 60% IT cost reduction + instant post-quantum security upgrade</div>
            </div>
            <div class="er-case">
                <div class="er-case-icon">🏭</div>
                <div class="er-case-title">Manufacturing Revive</div>
                <div class="er-case-industry">INDUSTRIAL / SUPPLY CHAIN</div>
                <div class="er-case-desc">Supply chain chaos solved by AI Operations agents. Inventory managed autonomously. Quality control by Analytics agents. Decision-making by democratic governance, not corner office fiat.</div>
                <div class="er-case-stat"><strong>Typical result:</strong> 40% faster decision cycles + zero supply chain blind spots</div>
            </div>
            <div class="er-case">
                <div class="er-case-icon">🏥</div>
                <div class="er-case-title">Healthcare Transformation</div>
                <div class="er-case-industry">HEALTHCARE / PHARMA</div>
                <div class="er-case-desc">Research agents running drug discovery simulations in MetaDome Labs. Patient data secured under Veil encryption. Compliance automated by Legal agents. Clinical trials governed transparently.</div>
                <div class="er-case-stat"><strong>Typical result:</strong> 70% faster research cycles + HIPAA compliance by architecture</div>
            </div>
            <div class="er-case">
                <div class="er-case-icon">📰</div>
                <div class="er-case-title">Media Company Rebirth</div>
                <div class="er-case-industry">MEDIA / PUBLISHING</div>
                <div class="er-case-desc">Dying publications reborn as MetaDome divisions. Content creation by AI agents governed by editorial committees. Revenue through GSM, not dying ad models. Distribution via the social network.</div>
                <div class="er-case-stat"><strong>Typical result:</strong> New revenue model + content at 100x speed</div>
            </div>
            <div class="er-case">
                <div class="er-case-icon">🛒</div>
                <div class="er-case-title">Retail Chain Revival</div>
                <div class="er-case-industry">RETAIL / E-COMMERCE</div>
                <div class="er-case-desc">Brick-and-mortar chains getting crushed by Amazon? Become a MetaDome division. AI agents handle inventory, customer service, personalization, logistics. Employees protected by welfare engine.</div>
                <div class="er-case-stat"><strong>Typical result:</strong> 50% operational cost reduction + employee safety net</div>
            </div>
            <div class="er-case">
                <div class="er-case-icon">⚖️</div>
                <div class="er-case-title">Law Firm Modernization</div>
                <div class="er-case-industry">LEGAL / PROFESSIONAL SERVICES</div>
                <div class="er-case-desc">Legal research at light speed. Contract analysis by AI. Case management under governance. Billing transparency enforced by architecture. Pro bono work funded by welfare pool.</div>
                <div class="er-case-stat"><strong>Typical result:</strong> 80% research time savings + radical billing transparency</div>
            </div>
        </div>
    </section>

    <!-- Intake Form -->
    <section class="er-section" id="apply">
        <div class="er-form">
            <h3>🏢 Apply for Enterprise Rescue</h3>
            <p>Tell us about your organization. Our Enterprise department will assess eligibility and begin the onboarding protocol. All information encrypted under Veil.</p>
            <form id="rescueForm" onsubmit="return submitRescue(event)">
                <div class="er-field-row">
                    <div class="er-field">
                        <label>Company Name *</label>
                        <input type="text" name="company" required placeholder="Acme Corporation">
                    </div>
                    <div class="er-field">
                        <label>Industry *</label>
                        <select name="industry" required>
                            <option value="">Select industry...</option>
                            <option>Financial Services</option>
                            <option>Healthcare / Pharma</option>
                            <option>Manufacturing</option>
                            <option>Retail / E-Commerce</option>
                            <option>Media / Publishing</option>
                            <option>Legal / Professional Services</option>
                            <option>Technology</option>
                            <option>Energy / Utilities</option>
                            <option>Real Estate</option>
                            <option>Education</option>
                            <option>Government</option>
                            <option>Transportation / Logistics</option>
                            <option>Other</option>
                        </select>
                    </div>
                </div>
                <div class="er-field-row">
                    <div class="er-field">
                        <label>Employee Count *</label>
                        <select name="size" required>
                            <option value="">Select size...</option>
                            <option>10-50</option>
                            <option>50-200</option>
                            <option>200-1,000</option>
                            <option>1,000-5,000</option>
                            <option>5,000-50,000</option>
                            <option>50,000+</option>
                        </select>
                    </div>
                    <div class="er-field">
                        <label>Annual Revenue *</label>
                        <select name="revenue" required>
                            <option value="">Select range...</option>
                            <option>Under $1M</option>
                            <option>$1M - $10M</option>
                            <option>$10M - $100M</option>
                            <option>$100M - $1B</option>
                            <option>$1B - $10B</option>
                            <option>$10B+</option>
                        </select>
                    </div>
                </div>
                <div class="er-field-row">
                    <div class="er-field">
                        <label>Contact Name *</label>
                        <input type="text" name="contact" required placeholder="John Smith">
                    </div>
                    <div class="er-field">
                        <label>Email *</label>
                        <input type="email" name="email" required placeholder="john@company.com">
                    </div>
                </div>
                <div class="er-field">
                    <label>What's killing your company? *</label>
                    <textarea name="pain_points" required placeholder="Describe your biggest challenges — legacy systems, bureaucracy, competition, talent loss, innovation paralysis..."></textarea>
                </div>
                <div class="er-field">
                    <label>What have you tried that didn't work?</label>
                    <textarea name="failed_attempts" placeholder="Consultants, digital transformation initiatives, restructuring, layoffs..."></textarea>
                </div>
                <button type="submit" class="er-btn">🚀 Request Rescue Assessment</button>
            </form>
            <div id="rescueSuccess" style="display:none;text-align:center;padding:2rem;">
                <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
                <div style="font-size:1.2rem;font-weight:700;margin-bottom:0.5rem;">Application Received</div>
                <div style="color:var(--er-muted);font-size:0.9rem;">Our Enterprise department will review your case and respond within 48 hours. Your data is encrypted under Veil.</div>
            </div>
        </div>
    </section>

</div>

<script src="/assets/js/enterprise-rescue-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
