<?php
$page_title = 'Invest in GoSiteMe — AI Platform Powering the Next Generation of Business';
$page_description = 'Join GoSiteMe\'s growth journey. 1,220+ AI tools, 89 categories, voice AI, and a full hosting platform. Early-stage investment opportunity with transparent returns.';
$page_canonical = 'https://gositeme.com/invest';
$page_og_title = 'Invest in GoSiteMe — Early Stage AI Platform Opportunity';
$page_og_description = 'GoSiteMe is building Canada\'s most comprehensive AI platform. 1,220+ tools, voice AI, 35 industries. See our traction and invest.';
include __DIR__ . '/includes/site-header.inc.php';
?>

<!-- Schema.org FAQPage Markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {"@type":"Question","name":"What is GoSiteMe?","acceptedAnswer":{"@type":"Answer","text":"GoSiteMe is Canada's most comprehensive AI platform, offering 1,220+ tools across 89 categories, voice AI, hosting, and developer APIs."}},
    {"@type":"Question","name":"How do I invest in GoSiteMe?","acceptedAnswer":{"@type":"Answer","text":"You can invest by selecting a tier on our invest page and filling out the contact form. We offer Seed, Growth, and Strategic tiers with SAFE agreements."}},
    {"@type":"Question","name":"What is the minimum investment?","acceptedAnswer":{"@type":"Answer","text":"Our Seed tier starts at $1,000, making it accessible for early believers in our platform."}},
    {"@type":"Question","name":"Is GoSiteMe a registered corporation?","acceptedAnswer":{"@type":"Answer","text":"Yes. GoSiteMe is a registered Canadian corporation operating from Ontario, Canada."}},
    {"@type":"Question","name":"What is a SAFE agreement?","acceptedAnswer":{"@type":"Answer","text":"A Simple Agreement for Future Equity (SAFE) is a contract that gives investors the right to equity in a future financing round. Used by Y Combinator-backed startups worldwide."}},
    {"@type":"Question","name":"How will my investment be used?","acceptedAnswer":{"@type":"Answer","text":"Funds are allocated across engineering (40%), infrastructure (20%), marketing (15%), legal (10%), operations (10%), and reserve (5%)."}},
    {"@type":"Question","name":"What returns can I expect?","acceptedAnswer":{"@type":"Answer","text":"While returns are never guaranteed, our 8 revenue streams and growing platform position us for significant growth. We provide monthly transparent updates on all metrics."}},
    {"@type":"Question","name":"Can I get a refund?","acceptedAnswer":{"@type":"Answer","text":"We offer a 30-day cooling-off period for investments under $10,000. After that, investments are subject to the terms of your SAFE agreement."}}
  ]
}
</script>

<style>
/* ═══════════════════════════════════════════════════════════════
   INVEST PAGE — GoSiteMe Enterprise Investor Portal
   Dark theme, premium aesthetic, Y Combinator quality
   ═══════════════════════════════════════════════════════════════ */

@import url('/assets/css/fonts.css');

:root {
  --inv-bg: #0a0a14;
  --inv-bg-alt: #0f0f1e;
  --inv-bg-card: #12122a;
  --inv-bg-card-hover: #1a1a3e;
  --inv-border: rgba(255,255,255,0.06);
  --inv-border-hover: rgba(0,184,148,0.3);
  --inv-text: #e8e8f0;
  --inv-text-muted: #8888a8;
  --inv-text-dim: #555578;
  --inv-green: #00b894;
  --inv-green-light: #55efc4;
  --inv-blue: #0984e3;
  --inv-purple: #6c5ce7;
  --inv-gradient: linear-gradient(135deg, #00b894, #0984e3, #6c5ce7);
  --inv-gradient-text: linear-gradient(135deg, #55efc4, #74b9ff, #a29bfe);
  --inv-radius: 16px;
  --inv-radius-sm: 10px;
  --inv-shadow: 0 8px 32px rgba(0,0,0,0.4);
  --inv-shadow-glow: 0 0 40px rgba(0,184,148,0.15);
  --inv-font-display: 'Space Grotesk', sans-serif;
  --inv-font-body: 'Inter', sans-serif;
  --inv-max-width: 1280px;
}

/* ── Base Resets ── */
.inv-page { background: var(--inv-bg); color: var(--inv-text); font-family: var(--inv-font-body); overflow-x: hidden; }
.inv-page *, .inv-page *::before, .inv-page *::after { box-sizing: border-box; }
.inv-container { max-width: var(--inv-max-width); margin: 0 auto; padding: 0 24px; }
.inv-page a { color: var(--inv-green-light); text-decoration: none; transition: color 0.3s; }
.inv-page a:hover { color: #fff; }

/* ── Section Spacing ── */
.inv-section { padding: 100px 0; position: relative; }
.inv-section-alt { background: var(--inv-bg-alt); }
.inv-section-title { font-family: var(--inv-font-display); font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; text-align: center; margin-bottom: 16px; color: #fff; }
.inv-section-subtitle { text-align: center; color: var(--inv-text-muted); font-size: 1.125rem; max-width: 640px; margin: 0 auto 60px; line-height: 1.7; }

/* ── Gradient Text ── */
.inv-gradient-text { background: var(--inv-gradient-text); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

/* ═══════════════════════════════════════════════════════════════
   1. HERO
   ═══════════════════════════════════════════════════════════════ */
.inv-hero {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  padding: 140px 24px 80px;
}
.inv-hero-bg {
  position: absolute; inset: 0; z-index: 0;
  background: radial-gradient(ellipse 80% 60% at 50% 20%, rgba(0,184,148,0.12) 0%, transparent 60%),
              radial-gradient(ellipse 60% 50% at 80% 80%, rgba(108,92,231,0.1) 0%, transparent 50%),
              radial-gradient(ellipse 50% 40% at 10% 70%, rgba(9,132,227,0.08) 0%, transparent 50%),
              var(--inv-bg);
}
/* Animated particles */
.inv-particles { position: absolute; inset: 0; z-index: 1; overflow: hidden; }
.inv-particle {
  position: absolute;
  width: 3px; height: 3px;
  background: var(--inv-green);
  border-radius: 50%;
  opacity: 0;
  animation: inv-float 8s ease-in-out infinite;
}
.inv-particle:nth-child(1) { left:10%; top:20%; animation-delay:0s; animation-duration:7s; }
.inv-particle:nth-child(2) { left:25%; top:60%; animation-delay:1s; animation-duration:9s; }
.inv-particle:nth-child(3) { left:50%; top:30%; animation-delay:2s; animation-duration:6s; }
.inv-particle:nth-child(4) { left:70%; top:70%; animation-delay:3s; animation-duration:8s; }
.inv-particle:nth-child(5) { left:85%; top:15%; animation-delay:0.5s; animation-duration:10s; }
.inv-particle:nth-child(6) { left:40%; top:80%; animation-delay:1.5s; animation-duration:7s; }
.inv-particle:nth-child(7) { left:60%; top:45%; animation-delay:2.5s; animation-duration:9s; }
.inv-particle:nth-child(8) { left:15%; top:50%; animation-delay:3.5s; animation-duration:6s; }
.inv-particle:nth-child(9) { left:90%; top:40%; animation-delay:4s; animation-duration:8s; }
.inv-particle:nth-child(10){ left:35%; top:10%; animation-delay:0.8s; animation-duration:11s; }

@keyframes inv-float {
  0%   { opacity:0; transform: translateY(0) scale(1); }
  20%  { opacity:0.6; }
  50%  { opacity:0.3; transform: translateY(-120px) scale(1.5); }
  80%  { opacity:0.5; }
  100% { opacity:0; transform: translateY(-250px) scale(0.5); }
}

.inv-hero-content { position: relative; z-index: 2; text-align: center; max-width: 860px; }

/* Pulse Badge */
.inv-pulse-badge {
  display: inline-flex; align-items: center; gap: 10px;
  background: rgba(0,184,148,0.1); border: 1px solid rgba(0,184,148,0.3);
  padding: 10px 24px; border-radius: 50px; margin-bottom: 32px;
  font-size: 0.9rem; font-weight: 500; color: var(--inv-green-light);
  animation: inv-badge-pulse 3s ease-in-out infinite;
}
.inv-pulse-dot {
  width: 10px; height: 10px; background: var(--inv-green);
  border-radius: 50%; position: relative;
}
.inv-pulse-dot::after {
  content:''; position: absolute; inset:-4px;
  border-radius: 50%; border: 2px solid var(--inv-green);
  animation: inv-ping 2s ease-out infinite;
}
@keyframes inv-ping { 0%{opacity:1;transform:scale(1)} 100%{opacity:0;transform:scale(2)} }
@keyframes inv-badge-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(0,184,148,0.2)} 50%{box-shadow:0 0 20px 4px rgba(0,184,148,0.15)} }

.inv-hero h1 {
  font-family: var(--inv-font-display);
  font-size: clamp(2.8rem, 6vw, 4.8rem);
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 24px;
  letter-spacing: -0.03em;
}
.inv-hero-sub {
  font-size: clamp(1.05rem, 2vw, 1.3rem);
  color: var(--inv-text-muted);
  line-height: 1.7;
  margin-bottom: 40px;
  max-width: 680px;
  margin-left: auto; margin-right: auto;
}
.inv-hero-ctas { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 48px; }

.inv-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 16px 36px; border-radius: 12px;
  font-family: var(--inv-font-display);
  font-size: 1.05rem; font-weight: 600;
  border: none; cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
}
.inv-btn-primary {
  background: var(--inv-gradient);
  color: #fff;
  box-shadow: 0 4px 24px rgba(0,184,148,0.3);
}
.inv-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(0,184,148,0.4);
  color: #fff;
}
.inv-btn-secondary {
  background: rgba(255,255,255,0.06);
  color: var(--inv-text);
  border: 1px solid var(--inv-border);
}
.inv-btn-secondary:hover {
  background: rgba(255,255,255,0.1);
  border-color: var(--inv-border-hover);
  color: #fff;
}

/* Live Counter */
.inv-live-counter {
  display: flex; gap: 40px; justify-content: center; flex-wrap: wrap;
  padding: 24px 0; margin-bottom: 40px;
}
.inv-live-counter-item { text-align: center; }
.inv-live-counter-value {
  font-family: var(--inv-font-display); font-size: 2rem; font-weight: 700; color: #fff;
}
.inv-live-counter-label { font-size: 0.85rem; color: var(--inv-text-muted); margin-top: 4px; }

/* Trust Strip */
.inv-trust-strip {
  display: flex; align-items: center; gap: 32px;
  justify-content: center; flex-wrap: wrap;
  padding-top: 40px; border-top: 1px solid var(--inv-border);
}
.inv-trust-strip-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: var(--inv-text-dim); }
.inv-trust-logos { display: flex; gap: 28px; flex-wrap: wrap; align-items: center; }
.inv-trust-logo {
  font-family: var(--inv-font-display); font-size: 0.95rem; font-weight: 600;
  color: var(--inv-text-dim); opacity: 0.5; transition: opacity 0.3s;
}
.inv-trust-logo:hover { opacity: 0.8; }

/* ═══════════════════════════════════════════════════════════════
   2. LIVE STATS BAR
   ═══════════════════════════════════════════════════════════════ */
.inv-stats-bar {
  background: linear-gradient(135deg, rgba(0,184,148,0.08), rgba(108,92,231,0.08));
  border-top: 1px solid var(--inv-border); border-bottom: 1px solid var(--inv-border);
  padding: 48px 0;
}
.inv-stats-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 20px;
}
@media (max-width: 1024px) { .inv-stats-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 640px) { .inv-stats-grid { grid-template-columns: repeat(2, 1fr); } }
.inv-stat-item { text-align: center; padding: 16px 8px; }
.inv-stat-value {
  font-family: var(--inv-font-display);
  font-size: 2.2rem; font-weight: 700; color: #fff;
}
.inv-stat-value .inv-count { display: inline-block; }
.inv-stat-label { font-size: 0.8rem; color: var(--inv-text-muted); margin-top: 6px; text-transform: uppercase; letter-spacing: 1px; }
.inv-stat-icon { font-size: 1.6rem; margin-bottom: 8px; }

/* ═══════════════════════════════════════════════════════════════
   3. WHY INVEST NOW (Market Opportunity)
   ═══════════════════════════════════════════════════════════════ */
.inv-tam-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
  margin-bottom: 64px;
}
.inv-tam-card {
  text-align: center; padding: 40px 24px;
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius); transition: all 0.3s;
}
.inv-tam-card:hover { border-color: var(--inv-border-hover); transform: translateY(-4px); box-shadow: var(--inv-shadow-glow); }
.inv-tam-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: var(--inv-text-muted); margin-bottom: 12px; }
.inv-tam-value { font-family: var(--inv-font-display); font-size: 3rem; font-weight: 700; margin-bottom: 8px; }
.inv-tam-desc { font-size: 0.9rem; color: var(--inv-text-muted); line-height: 1.6; }

.inv-ps-grid {
  display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;
}
.inv-ps-col-title {
  font-family: var(--inv-font-display); font-size: 1.3rem; font-weight: 600;
  margin-bottom: 24px; padding-bottom: 12px;
  border-bottom: 2px solid;
}
.inv-ps-col-title.problem { border-color: #e74c3c; color: #e74c3c; }
.inv-ps-col-title.solution { border-color: var(--inv-green); color: var(--inv-green); }

.inv-ps-card {
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius-sm); padding: 24px;
  margin-bottom: 16px; transition: all 0.3s;
}
.inv-ps-card:hover { border-color: var(--inv-border-hover); }
.inv-ps-card h4 { font-family: var(--inv-font-display); font-size: 1.05rem; margin-bottom: 8px; color: #fff; }
.inv-ps-card p { font-size: 0.9rem; color: var(--inv-text-muted); line-height: 1.6; margin: 0; }
.inv-ps-icon { font-size: 1.5rem; margin-bottom: 12px; }

/* ═══════════════════════════════════════════════════════════════
   4. TRACTION DASHBOARD
   ═══════════════════════════════════════════════════════════════ */
.inv-traction-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(0,184,148,0.08); border: 1px solid rgba(0,184,148,0.2);
  padding: 8px 20px; border-radius: 50px; margin: 0 auto 40px; display: flex; justify-content: center; width: fit-content;
  font-size: 0.85rem; color: var(--inv-green-light);
}
.inv-traction-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
}
@media (max-width: 900px) { .inv-traction-grid { grid-template-columns: repeat(2, 1fr); } }
.inv-traction-card {
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius); padding: 28px; text-align: center;
  transition: all 0.3s;
}
.inv-traction-card:hover { border-color: var(--inv-border-hover); transform: translateY(-3px); box-shadow: var(--inv-shadow-glow); }
.inv-traction-icon { font-size: 2rem; margin-bottom: 12px; }
.inv-traction-value { font-family: var(--inv-font-display); font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
.inv-traction-label { font-size: 0.85rem; color: var(--inv-text-muted); margin-bottom: 16px; }
.inv-progress-bar { height: 4px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden; }
.inv-progress-fill { height: 100%; background: var(--inv-gradient); border-radius: 4px; transition: width 1.5s ease; width: 0; }

/* ═══════════════════════════════════════════════════════════════
   5. REVENUE MODEL
   ═══════════════════════════════════════════════════════════════ */
.inv-revenue-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
  margin-bottom: 48px;
}
@media (max-width: 900px) { .inv-revenue-grid { grid-template-columns: repeat(2, 1fr); } }
.inv-revenue-card {
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius); padding: 28px;
  transition: all 0.3s; position: relative; overflow: hidden;
}
.inv-revenue-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: var(--inv-gradient); opacity: 0; transition: opacity 0.3s;
}
.inv-revenue-card:hover { border-color: var(--inv-border-hover); transform: translateY(-3px); }
.inv-revenue-card:hover::before { opacity: 1; }
.inv-revenue-icon { font-size: 1.8rem; margin-bottom: 16px; }
.inv-revenue-name { font-family: var(--inv-font-display); font-size: 1.1rem; font-weight: 600; color: #fff; margin-bottom: 8px; }
.inv-revenue-desc { font-size: 0.85rem; color: var(--inv-text-muted); line-height: 1.6; margin-bottom: 16px; }
.inv-revenue-est {
  font-family: var(--inv-font-display); font-size: 0.95rem; font-weight: 600;
  color: var(--inv-green-light); padding-top: 12px;
  border-top: 1px solid var(--inv-border);
}

.inv-total-revenue {
  text-align: center; padding: 32px;
  background: linear-gradient(135deg, rgba(0,184,148,0.1), rgba(108,92,231,0.1));
  border: 1px solid var(--inv-border-hover);
  border-radius: var(--inv-radius);
}
.inv-total-revenue-label { font-size: 0.9rem; color: var(--inv-text-muted); margin-bottom: 8px; }
.inv-total-revenue-value {
  font-family: var(--inv-font-display); font-size: 2.5rem; font-weight: 700;
}

/* ═══════════════════════════════════════════════════════════════
   6. FINANCIAL PROJECTIONS
   ═══════════════════════════════════════════════════════════════ */
.inv-fin-table-wrap {
  overflow-x: auto; margin-bottom: 48px;
  border-radius: var(--inv-radius); border: 1px solid var(--inv-border);
}
.inv-fin-table {
  width: 100%; border-collapse: collapse;
  font-size: 0.95rem;
}
.inv-fin-table th, .inv-fin-table td { padding: 16px 20px; text-align: left; }
.inv-fin-table thead th {
  background: rgba(0,184,148,0.08); color: var(--inv-green-light);
  font-family: var(--inv-font-display); font-weight: 600;
  border-bottom: 1px solid var(--inv-border);
}
.inv-fin-table tbody tr { border-bottom: 1px solid var(--inv-border); transition: background 0.2s; }
.inv-fin-table tbody tr:hover { background: rgba(255,255,255,0.02); }
.inv-fin-table td:first-child { font-weight: 600; color: #fff; }
.inv-scenario-badge {
  display: inline-block; padding: 3px 10px; border-radius: 6px;
  font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
}
.inv-scenario-con { background: rgba(0,184,148,0.15); color: var(--inv-green-light); }
.inv-scenario-mod { background: rgba(9,132,227,0.15); color: #74b9ff; }
.inv-scenario-agg { background: rgba(108,92,231,0.15); color: #a29bfe; }

/* CSS Chart Bars */
.inv-chart-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
@media (max-width: 768px) { .inv-chart-container { grid-template-columns: 1fr; } }
.inv-chart-col { background: var(--inv-bg-card); border: 1px solid var(--inv-border); border-radius: var(--inv-radius); padding: 24px; }
.inv-chart-col h4 { font-family: var(--inv-font-display); text-align: center; margin-bottom: 20px; color: #fff; }
.inv-chart-bar-wrap { margin-bottom: 14px; }
.inv-chart-bar-label { font-size: 0.8rem; color: var(--inv-text-muted); margin-bottom: 4px; display: flex; justify-content: space-between; }
.inv-chart-bar { height: 24px; background: rgba(255,255,255,0.04); border-radius: 6px; overflow: hidden; }
.inv-chart-bar-fill {
  height: 100%; border-radius: 6px;
  display: flex; align-items: center; padding: 0 8px;
  font-size: 0.7rem; font-weight: 600; color: #fff;
  transition: width 1.5s ease; width: 0;
}
.inv-chart-bar-fill.con { background: linear-gradient(90deg, #00b894, #00cec9); }
.inv-chart-bar-fill.mod { background: linear-gradient(90deg, #0984e3, #74b9ff); }
.inv-chart-bar-fill.agg { background: linear-gradient(90deg, #6c5ce7, #a29bfe); }

/* ═══════════════════════════════════════════════════════════════
   7. COMPETITIVE ADVANTAGE
   ═══════════════════════════════════════════════════════════════ */
.inv-comp-table-wrap {
  overflow-x: auto; margin-bottom: 48px;
  border-radius: var(--inv-radius); border: 1px solid var(--inv-border);
}
.inv-comp-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.inv-comp-table th, .inv-comp-table td { padding: 14px 18px; text-align: center; }
.inv-comp-table th { background: rgba(255,255,255,0.03); font-family: var(--inv-font-display); color: #fff; border-bottom: 1px solid var(--inv-border); }
.inv-comp-table th:first-child, .inv-comp-table td:first-child { text-align: left; }
.inv-comp-table tbody tr { border-bottom: 1px solid var(--inv-border); }
.inv-comp-table .inv-check { color: var(--inv-green); font-weight: 700; }
.inv-comp-table .inv-cross { color: #555; }
.inv-comp-table .inv-highlight-col { background: rgba(0,184,148,0.05); }

.inv-diff-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
@media (max-width: 900px) { .inv-diff-grid { grid-template-columns: repeat(2, 1fr); } }
.inv-diff-card {
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius); padding: 28px;
  transition: all 0.3s;
}
.inv-diff-card:hover { border-color: var(--inv-border-hover); transform: translateY(-3px); }
.inv-diff-icon { font-size: 2rem; margin-bottom: 12px; }
.inv-diff-title { font-family: var(--inv-font-display); font-size: 1.05rem; font-weight: 600; color: #fff; margin-bottom: 8px; }
.inv-diff-desc { font-size: 0.85rem; color: var(--inv-text-muted); line-height: 1.6; }

/* ═══════════════════════════════════════════════════════════════
   8. INVESTMENT TIERS
   ═══════════════════════════════════════════════════════════════ */
.inv-tiers-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
@media (max-width: 900px) { .inv-tiers-grid { grid-template-columns: 1fr; } }
.inv-tier-card {
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius); padding: 36px 28px;
  text-align: center; transition: all 0.4s; cursor: pointer;
  position: relative; overflow: hidden;
}
.inv-tier-card:hover { border-color: var(--inv-border-hover); transform: translateY(-6px); box-shadow: var(--inv-shadow-glow); }
.inv-tier-card.featured {
  border-color: var(--inv-green);
  box-shadow: 0 0 40px rgba(0,184,148,0.1);
}
.inv-tier-card.featured::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: var(--inv-gradient);
}
.inv-tier-badge {
  display: inline-block; padding: 5px 16px; border-radius: 50px;
  font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
  margin-bottom: 20px;
}
.inv-tier-badge.seed { background: rgba(0,184,148,0.15); color: var(--inv-green-light); }
.inv-tier-badge.growth { background: rgba(9,132,227,0.15); color: #74b9ff; }
.inv-tier-badge.strategic { background: rgba(108,92,231,0.15); color: #a29bfe; }
.inv-tier-label-tag {
  position: absolute; top: 16px; right: -28px;
  background: var(--inv-gradient); color: #fff;
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
  padding: 4px 40px; transform: rotate(45deg);
}
.inv-tier-amount {
  font-family: var(--inv-font-display); font-size: 2.8rem; font-weight: 700; color: #fff; margin-bottom: 8px;
}
.inv-tier-range { font-size: 0.9rem; color: var(--inv-text-muted); margin-bottom: 24px; }
.inv-tier-features { list-style: none; padding: 0; margin: 0 0 28px; text-align: left; }
.inv-tier-features li {
  padding: 10px 0; border-bottom: 1px solid var(--inv-border);
  font-size: 0.9rem; color: var(--inv-text-muted); display: flex; align-items: flex-start; gap: 10px;
}
.inv-tier-features li::before { content:'✓'; color: var(--inv-green); font-weight: 700; flex-shrink: 0; }
.inv-tier-roi {
  background: rgba(0,184,148,0.06); border: 1px solid rgba(0,184,148,0.15);
  border-radius: var(--inv-radius-sm); padding: 16px; margin-bottom: 20px;
}
.inv-tier-roi-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--inv-text-muted); margin-bottom: 4px; }
.inv-tier-roi-value { font-family: var(--inv-font-display); font-size: 1.3rem; font-weight: 700; color: var(--inv-green-light); }

/* ═══════════════════════════════════════════════════════════════
   9. ROADMAP TIMELINE
   ═══════════════════════════════════════════════════════════════ */
.inv-timeline { position: relative; padding: 0 0 0 40px; }
.inv-timeline::before {
  content: ''; position: absolute; left: 15px; top: 0; bottom: 0;
  width: 2px; background: linear-gradient(180deg, var(--inv-green), var(--inv-purple));
}
.inv-timeline-item {
  position: relative; margin-bottom: 40px; padding-left: 32px;
}
.inv-timeline-marker {
  position: absolute; left: -33px; top: 4px;
  width: 20px; height: 20px; border-radius: 50%;
  border: 3px solid var(--inv-green); background: var(--inv-bg);
  z-index: 1; transition: all 0.3s;
}
.inv-timeline-item.completed .inv-timeline-marker {
  background: var(--inv-green);
  box-shadow: 0 0 12px rgba(0,184,148,0.4);
}
.inv-timeline-item.completed .inv-timeline-marker::after {
  content: '✓'; position: absolute; top: 50%; left: 50%;
  transform: translate(-50%, -50%); color: #fff;
  font-size: 0.65rem; font-weight: 700;
}
.inv-timeline-date {
  font-size: 0.8rem; color: var(--inv-green-light);
  font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px;
}
.inv-timeline-title { font-family: var(--inv-font-display); font-size: 1.15rem; font-weight: 600; color: #fff; margin-bottom: 6px; }
.inv-timeline-desc { font-size: 0.9rem; color: var(--inv-text-muted); line-height: 1.6; }
.inv-timeline-status {
  display: inline-block; padding: 3px 10px; border-radius: 6px;
  font-size: 0.7rem; font-weight: 600; text-transform: uppercase; margin-top: 8px;
}
.inv-status-done { background: rgba(0,184,148,0.15); color: var(--inv-green-light); }
.inv-status-progress { background: rgba(9,132,227,0.15); color: #74b9ff; }
.inv-status-planned { background: rgba(108,92,231,0.15); color: #a29bfe; }

/* ═══════════════════════════════════════════════════════════════
   10. FOUNDER LETTER
   ═══════════════════════════════════════════════════════════════ */
.inv-founder {
  display: grid; grid-template-columns: 1fr 2fr; gap: 48px; align-items: center;
}
@media (max-width: 768px) { .inv-founder { grid-template-columns: 1fr; text-align: center; } }
.inv-founder-photo {
  width: 280px; height: 280px; border-radius: 50%;
  background: linear-gradient(135deg, var(--inv-bg-card), var(--inv-bg-card-hover));
  border: 3px solid var(--inv-border);
  display: flex; align-items: center; justify-content: center;
  font-size: 5rem; margin: 0 auto;
  box-shadow: 0 0 60px rgba(0,184,148,0.08);
}
.inv-founder-letter h3 { font-family: var(--inv-font-display); font-size: 1.8rem; color: #fff; margin-bottom: 20px; }
.inv-founder-letter p { color: var(--inv-text-muted); line-height: 1.8; margin-bottom: 16px; font-size: 1rem; }
.inv-founder-letter .inv-signature {
  font-family: var(--inv-font-display); font-size: 1.2rem;
  color: var(--inv-green-light); font-style: italic; margin-top: 24px;
}

/* ═══════════════════════════════════════════════════════════════
   11. TRUST & TRANSPARENCY
   ═══════════════════════════════════════════════════════════════ */
.inv-trust-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 48px; }
@media (max-width: 768px) { .inv-trust-grid { grid-template-columns: 1fr; } }
.inv-trust-card {
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius); padding: 28px;
  transition: all 0.3s;
}
.inv-trust-card:hover { border-color: var(--inv-border-hover); }
.inv-trust-card-icon { font-size: 2rem; margin-bottom: 12px; }
.inv-trust-card-title { font-family: var(--inv-font-display); font-size: 1.05rem; font-weight: 600; color: #fff; margin-bottom: 8px; }
.inv-trust-card-desc { font-size: 0.9rem; color: var(--inv-text-muted); line-height: 1.6; }

/* Pie Chart CSS */
.inv-pie-wrap { display: flex; justify-content: center; align-items: center; gap: 48px; flex-wrap: wrap; }
.inv-pie {
  width: 200px; height: 200px; border-radius: 50%;
  background: conic-gradient(
    #00b894 0deg 144deg,
    #0984e3 144deg 216deg,
    #6c5ce7 216deg 270deg,
    #e17055 270deg 306deg,
    #fdcb6e 306deg 342deg,
    #636e72 342deg 360deg
  );
  position: relative;
}
.inv-pie::after {
  content: ''; position: absolute; inset: 35px;
  background: var(--inv-bg-alt); border-radius: 50%;
}
.inv-pie-legend { list-style: none; padding: 0; margin: 0; }
.inv-pie-legend li { display: flex; align-items: center; gap: 10px; padding: 8px 0; font-size: 0.9rem; color: var(--inv-text-muted); }
.inv-pie-swatch { width: 14px; height: 14px; border-radius: 4px; flex-shrink: 0; }

/* ═══════════════════════════════════════════════════════════════
   12. FAQ ACCORDION
   ═══════════════════════════════════════════════════════════════ */
.inv-faq-list { max-width: 800px; margin: 0 auto; }
.inv-faq-item {
  margin-bottom: 12px; border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius-sm); overflow: hidden;
  transition: border-color 0.3s;
}
.inv-faq-item:hover { border-color: var(--inv-border-hover); }
.inv-faq-q {
  display: flex; justify-content: space-between; align-items: center;
  padding: 20px 24px; cursor: pointer;
  font-family: var(--inv-font-display); font-size: 1.05rem; font-weight: 500; color: #fff;
  background: var(--inv-bg-card); transition: background 0.3s;
}
.inv-faq-q:hover { background: var(--inv-bg-card-hover); }
.inv-faq-arrow {
  font-size: 1.2rem; transition: transform 0.3s; color: var(--inv-text-muted);
}
.inv-faq-item.open .inv-faq-arrow { transform: rotate(180deg); }
.inv-faq-a {
  max-height: 0; overflow: hidden; transition: max-height 0.4s ease, padding 0.3s;
  background: rgba(0,0,0,0.15);
}
.inv-faq-item.open .inv-faq-a { max-height: 300px; }
.inv-faq-a-inner { padding: 20px 24px; font-size: 0.95rem; color: var(--inv-text-muted); line-height: 1.7; }

/* ═══════════════════════════════════════════════════════════════
   13. CONTACT FORM
   ═══════════════════════════════════════════════════════════════ */
.inv-form-container {
  max-width: 680px; margin: 0 auto;
  background: var(--inv-bg-card); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius); padding: 48px; position: relative;
  box-shadow: var(--inv-shadow);
}
.inv-form-container::before {
  content: ''; position: absolute; top: -1px; left: 20%; right: 20%;
  height: 2px; background: var(--inv-gradient);
}
.inv-form-title { font-family: var(--inv-font-display); font-size: 1.6rem; font-weight: 700; color: #fff; text-align: center; margin-bottom: 8px; }
.inv-form-subtitle { text-align: center; color: var(--inv-text-muted); font-size: 0.95rem; margin-bottom: 32px; }

.inv-form-group { margin-bottom: 20px; }
.inv-form-label { display: block; font-size: 0.85rem; font-weight: 500; color: var(--inv-text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.inv-form-input, .inv-form-select, .inv-form-textarea {
  width: 100%; padding: 14px 16px;
  background: rgba(255,255,255,0.04); border: 1px solid var(--inv-border);
  border-radius: var(--inv-radius-sm); color: var(--inv-text);
  font-family: var(--inv-font-body); font-size: 0.95rem;
  transition: all 0.3s; outline: none;
}
.inv-form-input:focus, .inv-form-select:focus, .inv-form-textarea:focus {
  border-color: var(--inv-green); box-shadow: 0 0 0 3px rgba(0,184,148,0.1);
}
.inv-form-select { appearance: none; cursor: pointer; }
.inv-form-textarea { resize: vertical; min-height: 100px; }
.inv-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 580px) { .inv-form-row { grid-template-columns: 1fr; } }

.inv-roi-preview {
  background: rgba(0,184,148,0.06); border: 1px solid rgba(0,184,148,0.15);
  border-radius: var(--inv-radius-sm); padding: 20px; margin-bottom: 24px;
  text-align: center; display: none;
}
.inv-roi-preview.visible { display: block; animation: inv-fade-in 0.5s ease; }
.inv-roi-preview-label { font-size: 0.8rem; color: var(--inv-text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.inv-roi-preview-value { font-family: var(--inv-font-display); font-size: 1.6rem; font-weight: 700; color: var(--inv-green-light); }

.inv-form-submit {
  width: 100%; padding: 18px; border: none;
  background: var(--inv-gradient); color: #fff;
  font-family: var(--inv-font-display); font-size: 1.1rem; font-weight: 600;
  border-radius: var(--inv-radius-sm); cursor: pointer;
  transition: all 0.3s;
}
.inv-form-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,184,148,0.3); }
.inv-form-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Success/Error states */
.inv-form-message {
  padding: 16px 20px; border-radius: var(--inv-radius-sm);
  font-size: 0.95rem; margin-top: 16px; display: none;
}
.inv-form-message.success {
  background: rgba(0,184,148,0.1); border: 1px solid var(--inv-green);
  color: var(--inv-green-light); display: block;
}
.inv-form-message.error {
  background: rgba(231,76,60,0.1); border: 1px solid #e74c3c;
  color: #e74c3c; display: block;
}

/* ═══════════════════════════════════════════════════════════════
   14. FOOTER CTA
   ═══════════════════════════════════════════════════════════════ */
.inv-footer-cta {
  text-align: center; padding: 80px 24px;
  background: linear-gradient(135deg, rgba(0,184,148,0.06), rgba(108,92,231,0.06));
  border-top: 1px solid var(--inv-border);
}
.inv-footer-cta h2 {
  font-family: var(--inv-font-display); font-size: 2.2rem; font-weight: 700; color: #fff; margin-bottom: 16px;
}
.inv-footer-cta p { color: var(--inv-text-muted); font-size: 1.1rem; margin-bottom: 32px; }

/* ── Utilities ── */
@keyframes inv-fade-in { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
.inv-animate { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease, transform 0.6s ease; }
.inv-animate.in-view { opacity: 1; transform: translateY(0); }

@media (max-width: 640px) {
  .inv-tam-grid { grid-template-columns: 1fr; }
  .inv-ps-grid { grid-template-columns: 1fr; }
  .inv-diff-grid { grid-template-columns: 1fr; }
  .inv-revenue-grid { grid-template-columns: 1fr; }
  .inv-tiers-grid { grid-template-columns: 1fr; }
  .inv-section { padding: 64px 0; }
  .inv-hero { padding: 100px 16px 60px; }
}
</style>

<div class="inv-page">

<!-- ═══════════════════════════════════════════════════════════════
     1. HERO
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-hero" id="inv-hero">
  <div class="inv-hero-bg"></div>
  <div class="inv-particles">
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
    <div class="inv-particle"></div>
  </div>
  <div class="inv-hero-content">
    <div class="inv-pulse-badge">
      <span class="inv-pulse-dot"></span>
      Now Accepting Investors — Limited Spots
    </div>
    <h1><span class="inv-gradient-text">Invest in the Future of AI</span></h1>
    <p class="inv-hero-sub">
      GoSiteMe is building Canada's most comprehensive AI platform — 1,220+ tools, 89 categories, voice AI, 
      developer APIs, GPU servers, and a full hosting ecosystem. All bootstrapped. All live. And we're just getting started.
    </p>
    <div class="inv-hero-ctas">
      <a href="#inv-contact" class="inv-btn inv-btn-primary">
        <span>💎</span> Invest Now
      </a>
      <a href="#inv-founder" class="inv-btn inv-btn-secondary">
        <span>▶</span> Read Our Story
      </a>
    </div>
    <div class="inv-live-counter">
      <div class="inv-live-counter-item">
        <div class="inv-live-counter-value">$<span id="inv-raised">0</span></div>
        <div class="inv-live-counter-label">Capital Raised</div>
      </div>
      <div class="inv-live-counter-item">
        <div class="inv-live-counter-value"><span id="inv-investor-count">0</span></div>
        <div class="inv-live-counter-label">Investors Joined</div>
      </div>
      <div class="inv-live-counter-item">
        <div class="inv-live-counter-value"><span id="inv-momentum">0</span>%</div>
        <div class="inv-live-counter-label">Round Progress</div>
      </div>
    </div>
    <div class="inv-trust-strip">
      <span class="inv-trust-strip-label">Backed by trust in:</span>
      <div class="inv-trust-logos">
        <span class="inv-trust-logo">Y Combinator Standards</span>
        <span class="inv-trust-logo">SAFE Agreements</span>
        <span class="inv-trust-logo">Canadian Corp</span>
        <span class="inv-trust-logo">Stripe Payments</span>
        <span class="inv-trust-logo">AI-Powered Platform</span>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     2. LIVE STATS BAR
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-stats-bar" id="inv-stats">
  <div class="inv-container">
    <div class="inv-stats-grid">
      <div class="inv-stat-item inv-animate">
        <div class="inv-stat-icon">🤖</div>
        <div class="inv-stat-value"><span class="inv-count" data-target="1220">0</span>+</div>
        <div class="inv-stat-label">AI Tools Built</div>
      </div>
      <div class="inv-stat-item inv-animate">
        <div class="inv-stat-icon">🏢</div>
        <div class="inv-stat-value"><span class="inv-count" data-target="89">0</span></div>
        <div class="inv-stat-label">Industry Verticals</div>
      </div>
      <div class="inv-stat-item inv-animate">
        <div class="inv-stat-icon">🔌</div>
        <div class="inv-stat-value"><span class="inv-count" data-target="504">0</span>+</div>
        <div class="inv-stat-label">API Endpoints</div>
      </div>
      <div class="inv-stat-item inv-animate">
        <div class="inv-stat-icon">💰</div>
        <div class="inv-stat-value">$<span class="inv-count" data-target="4">0</span>/mo</div>
        <div class="inv-stat-label">Starting Price</div>
      </div>
      <div class="inv-stat-item inv-animate">
        <div class="inv-stat-icon">📊</div>
        <div class="inv-stat-value"><span class="inv-count" data-target="8">0</span></div>
        <div class="inv-stat-label">Revenue Streams</div>
      </div>
      <div class="inv-stat-item inv-animate">
        <div class="inv-stat-icon">🌍</div>
        <div class="inv-stat-value"><span class="inv-count" data-target="195">0</span>+</div>
        <div class="inv-stat-label">Countries Served</div>
      </div>
      <div class="inv-stat-item inv-animate">
        <div class="inv-stat-icon">📁</div>
        <div class="inv-stat-value"><span class="inv-count" data-target="18227">0</span></div>
        <div class="inv-stat-label">Codebase Files</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     3. WHY INVEST NOW — Market Opportunity
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section" id="inv-market">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Why Invest <span class="inv-gradient-text">Now</span></h2>
    <p class="inv-section-subtitle inv-animate">
      The AI market is projected to reach $1.8 trillion by 2030. GoSiteMe is positioned at the intersection of 
      AI tools, voice AI, hosting, and developer infrastructure — capturing multiple revenue streams across a 
      massive addressable market.
    </p>

    <!-- TAM / SAM / SOM -->
    <div class="inv-tam-grid inv-animate">
      <div class="inv-tam-card">
        <div class="inv-tam-label">Total Addressable Market</div>
        <div class="inv-tam-value inv-gradient-text">$200B</div>
        <div class="inv-tam-desc">Global AI SaaS, hosting, voice AI, and developer tools combined market by 2028</div>
      </div>
      <div class="inv-tam-card">
        <div class="inv-tam-label">Serviceable Addressable Market</div>
        <div class="inv-tam-value inv-gradient-text">$12B</div>
        <div class="inv-tam-desc">SMBs, professionals, and developers needing affordable, comprehensive AI solutions</div>
      </div>
      <div class="inv-tam-card">
        <div class="inv-tam-label">Serviceable Obtainable Market</div>
        <div class="inv-tam-value inv-gradient-text">$500M</div>
        <div class="inv-tam-desc">Realistic capture with our multi-vertical approach and competitive pricing</div>
      </div>
    </div>

    <!-- Problem / Solution Grid -->
    <div class="inv-ps-grid inv-animate">
      <div>
        <div class="inv-ps-col-title problem">❌ The Problem</div>
        <div class="inv-ps-card">
          <div class="inv-ps-icon">🧩</div>
          <h4>Fragmented AI Tools</h4>
          <p>Businesses subscribe to 5–10 separate AI services. Each one costs $20–$100/month. There's no unified platform that serves all industries under one roof.</p>
        </div>
        <div class="inv-ps-card">
          <div class="inv-ps-icon">💸</div>
          <h4>Prohibitive Pricing</h4>
          <p>Enterprise AI solutions cost $500–$5,000/month. Small businesses and professionals are priced out of the AI revolution entirely.</p>
        </div>
        <div class="inv-ps-card">
          <div class="inv-ps-icon">🔒</div>
          <h4>No Developer Access</h4>
          <p>Most AI platforms offer no API, no SDKs, no white-labeling. Developers can't build on top of them or integrate them into existing workflows.</p>
        </div>
      </div>
      <div>
        <div class="inv-ps-col-title solution">✅ Our Solution</div>
        <div class="inv-ps-card">
          <div class="inv-ps-icon">🏗️</div>
          <h4>One Unified Platform</h4>
          <p>1,220+ AI tools across 35 industries — legal, medical, real estate, marketing, finance, education, and more. All accessible from a single dashboard.</p>
        </div>
        <div class="inv-ps-card">
          <div class="inv-ps-icon">🎯</div>
          <h4>Starting at $4/month</h4>
          <p>We've made enterprise-grade AI tools affordable for everyone. Our pricing undercuts competitors by 90% while delivering more features.</p>
        </div>
        <div class="inv-ps-card">
          <div class="inv-ps-icon">🔓</div>
          <h4>Full Developer Ecosystem</h4>
          <p>504+ API endpoints, 3 SDKs, webhooks, white-label capabilities, marketplace, and a growing developer community building on our platform.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     4. TRACTION DASHBOARD
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section inv-section-alt" id="inv-traction">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Live <span class="inv-gradient-text">Traction Dashboard</span></h2>
    <p class="inv-section-subtitle inv-animate">
      Every metric you see is pulled from our live systems. No vanity numbers. No projections dressed as facts. 
      Real data from a real platform.
    </p>
    <div class="inv-traction-badge inv-animate">
      <span class="inv-pulse-dot" style="width:8px;height:8px;"></span>
      Everything You See Is Live
    </div>

    <div class="inv-traction-grid">
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">🛠️</div>
        <div class="inv-traction-value" id="met-tools">1,220+</div>
        <div class="inv-traction-label">AI Tools Live</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="87"></div></div>
      </div>
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">🗣️</div>
        <div class="inv-traction-value" id="met-voice">85+</div>
        <div class="inv-traction-label">Voice AI Tools</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="72"></div></div>
      </div>
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">📄</div>
        <div class="inv-traction-value" id="met-pages">44</div>
        <div class="inv-traction-label">Platform Pages</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="65"></div></div>
      </div>
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">📰</div>
        <div class="inv-traction-value" id="met-articles">26</div>
        <div class="inv-traction-label">Published Articles</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="52"></div></div>
      </div>
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">🔌</div>
        <div class="inv-traction-value" id="met-api">504+</div>
        <div class="inv-traction-label">API Endpoints</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="80"></div></div>
      </div>
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">📦</div>
        <div class="inv-traction-value" id="met-sdks">3</div>
        <div class="inv-traction-label">SDKs Released</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="30"></div></div>
      </div>
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">📁</div>
        <div class="inv-traction-value" id="met-files">18,227</div>
        <div class="inv-traction-label">Codebase Files</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="91"></div></div>
      </div>
      <div class="inv-traction-card inv-animate">
        <div class="inv-traction-icon">🏢</div>
        <div class="inv-traction-value" id="met-verticals">35</div>
        <div class="inv-traction-label">Industry Verticals</div>
        <div class="inv-progress-bar"><div class="inv-progress-fill" data-width="70"></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     5. REVENUE MODEL
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section" id="inv-revenue">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate"><span class="inv-gradient-text">8 Revenue Streams</span></h2>
    <p class="inv-section-subtitle inv-animate">
      Diversified monetization across subscriptions, infrastructure, marketplace, and services. 
      No single point of failure. Multiple paths to profitability.
    </p>

    <div class="inv-revenue-grid inv-animate">
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">💳</div>
        <div class="inv-revenue-name">SaaS Subscriptions</div>
        <div class="inv-revenue-desc">Recurring monthly/annual plans for AI tools access. Tiers from $4 to $99/mo per user across all 89 categories.</div>
        <div class="inv-revenue-est">Est. $2.4M ARR at scale</div>
      </div>
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">🔌</div>
        <div class="inv-revenue-name">API & Developer Access</div>
        <div class="inv-revenue-desc">Usage-based API billing for developers integrating GoSiteMe AI into their applications. 504+ endpoints.</div>
        <div class="inv-revenue-est">Est. $1.8M ARR at scale</div>
      </div>
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">🗣️</div>
        <div class="inv-revenue-name">Voice AI Services</div>
        <div class="inv-revenue-desc">Per-minute billing for voice AI assistants, IVR, call campaigns, and conference rooms. High-margin telephony.</div>
        <div class="inv-revenue-est">Est. $1.2M ARR at scale</div>
      </div>
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">🌐</div>
        <div class="inv-revenue-name">Hosting & Domains</div>
        <div class="inv-revenue-desc">Web hosting, domain registration, SSL certificates, and managed infrastructure for customers' businesses.</div>
        <div class="inv-revenue-est">Est. $800K ARR at scale</div>
      </div>
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">🏪</div>
        <div class="inv-revenue-name">Marketplace Commission</div>
        <div class="inv-revenue-desc">20% commission on third-party tools, templates, and extensions sold in the GoSiteMe marketplace.</div>
        <div class="inv-revenue-est">Est. $600K ARR at scale</div>
      </div>
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">🏷️</div>
        <div class="inv-revenue-name">White-Label Licensing</div>
        <div class="inv-revenue-desc">Enterprise clients rebrand and resell GoSiteMe tools under their own brand. High-value B2B contracts.</div>
        <div class="inv-revenue-est">Est. $1.5M ARR at scale</div>
      </div>
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">🖥️</div>
        <div class="inv-revenue-name">AI GPU Servers</div>
        <div class="inv-revenue-desc">Dedicated and shared GPU infrastructure for AI training and inference. Pre-configured AI server rentals.</div>
        <div class="inv-revenue-est">Est. $2.0M ARR at scale</div>
      </div>
      <div class="inv-revenue-card">
        <div class="inv-revenue-icon">🎓</div>
        <div class="inv-revenue-name">AI Training & Courses</div>
        <div class="inv-revenue-desc">Online courses, certifications, and enterprise training programs on AI tools and implementation.</div>
        <div class="inv-revenue-est">Est. $400K ARR at scale</div>
      </div>
    </div>

    <div class="inv-total-revenue inv-animate">
      <div class="inv-total-revenue-label">Combined Addressable Revenue at Scale</div>
      <div class="inv-total-revenue-value inv-gradient-text">$10.7M ARR</div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     6. FINANCIAL PROJECTIONS
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section inv-section-alt" id="inv-projections">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Financial <span class="inv-gradient-text">Projections</span></h2>
    <p class="inv-section-subtitle inv-animate">
      Three scenarios based on market conditions, execution speed, and capital deployment. 
      All figures are projections and not guaranteed.
    </p>

    <div class="inv-fin-table-wrap inv-animate">
      <table class="inv-fin-table">
        <thead>
          <tr>
            <th>Metric</th>
            <th>Year 1 (2026)</th>
            <th>Year 2 (2027)</th>
            <th>Year 3 (2028)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><span class="inv-scenario-badge inv-scenario-con">Conservative</span> MRR</td>
            <td>$8K</td>
            <td>$35K</td>
            <td>$120K</td>
          </tr>
          <tr>
            <td><span class="inv-scenario-badge inv-scenario-mod">Moderate</span> MRR</td>
            <td>$20K</td>
            <td>$85K</td>
            <td>$320K</td>
          </tr>
          <tr>
            <td><span class="inv-scenario-badge inv-scenario-agg">Aggressive</span> MRR</td>
            <td>$45K</td>
            <td>$200K</td>
            <td>$650K</td>
          </tr>
          <tr>
            <td>Paying Customers</td>
            <td>200 – 1,200</td>
            <td>1,500 – 8,000</td>
            <td>8,000 – 30,000</td>
          </tr>
          <tr>
            <td>Team Size</td>
            <td>3 – 5</td>
            <td>8 – 15</td>
            <td>20 – 40</td>
          </tr>
          <tr>
            <td>Burn Rate / mo</td>
            <td>$5K – $12K</td>
            <td>$20K – $50K</td>
            <td>$60K – $150K</td>
          </tr>
          <tr>
            <td>Est. Valuation</td>
            <td>$2M – $5M</td>
            <td>$10M – $25M</td>
            <td>$30M – $80M</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- CSS Chart Bars -->
    <div class="inv-chart-container inv-animate">
      <div class="inv-chart-col">
        <h4>Year 1 — 2026</h4>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Conservative</span><span>$8K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill con" data-width="12">$8K</div></div>
        </div>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Moderate</span><span>$20K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill mod" data-width="30">$20K</div></div>
        </div>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Aggressive</span><span>$45K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill agg" data-width="50">$45K</div></div>
        </div>
      </div>
      <div class="inv-chart-col">
        <h4>Year 2 — 2027</h4>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Conservative</span><span>$35K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill con" data-width="25">$35K</div></div>
        </div>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Moderate</span><span>$85K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill mod" data-width="55">$85K</div></div>
        </div>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Aggressive</span><span>$200K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill agg" data-width="78">$200K</div></div>
        </div>
      </div>
      <div class="inv-chart-col">
        <h4>Year 3 — 2028</h4>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Conservative</span><span>$120K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill con" data-width="40">$120K</div></div>
        </div>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Moderate</span><span>$320K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill mod" data-width="70">$320K</div></div>
        </div>
        <div class="inv-chart-bar-wrap">
          <div class="inv-chart-bar-label"><span>Aggressive</span><span>$650K</span></div>
          <div class="inv-chart-bar"><div class="inv-chart-bar-fill agg" data-width="100">$650K</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     7. COMPETITIVE ADVANTAGE
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section" id="inv-competitive">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Competitive <span class="inv-gradient-text">Advantage</span></h2>
    <p class="inv-section-subtitle inv-animate">
      We're not another ChatGPT wrapper. GoSiteMe is a full-stack AI platform that combines tools, 
      voice, hosting, and developer infrastructure in a way no competitor does.
    </p>

    <div class="inv-comp-table-wrap inv-animate">
      <table class="inv-comp-table">
        <thead>
          <tr>
            <th>Feature</th>
            <th class="inv-highlight-col">GoSiteMe</th>
            <th>ChatGPT / OpenAI</th>
            <th>Harvey AI</th>
            <th>Clio</th>
            <th>Jasper</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Multi-Industry AI (35+)</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
          </tr>
          <tr>
            <td>Voice AI Platform</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
          </tr>
          <tr>
            <td>Web Hosting Included</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
          </tr>
          <tr>
            <td>Developer API (500+)</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-check">✓</span></td>
            <td><span class="inv-check">✓</span></td>
          </tr>
          <tr>
            <td>White-Label Option</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-check">✓</span></td>
          </tr>
          <tr>
            <td>GPU Server Rental</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
          </tr>
          <tr>
            <td>Marketplace</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
          </tr>
          <tr>
            <td>Price from $4/mo</td>
            <td class="inv-highlight-col"><span class="inv-check">✓</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
            <td><span class="inv-cross">✗</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <h3 class="inv-section-title inv-animate" style="font-size:1.6rem;margin-bottom:32px;">What Makes Us <span class="inv-gradient-text">Different</span></h3>
    <div class="inv-diff-grid inv-animate">
      <div class="inv-diff-card">
        <div class="inv-diff-icon">🧬</div>
        <div class="inv-diff-title">Horizontal Platform</div>
        <div class="inv-diff-desc">Unlike single-industry tools, we serve 89 categories. One platform, infinite use cases. Moat through breadth.</div>
      </div>
      <div class="inv-diff-card">
        <div class="inv-diff-icon">⚡</div>
        <div class="inv-diff-title">Bootstrapped Speed</div>
        <div class="inv-diff-desc">Built by a solo founder from $0. No bloated teams, no waste. Every dollar of investment goes directly to growth.</div>
      </div>
      <div class="inv-diff-card">
        <div class="inv-diff-icon">🔗</div>
        <div class="inv-diff-title">Full Stack Play</div>
        <div class="inv-diff-desc">AI + Voice + Hosting + APIs + Marketplace. We own the entire value chain, creating natural lock-in and cross-selling.</div>
      </div>
      <div class="inv-diff-card">
        <div class="inv-diff-icon">🇨🇦</div>
        <div class="inv-diff-title">Canadian Innovation</div>
        <div class="inv-diff-desc">Based in Ontario, Canada. Access to SR&ED tax credits, Canadian AI ecosystem, and trust of a regulated market.</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     8. INVESTMENT TIERS
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section inv-section-alt" id="inv-tiers">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Investment <span class="inv-gradient-text">Tiers</span></h2>
    <p class="inv-section-subtitle inv-animate">
      Choose the tier that aligns with your conviction. All tiers include SAFE agreements, 
      monthly updates, and access to our live investor dashboard.
    </p>

    <div class="inv-tiers-grid inv-animate">
      <!-- Seed Tier -->
      <div class="inv-tier-card" data-tier="seed" data-amount="1000" onclick="selectTier('seed','1,000 – 4,999')">
        <span class="inv-tier-badge seed">Seed</span>
        <div class="inv-tier-amount">$1K–$5K</div>
        <div class="inv-tier-range">Early Believer</div>
        <ul class="inv-tier-features">
          <li>SAFE Agreement (standard terms)</li>
          <li>Monthly investor email updates</li>
          <li>Access to investor dashboard</li>
          <li>Name on public investor wall</li>
          <li>Early access to new features</li>
          <li>Priority customer support</li>
        </ul>
        <div class="inv-tier-roi">
          <div class="inv-tier-roi-label">Projected 3-Year ROI</div>
          <div class="inv-tier-roi-value">5x – 15x</div>
        </div>
        <button class="inv-btn inv-btn-secondary" style="width:100%;" onclick="event.stopPropagation();selectTier('seed','1,000 – 4,999')">Select Seed Tier</button>
      </div>

      <!-- Growth Tier (Featured) -->
      <div class="inv-tier-card featured" data-tier="growth" data-amount="5000" onclick="selectTier('growth','5,000 – 24,999')">
        <span class="inv-tier-label-tag">Popular</span>
        <span class="inv-tier-badge growth">Growth</span>
        <div class="inv-tier-amount">$5K–$25K</div>
        <div class="inv-tier-range">Growth Partner</div>
        <ul class="inv-tier-features">
          <li>Everything in Seed tier</li>
          <li>Quarterly video call with founder</li>
          <li>Input on product roadmap</li>
          <li>Beta access to all new products</li>
          <li>Discounted platform services</li>
          <li>Referral bonus for new investors</li>
          <li>Pro-rata rights on next round</li>
        </ul>
        <div class="inv-tier-roi">
          <div class="inv-tier-roi-label">Projected 3-Year ROI</div>
          <div class="inv-tier-roi-value">8x – 25x</div>
        </div>
        <button class="inv-btn inv-btn-primary" style="width:100%;" onclick="event.stopPropagation();selectTier('growth','5,000 – 24,999')">Select Growth Tier</button>
      </div>

      <!-- Strategic Tier -->
      <div class="inv-tier-card" data-tier="strategic" data-amount="25000" onclick="selectTier('strategic','25,000+')">
        <span class="inv-tier-badge strategic">Strategic</span>
        <div class="inv-tier-amount">$25K+</div>
        <div class="inv-tier-range">Strategic Ally</div>
        <ul class="inv-tier-features">
          <li>Everything in Growth tier</li>
          <li>Advisory board seat consideration</li>
          <li>Monthly 1-on-1 with founder</li>
          <li>Custom white-label deployment</li>
          <li>Revenue share on referred clients</li>
          <li>First look at acquisition/exit offers</li>
          <li>Enterprise API access included</li>
          <li>Co-marketing opportunities</li>
        </ul>
        <div class="inv-tier-roi">
          <div class="inv-tier-roi-label">Projected 3-Year ROI</div>
          <div class="inv-tier-roi-value">12x – 40x</div>
        </div>
        <button class="inv-btn inv-btn-secondary" style="width:100%;" onclick="event.stopPropagation();selectTier('strategic','25,000+')">Select Strategic Tier</button>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     9. ROADMAP TIMELINE
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section" id="inv-roadmap">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Product <span class="inv-gradient-text">Roadmap</span></h2>
    <p class="inv-section-subtitle inv-animate">
      A transparent look at where we've been, where we are, and where we're headed. 
      Milestones are updated in real-time as we ship.
    </p>

    <div class="inv-timeline inv-animate">
      <div class="inv-timeline-item completed">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q1 2024</div>
        <div class="inv-timeline-title">Platform Foundation</div>
        <div class="inv-timeline-desc">Core architecture, authentication, billing, and first 100 AI tools launched across 8 industries.</div>
        <span class="inv-timeline-status inv-status-done">Completed</span>
      </div>
      <div class="inv-timeline-item completed">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q3 2024</div>
        <div class="inv-timeline-title">Voice AI & Telephony</div>
        <div class="inv-timeline-desc">Voice AI platform with 85+ tools, IVR builder, call campaigns, and conference rooms went live.</div>
        <span class="inv-timeline-status inv-status-done">Completed</span>
      </div>
      <div class="inv-timeline-item completed">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q1 2025</div>
        <div class="inv-timeline-title">API & Developer Ecosystem</div>
        <div class="inv-timeline-desc">504+ API endpoints, 3 SDKs (Python, Node.js, PHP), webhooks, and developer documentation portal.</div>
        <span class="inv-timeline-status inv-status-done">Completed</span>
      </div>
      <div class="inv-timeline-item completed">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q3 2025</div>
        <div class="inv-timeline-title">1,220+ Tools & 35 Verticals</div>
        <div class="inv-timeline-desc">Expanded to 1,220+ AI tools spanning 89 categories, marketplace, and white-label capabilities.</div>
        <span class="inv-timeline-status inv-status-done">Completed</span>
      </div>
      <div class="inv-timeline-item completed">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q1 2026</div>
        <div class="inv-timeline-title">AI GPU Server Platform</div>
        <div class="inv-timeline-desc">Launched dedicated GPU server rental marketplace with pre-configured AI training environments.</div>
        <span class="inv-timeline-status inv-status-done">Completed</span>
      </div>
      <div class="inv-timeline-item">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q3 2026</div>
        <div class="inv-timeline-title">Enterprise & Team Plans</div>
        <div class="inv-timeline-desc">Multi-seat team management, enterprise SSO, custom AI model fine-tuning, and SLA guarantees.</div>
        <span class="inv-timeline-status inv-status-progress">In Progress</span>
      </div>
      <div class="inv-timeline-item">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q1 2027</div>
        <div class="inv-timeline-title">Mobile Apps & Offline Mode</div>
        <div class="inv-timeline-desc">Native iOS and Android apps with offline AI capabilities and edge computing support.</div>
        <span class="inv-timeline-status inv-status-planned">Planned</span>
      </div>
      <div class="inv-timeline-item">
        <div class="inv-timeline-marker"></div>
        <div class="inv-timeline-date">Q3 2027</div>
        <div class="inv-timeline-title">Series A & Global Expansion</div>
        <div class="inv-timeline-desc">Target $5M+ Series A, expand to EU and APAC markets, 50+ verticals, 2,000+ tools.</div>
        <span class="inv-timeline-status inv-status-planned">Planned</span>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     10. FOUNDER LETTER
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section inv-section-alt" id="inv-founder">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">A Letter from the <span class="inv-gradient-text">Founder</span></h2>
    <p class="inv-section-subtitle inv-animate">
      Transparency isn't a feature — it's our foundation.
    </p>

    <div class="inv-founder inv-animate">
      <div class="inv-founder-photo">
        👨‍💻
      </div>
      <div class="inv-founder-letter">
        <h3>"I built this with $50 and a vision."</h3>
        <p>
          I'm going to be radically honest with you. When I started GoSiteMe, I had $50 to my name. No venture capital. 
          No wealthy family. No safety net. Just a belief that AI should be accessible to everyone — not just 
          Fortune 500 companies with six-figure software budgets.
        </p>
        <p>
          What you see today — 1,220+ AI tools, 89 categories, a voice AI platform, 504 API endpoints, GPU servers, 
          a marketplace, and an 18,000+ file codebase — was built through relentless execution. Every line of code. 
          Every tool. Every API. Bootstrapped from nothing.
        </p>
        <p>
          I'm not raising money because I need it to survive. The platform is live, it's growing, and it's serving 
          real users. I'm raising because the right capital at the right time can compress 3 years of growth into 12 months. 
          Marketing, infrastructure, team — these are the accelerants.
        </p>
        <p>
          If you invest, you're not just buying equity. You're backing someone who has already proven they can build 
          world-class technology with almost nothing. Imagine what happens with actual resources.
        </p>
        <p>
          I send monthly updates to every investor. No vanity metrics. Real numbers, real challenges, real wins. 
          You'll know exactly where your money is going and what it's producing.
        </p>
        <div class="inv-signature">
          — The Founder, GoSiteMe<br>
          <span style="font-size:0.85rem;color:var(--inv-text-dim);font-style:normal;">Ontario, Canada · Building since 2024</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     11. TRUST & TRANSPARENCY
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section" id="inv-trust">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Trust & <span class="inv-gradient-text">Transparency</span></h2>
    <p class="inv-section-subtitle inv-animate">
      We believe investors deserve complete visibility. Here's how we earn and keep your trust.
    </p>

    <div class="inv-trust-grid inv-animate">
      <div class="inv-trust-card">
        <div class="inv-trust-card-icon">📊</div>
        <div class="inv-trust-card-title">Real-Time Dashboard</div>
        <div class="inv-trust-card-desc">Every investor gets access to our live dashboard showing revenue, user growth, expenses, and development progress in real-time.</div>
      </div>
      <div class="inv-trust-card">
        <div class="inv-trust-card-icon">📜</div>
        <div class="inv-trust-card-title">SAFE Agreements</div>
        <div class="inv-trust-card-desc">All investments are formalized through Y Combinator's SAFE (Simple Agreement for Future Equity) — the industry standard for early-stage investments.</div>
      </div>
      <div class="inv-trust-card">
        <div class="inv-trust-card-icon">🇨🇦</div>
        <div class="inv-trust-card-title">Canadian Corporation</div>
        <div class="inv-trust-card-desc">GoSiteMe is a registered Canadian corporation operating under Ontario business law. Full corporate governance and compliance.</div>
      </div>
      <div class="inv-trust-card">
        <div class="inv-trust-card-icon">📧</div>
        <div class="inv-trust-card-title">Monthly Updates</div>
        <div class="inv-trust-card-desc">Detailed monthly reports sent to every investor: revenue, expenses, MRR growth, customer count, wins, challenges, and next priorities.</div>
      </div>
      <div class="inv-trust-card">
        <div class="inv-trust-card-icon">🔐</div>
        <div class="inv-trust-card-title">Funds Usage Breakdown</div>
        <div class="inv-trust-card-desc">Every dollar tracked and reported. See exactly how investment capital is allocated across engineering, marketing, infrastructure, and operations.</div>
      </div>
      <div class="inv-trust-card">
        <div class="inv-trust-card-icon">🔄</div>
        <div class="inv-trust-card-title">30-Day Cooling Period</div>
        <div class="inv-trust-card-desc">Investments under $10,000 include a 30-day cooling-off period. We want investors who are confident, not pressured.</div>
      </div>
    </div>

    <!-- Pie Chart: Fund Allocation -->
    <h3 class="inv-section-title inv-animate" style="font-size:1.6rem;margin-bottom:40px;">How Funds Are <span class="inv-gradient-text">Allocated</span></h3>
    <div class="inv-pie-wrap inv-animate">
      <div class="inv-pie"></div>
      <ul class="inv-pie-legend">
        <li><span class="inv-pie-swatch" style="background:#00b894;"></span> Engineering & Development — 40%</li>
        <li><span class="inv-pie-swatch" style="background:#0984e3;"></span> Infrastructure & Hosting — 20%</li>
        <li><span class="inv-pie-swatch" style="background:#6c5ce7;"></span> Marketing & Growth — 15%</li>
        <li><span class="inv-pie-swatch" style="background:#e17055;"></span> Legal & Compliance — 10%</li>
        <li><span class="inv-pie-swatch" style="background:#fdcb6e;"></span> Operations & Admin — 10%</li>
        <li><span class="inv-pie-swatch" style="background:#636e72;"></span> Reserve Fund — 5%</li>
      </ul>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     12. FAQ SECTION
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section inv-section-alt" id="inv-faq">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Investor <span class="inv-gradient-text">FAQ</span></h2>
    <p class="inv-section-subtitle inv-animate">
      Common questions from potential investors. Can't find your answer? Reach out directly.
    </p>

    <div class="inv-faq-list inv-animate">
      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>What is GoSiteMe?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          GoSiteMe is Canada's most comprehensive AI platform, offering 1,220+ tools across 89 categories including legal, medical, real estate, marketing, finance, education, and more. We also provide voice AI services, web hosting, domain registration, developer APIs (504+ endpoints), SDKs, a marketplace, white-label solutions, and GPU server rentals.
        </div></div>
      </div>

      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>How do I invest in GoSiteMe?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          Select an investment tier above (Seed, Growth, or Strategic), then fill out the contact form at the bottom of this page. Our team will follow up within 24 hours to discuss terms, share the SAFE agreement, and guide you through the process. Payments are processed securely via bank transfer or Stripe.
        </div></div>
      </div>

      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>What is the minimum investment?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          Our Seed tier starts at $1,000. This makes early-stage investment accessible to a wider range of believers in our platform. Higher tiers ($5K+ and $25K+) offer additional benefits including advisory access and pro-rata rights.
        </div></div>
      </div>

      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>Is GoSiteMe a registered corporation?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          Yes. GoSiteMe is a registered Canadian corporation operating under Ontario business law. We maintain full corporate governance, proper bookkeeping, and compliance with all applicable regulations. Our corporate documents are available to investors upon request.
        </div></div>
      </div>

      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>What is a SAFE agreement?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          A Simple Agreement for Future Equity (SAFE) is an investment contract pioneered by Y Combinator. It gives investors the right to receive equity in a future financing round at a discount. SAFEs are the standard instrument used by thousands of startups worldwide because they're simple, fair, and founder/investor-friendly.
        </div></div>
      </div>

      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>How will my investment be used?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          Funds are allocated strategically: 40% to engineering and development (new tools, platform improvements), 20% to infrastructure (servers, hosting, GPU resources), 15% to marketing and growth, 10% to legal and compliance, 10% to operations, and 5% to reserve. Every dollar is tracked and reported in monthly updates.
        </div></div>
      </div>

      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>What returns can I expect?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          While returns are never guaranteed in early-stage investing, our diversified 8 revenue streams, growing platform, and massive addressable market ($200B TAM) position us for significant growth. Our conservative model projects 5-15x returns over 3 years, while aggressive projections suggest 12-40x for strategic investors. We provide full transparency through monthly reporting.
        </div></div>
      </div>

      <div class="inv-faq-item">
        <div class="inv-faq-q" onclick="toggleFaq(this)">
          <span>Can I get a refund on my investment?</span>
          <span class="inv-faq-arrow">▼</span>
        </div>
        <div class="inv-faq-a"><div class="inv-faq-a-inner">
          We offer a 30-day cooling-off period for investments under $10,000. During this period, you can request a full refund with no questions asked. After the cooling period, investments are subject to the terms of your SAFE agreement. We want investors who are confident in their decision, not pressured.
        </div></div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     13. CONTACT FORM
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-section" id="inv-contact">
  <div class="inv-container">
    <h2 class="inv-section-title inv-animate">Start Your <span class="inv-gradient-text">Investment</span></h2>
    <p class="inv-section-subtitle inv-animate">
      Choose your tier, fill out the form, and proceed to secure payment via Stripe. All major cards accepted.
    </p>

    <div class="inv-form-container inv-animate">
      <div class="inv-form-title">Secure Investment</div>
      <div class="inv-form-subtitle">All fields required. Payment processed securely via Stripe. Visa, Mastercard, Amex, Discover & more accepted.</div>

      <form id="inv-form" onsubmit="return submitInvestorForm(event)">
        <div class="inv-form-row">
          <div class="inv-form-group">
            <label class="inv-form-label" for="inv-name">Full Name</label>
            <input class="inv-form-input" type="text" id="inv-name" name="name" placeholder="Your full legal name" required>
          </div>
          <div class="inv-form-group">
            <label class="inv-form-label" for="inv-email">Email Address</label>
            <input class="inv-form-input" type="email" id="inv-email" name="email" placeholder="your@email.com" required>
          </div>
        </div>

        <div class="inv-form-row">
          <div class="inv-form-group">
            <label class="inv-form-label" for="inv-phone">Phone Number</label>
            <input class="inv-form-input" type="tel" id="inv-phone" name="phone" placeholder="+1 (555) 000-0000">
          </div>
          <div class="inv-form-group">
            <label class="inv-form-label" for="inv-company">Company / Entity (Optional)</label>
            <input class="inv-form-input" type="text" id="inv-company" name="company" placeholder="Your company name">
          </div>
        </div>

        <div class="inv-form-row">
          <div class="inv-form-group">
            <label class="inv-form-label" for="inv-tier">Investment Tier</label>
            <select class="inv-form-select" id="inv-tier" name="tier" required onchange="updateROIPreview()">
              <option value="">Select a tier...</option>
              <option value="seed">Seed — $1,000 – $4,999</option>
              <option value="growth">Growth — $5,000 – $24,999</option>
              <option value="strategic">Strategic — $25,000+</option>
            </select>
          </div>
          <div class="inv-form-group">
            <label class="inv-form-label" for="inv-amount">Investment Amount (USD)</label>
            <input class="inv-form-input" type="text" id="inv-amount" name="amount" placeholder="$5,000" required>
          </div>
        </div>

        <div id="inv-roi-box" class="inv-roi-preview">
          <div class="inv-roi-preview-label">Projected 3-Year Return Range</div>
          <div class="inv-roi-preview-value" id="inv-roi-value">—</div>
        </div>

        <div class="inv-form-group">
          <label class="inv-form-label" for="inv-how">How did you hear about GoSiteMe?</label>
          <select class="inv-form-select" id="inv-how" name="source">
            <option value="">Select...</option>
            <option value="search">Search Engine (Google)</option>
            <option value="social">Social Media</option>
            <option value="referral">Referral / Word of Mouth</option>
            <option value="press">Press / Article</option>
            <option value="product">I'm a GoSiteMe User</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="inv-form-group">
          <label class="inv-form-label" for="inv-message">Message (Optional)</label>
          <textarea class="inv-form-textarea" id="inv-message" name="message" placeholder="Tell us about yourself, your investment goals, or any questions you have..."></textarea>
        </div>

        <button type="submit" class="inv-form-submit" id="inv-submit-btn">
          <i class="fas fa-lock" style="margin-right:8px;"></i> Proceed to Secure Payment →
        </button>

        <div class="inv-payment-badges" style="display:flex;align-items:center;justify-content:center;gap:16px;margin-top:16px;flex-wrap:wrap;">
          <span style="display:inline-flex;align-items:center;gap:6px;color:var(--inv-text-muted);font-size:.85rem;"><i class="fab fa-cc-visa" style="font-size:1.6rem;color:#1A1F71;"></i></span>
          <span style="display:inline-flex;align-items:center;gap:6px;color:var(--inv-text-muted);font-size:.85rem;"><i class="fab fa-cc-mastercard" style="font-size:1.6rem;color:#EB001B;"></i></span>
          <span style="display:inline-flex;align-items:center;gap:6px;color:var(--inv-text-muted);font-size:.85rem;"><i class="fab fa-cc-amex" style="font-size:1.6rem;color:#006FCF;"></i></span>
          <span style="display:inline-flex;align-items:center;gap:6px;color:var(--inv-text-muted);font-size:.85rem;"><i class="fab fa-cc-discover" style="font-size:1.6rem;color:#FF6000;"></i></span>
          <span style="display:inline-flex;align-items:center;gap:6px;color:var(--inv-text-muted);font-size:.85rem;"><i class="fab fa-cc-stripe" style="font-size:1.6rem;color:#635BFF;"></i></span>
          <span style="color:var(--inv-text-muted);font-size:.78rem;"><i class="fas fa-shield-halved" style="color:#55efc4;"></i> 256-bit SSL</span>
        </div>
      </form>

      <div id="inv-form-msg" class="inv-form-message"></div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     14. FOOTER CTA
     ═══════════════════════════════════════════════════════════════ -->
<section class="inv-footer-cta">
  <div class="inv-container">
    <h2>Join <span class="inv-gradient-text" id="inv-footer-count">early</span> investors who believe in the future of AI</h2>
    <p>The window to invest at the earliest stage is closing. Don't miss your spot.</p>
    <a href="#inv-contact" class="inv-btn inv-btn-primary">
      <span>💎</span> Invest in GoSiteMe Now
    </a>
  </div>
</section>

</div><!-- /.inv-page -->

<!-- ═══════════════════════════════════════════════════════════════
     JAVASCRIPT — Metrics, Counters, Animations, Form, Interactions
     ═══════════════════════════════════════════════════════════════ -->
<script src="/assets/js/invest-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
