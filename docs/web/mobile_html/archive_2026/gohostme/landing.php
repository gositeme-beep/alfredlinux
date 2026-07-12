<?php
/**
 * GoHostMe — The Definitive Landing Page
 * =======================================
 * "Say goodbye to every competitor — no doubt."
 * — Commander Danny William Perez
 * 
 * Built with live data, full product catalog, and the OVH intelligence empire.
 * Every button is a direct WHMCS cart link (Stripe + PayPal live).
 */

// Live server stats (cached 5min)
$stats_cache = '/tmp/gohostme-stats.json';
$stats = null;
if (file_exists($stats_cache) && (time() - filemtime($stats_cache)) < 300) {
    $stats = json_decode(file_get_contents($stats_cache), true);
}
if (!$stats) {
    $pm2_count = 31;
    exec('pm2 jlist 2>/dev/null', $pm2_out);
    if (!empty($pm2_out)) {
        $pm2_data = json_decode(implode('', $pm2_out), true);
        if (is_array($pm2_data)) {
            $pm2_count = count(array_filter($pm2_data, fn($p) => ($p['pm2_env']['status'] ?? '') === 'online'));
        }
    }
    $uptime_raw = (float)(explode(' ', file_get_contents('/proc/uptime'))[0] ?? 0);
    $load = sys_getloadavg();
    $stats = [
        'services' => $pm2_count,
        'uptime_hours' => round($uptime_raw / 3600),
        'load' => round($load[0] ?? 0, 1),
        'products' => 101,
        'regions' => 18,
        'agents' => '11.3M+',
    ];
    @file_put_contents($stats_cache, json_encode($stats));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoHostMe — AI-Powered Cloud Platform | VPS, Dedicated, GPU, AI Voice, 18 Regions</title>
<meta name="description" content="The only platform that combines enterprise cloud hosting with AI voice agents, AI call centers, AI office automation, and 12 industry solutions. 18 global regions. VPS from $29.99/mo. GPU servers with NVIDIA A10. GoHostMe panel included free. By GoSiteMe.">
<meta name="keywords" content="cloud hosting, VPS hosting, dedicated server, GPU server, AI voice agent, AI call center, Canadian hosting, GoSiteMe, GoHostMe, NVIDIA A10, managed kubernetes">
<link rel="icon" href="/favicon.ico">
<meta property="og:title" content="GoHostMe — Not Just Hosting. Everything.">
<meta property="og:description" content="Cloud hosting + AI voice agents + AI call centers + 12 industry solutions. 18 regions. VPS from $29.99.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://gositeme.com/gohostme/">
<style>
:root {
    --bg: #0a0e1a;
    --surface: #111827;
    --surface2: #1a2236;
    --surface3: #222d42;
    --border: rgba(255,255,255,0.08);
    --border2: rgba(255,255,255,0.12);
    --text: #e4e8f0;
    --text-dim: #8892a6;
    --accent: #6366f1;
    --accent2: #818cf8;
    --accent-glow: rgba(99,102,241,0.25);
    --green: #10b981;
    --orange: #f59e0b;
    --red: #ef4444;
    --gold: #fbbf24;
    --cyan: #22d3ee;
    --purple: #a855f7;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    overflow-x: hidden;
}
a { color: var(--accent2); text-decoration: none; }
a:hover { text-decoration: underline; }

/* === TRUST TICKER === */
.trust-ticker {
    background: linear-gradient(90deg, var(--accent), #7c3aed, var(--cyan));
    padding: 8px 0;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
}
.trust-ticker-inner {
    display: inline-block;
    animation: ticker 30s linear infinite;
    font-size: 0.78rem; font-weight: 600; color: #fff;
    letter-spacing: 0.5px;
}
.trust-ticker-inner span { margin: 0 40px; }
@keyframes ticker {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* === NAV === */
.nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    background: rgba(10,14,26,0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 0 24px;
}
.nav-inner {
    max-width: 1200px; margin: 0 auto;
    display: flex; align-items: center; justify-content: space-between;
    height: 64px;
}
.nav-logo {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.25rem; font-weight: 700; color: #fff;
}
.nav-logo svg { width: 32px; height: 32px; }
.nav-links { display: flex; gap: 20px; align-items: center; }
.nav-links a { color: var(--text-dim); font-size: 0.85rem; transition: color 0.2s; }
.nav-links a:hover { color: #fff; text-decoration: none; }

/* === BUTTONS === */
.btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 8px; font-weight: 600;
    font-size: 0.9rem; transition: all 0.2s; border: none; cursor: pointer;
    text-decoration: none !important;
}
.btn-primary {
    background: var(--accent); color: #fff;
    box-shadow: 0 0 20px var(--accent-glow);
}
.btn-primary:hover { background: #5558e8; transform: translateY(-1px); }
.btn-outline {
    background: transparent; color: var(--accent2);
    border: 1px solid var(--accent); padding: 9px 21px;
}
.btn-outline:hover { background: rgba(99,102,241,0.1); }
.btn-lg { padding: 14px 32px; font-size: 1rem; border-radius: 10px; }
.btn-gold { background: linear-gradient(135deg, #f59e0b, #d97706); color: #000; font-weight: 700; }
.btn-gold:hover { transform: translateY(-1px); }
.btn-cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; }
.btn-cyan:hover { transform: translateY(-1px); }
.btn-green { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.btn-green:hover { transform: translateY(-1px); }
.btn-purple { background: linear-gradient(135deg, #a855f7, #7c3aed); color: #fff; }
.btn-purple:hover { transform: translateY(-1px); }
.btn-pulse { animation: pulse 2s infinite; }
@keyframes pulse {
    0%, 100% { box-shadow: 0 0 20px var(--accent-glow); }
    50% { box-shadow: 0 0 40px var(--accent-glow), 0 0 60px rgba(99,102,241,0.15); }
}

/* === HERO === */
.hero {
    padding: 130px 24px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute; top: -300px; left: 50%; transform: translateX(-50%);
    width: 1200px; height: 1200px;
    background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, rgba(34,211,238,0.08) 30%, rgba(168,85,247,0.05) 50%, transparent 70%);
    pointer-events: none;
}
.hero-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 20px; padding: 6px 16px; font-size: 0.78rem;
    color: var(--green); margin-bottom: 24px; font-weight: 600;
}
.hero h1 {
    font-size: clamp(2.4rem, 5.5vw, 4rem);
    font-weight: 900; line-height: 1.1;
    max-width: 950px; margin: 0 auto 24px;
    background: linear-gradient(135deg, #fff 0%, var(--accent2) 40%, var(--cyan) 70%, var(--gold) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero .subtitle {
    font-size: clamp(1rem, 2vw, 1.25rem); color: var(--text-dim);
    max-width: 750px; margin: 0 auto 40px;
    line-height: 1.7;
}
.hero-cta { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.hero-stats {
    display: flex; gap: 32px; justify-content: center; margin-top: 60px;
    flex-wrap: wrap;
}
.hero-stat { text-align: center; }
.hero-stat .num { font-size: 2rem; font-weight: 800; color: #fff; }
.hero-stat .label { font-size: 0.75rem; color: var(--text-dim); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

/* === TRUST STRIP === */
.trust-strip {
    padding: 40px 24px;
    text-align: center;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.trust-badges {
    display: flex; gap: 40px; justify-content: center; align-items: center;
    flex-wrap: wrap; max-width: 1000px; margin: 0 auto;
}
.trust-badge {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.82rem; color: var(--text-dim); font-weight: 600;
}
.trust-badge .icon { font-size: 1.4rem; }

/* === SECTIONS === */
.section {
    padding: 80px 24px;
    max-width: 1200px; margin: 0 auto;
}
.section-label {
    text-transform: uppercase; font-size: 0.72rem; font-weight: 700;
    color: var(--accent2); letter-spacing: 2px; margin-bottom: 12px;
}
.section h2 {
    font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 700;
    margin-bottom: 16px;
}
.section p.sub {
    color: var(--text-dim); max-width: 650px; margin-bottom: 48px; font-size: 1.05rem;
}

/* === MANIFESTO === */
.manifesto {
    padding: 80px 24px;
    text-align: center;
    background: linear-gradient(180deg, rgba(99,102,241,0.04) 0%, transparent 100%);
    border-top: 1px solid rgba(99,102,241,0.1);
    border-bottom: 1px solid rgba(99,102,241,0.1);
}
.manifesto h2 { font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; margin-bottom: 24px; max-width: 800px; margin-left: auto; margin-right: auto; }
.manifesto p { color: var(--text-dim); max-width: 700px; margin: 0 auto 20px; font-size: 1.05rem; line-height: 1.8; }
.manifesto-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px; max-width: 1000px; margin: 40px auto 0;
}
.manifesto-item {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px 20px;
    text-align: center;
}
.manifesto-item .num { font-size: 2rem; font-weight: 900; color: var(--accent2); }
.manifesto-item .label { font-size: 0.82rem; color: var(--text-dim); margin-top: 4px; }

/* === INFRA STATS === */
.infra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px; margin: 40px 0;
}
.infra-stat {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    transition: border-color 0.3s;
}
.infra-stat:hover { border-color: rgba(99,102,241,0.3); }
.infra-stat .num { font-size: 1.6rem; font-weight: 800; color: var(--cyan); }
.infra-stat .label { font-size: 0.7rem; color: var(--text-dim); margin-top: 6px; text-transform: uppercase; letter-spacing: 0.5px; }

/* === FEATURES === */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
    gap: 20px;
}
.feature-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 28px;
    transition: border-color 0.3s, transform 0.2s;
}
.feature-card:hover { border-color: rgba(99,102,241,0.3); transform: translateY(-2px); }
.feature-icon {
    width: 44px; height: 44px;
    background: rgba(99,102,241,0.12);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; margin-bottom: 16px;
}
.feature-card h3 { font-size: 1.05rem; margin-bottom: 8px; }
.feature-card p { font-size: 0.85rem; color: var(--text-dim); line-height: 1.5; }

/* === REGIONS === */
.region-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px; margin-top: 32px;
}
.region-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    transition: border-color 0.3s, transform 0.2s;
}
.region-card:hover { border-color: rgba(34,211,238,0.4); transform: translateY(-2px); }
.region-flag { font-size: 1.8rem; margin-bottom: 6px; }
.region-name { font-size: 0.82rem; font-weight: 600; color: #fff; }
.region-code { font-size: 0.7rem; color: var(--text-dim); margin-top: 2px; }

/* === COMPARISON === */
.comp-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.comp-table th, .comp-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--border);
    font-size: 0.85rem;
}
.comp-table th {
    background: var(--surface2);
    font-weight: 600; color: var(--text-dim);
    font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
}
.comp-table td:not(:first-child), .comp-table th:not(:first-child) { text-align: center; }
.comp-table tr:last-child td { border-bottom: none; }
.check { color: var(--green); font-weight: 700; }
.cross { color: var(--text-dim); opacity: 0.4; }
.highlight-col { background: rgba(99,102,241,0.06); }

/* === PRICING CARDS === */
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    max-width: 1100px; margin: 0 auto;
}
.price-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 32px 24px;
    text-align: center;
    position: relative;
    transition: transform 0.2s, border-color 0.3s;
}
.price-card:hover { transform: translateY(-3px); border-color: rgba(99,102,241,0.2); }
.price-card.popular {
    border-color: var(--accent);
    box-shadow: 0 0 30px var(--accent-glow);
}
.price-card.popular::before {
    content: 'Most Popular';
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: var(--accent); color: #fff;
    font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
    padding: 4px 14px; border-radius: 10px; letter-spacing: 1px;
}
.price-card .tier { font-size: 0.82rem; color: var(--accent2); font-weight: 600; margin-bottom: 4px; }
.price-card .price { font-size: 2.2rem; font-weight: 800; color: #fff; margin-bottom: 4px; }
.price-card .period { font-size: 0.78rem; color: var(--text-dim); margin-bottom: 20px; }
.price-card ul { list-style: none; text-align: left; margin-bottom: 24px; }
.price-card li {
    font-size: 0.83rem; color: var(--text-dim);
    padding: 5px 0; display: flex; align-items: center; gap: 8px;
}
.price-card li::before { content: '✓'; color: var(--green); font-weight: 700; flex-shrink: 0; }

/* === DEDICATED === */
.dedicated-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    max-width: 1200px; margin: 0 auto;
}
.dedicated-card {
    background: linear-gradient(135deg, var(--surface), var(--surface2));
    border: 1px solid rgba(251,191,36,0.15);
    border-radius: 14px;
    padding: 28px 24px;
    transition: transform 0.2s;
}
.dedicated-card:hover { transform: translateY(-3px); }
.dedicated-card h3 { font-size: 1.05rem; color: var(--gold); margin-bottom: 4px; }
.dedicated-card .price { font-size: 1.6rem; font-weight: 800; color: #fff; }
.dedicated-card .period { font-size: 0.78rem; color: var(--text-dim); margin-bottom: 14px; }
.dedicated-card ul { list-style: none; margin-bottom: 16px; }
.dedicated-card li { font-size: 0.82rem; color: var(--text-dim); padding: 4px 0; display: flex; align-items: center; gap: 8px; }
.dedicated-card li::before { content: '→'; color: var(--gold); font-weight: 700; flex-shrink: 0; }

/* === GPU === */
.gpu-section {
    background: linear-gradient(180deg, rgba(99,102,241,0.05) 0%, transparent 100%);
    border-top: 1px solid rgba(99,102,241,0.15);
    border-bottom: 1px solid rgba(99,102,241,0.15);
    padding: 80px 24px;
}
.gpu-card {
    max-width: 800px; margin: 0 auto;
    background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(34,211,238,0.05));
    border: 1px solid rgba(99,102,241,0.25);
    border-radius: 16px;
    padding: 40px;
    text-align: center;
}
.gpu-specs {
    display: flex; gap: 28px; justify-content: center; flex-wrap: wrap;
    margin: 24px 0;
}
.gpu-spec { text-align: center; }
.gpu-spec .val { font-size: 1.3rem; font-weight: 800; color: var(--cyan); }
.gpu-spec .lbl { font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; margin-top: 4px; }

/* === AI SOLUTIONS === */
.ai-section {
    background: linear-gradient(180deg, rgba(168,85,247,0.05) 0%, transparent 100%);
    border-top: 1px solid rgba(168,85,247,0.15);
    border-bottom: 1px solid rgba(168,85,247,0.15);
    padding: 80px 24px;
}
.ai-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px; max-width: 1200px; margin: 0 auto;
}
.ai-card {
    background: linear-gradient(135deg, var(--surface), rgba(168,85,247,0.04));
    border: 1px solid rgba(168,85,247,0.12);
    border-radius: 14px;
    padding: 28px;
    transition: border-color 0.3s, transform 0.2s;
}
.ai-card:hover { border-color: rgba(168,85,247,0.3); transform: translateY(-2px); }
.ai-card .ai-icon { font-size: 2rem; margin-bottom: 12px; }
.ai-card h3 { font-size: 1rem; margin-bottom: 6px; color: #fff; }
.ai-card p { font-size: 0.83rem; color: var(--text-dim); margin-bottom: 12px; line-height: 1.5; }
.ai-card .ai-price { font-size: 0.85rem; color: var(--purple); font-weight: 700; margin-bottom: 12px; }

/* === INDUSTRY === */
.industry-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px; max-width: 1200px; margin: 0 auto;
}
.industry-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    transition: border-color 0.3s, transform 0.2s;
}
.industry-card:hover { border-color: rgba(251,191,36,0.3); transform: translateY(-2px); }
.industry-icon { font-size: 1.8rem; margin-bottom: 8px; }
.industry-card h4 { font-size: 0.88rem; color: #fff; margin-bottom: 4px; }
.industry-card .industry-price { font-size: 0.78rem; color: var(--gold); font-weight: 600; }

/* === SERVICES === */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.service-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px;
    text-align: center;
    transition: border-color 0.3s;
}
.service-card:hover { border-color: rgba(99,102,241,0.3); }
.service-icon { font-size: 1.8rem; margin-bottom: 10px; }
.service-card h4 { font-size: 0.9rem; margin-bottom: 5px; color: #fff; }
.service-card p { font-size: 0.78rem; color: var(--text-dim); }

/* === SECURITY === */
.security-section {
    background: linear-gradient(180deg, rgba(16,185,129,0.04) 0%, transparent 100%);
    border-top: 1px solid rgba(16,185,129,0.15);
    border-bottom: 1px solid rgba(16,185,129,0.15);
    padding: 80px 24px;
}
.security-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px; max-width: 1000px; margin: 0 auto;
}
.security-item {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 16px;
}
.security-icon { font-size: 1.5rem; flex-shrink: 0; margin-top: 2px; }
.security-item h4 { font-size: 0.92rem; color: #fff; margin-bottom: 4px; }
.security-item p { font-size: 0.82rem; color: var(--text-dim); }

/* === GUARANTEE === */
.guarantee-section {
    text-align: center;
    padding: 60px 24px;
    background: var(--surface);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.guarantee-grid {
    display: flex; gap: 40px; justify-content: center; flex-wrap: wrap;
    max-width: 900px; margin: 32px auto 0;
}
.guarantee-item { text-align: center; }
.guarantee-item .icon { font-size: 2.5rem; margin-bottom: 8px; }
.guarantee-item h4 { font-size: 0.95rem; color: #fff; margin-bottom: 4px; }
.guarantee-item p { font-size: 0.8rem; color: var(--text-dim); max-width: 180px; }

/* === API PREVIEW === */
.api-section { padding: 80px 24px; max-width: 1000px; margin: 0 auto; }
.code-block {
    background: #0d1117;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    padding: 24px;
    overflow-x: auto;
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    font-size: 0.82rem;
    line-height: 1.7;
    color: #c9d1d9;
    margin: 20px 0;
}
.code-block .comment { color: #8b949e; }
.code-block .keyword { color: #ff7b72; }
.code-block .string { color: #a5d6ff; }
.code-block .func { color: #d2a8ff; }
.code-block .const { color: #79c0ff; }

/* === FAQ === */
.faq-list { max-width: 800px; margin: 0 auto; }
.faq-item {
    border-bottom: 1px solid var(--border);
    padding: 20px 0;
}
.faq-q {
    font-size: 1rem; font-weight: 600; color: #fff;
    cursor: pointer; display: flex; justify-content: space-between; align-items: center;
}
.faq-q::after { content: '+'; font-size: 1.3rem; color: var(--accent2); transition: transform 0.3s; }
.faq-item.open .faq-q::after { transform: rotate(45deg); }
.faq-a {
    max-height: 0; overflow: hidden; transition: max-height 0.3s ease;
    color: var(--text-dim); font-size: 0.9rem; line-height: 1.7;
}
.faq-item.open .faq-a { max-height: 300px; padding-top: 12px; }

/* === ECOSYSTEM === */
.eco-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px; max-width: 1000px; margin: 32px auto 0;
}
.eco-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 28px;
    text-align: center;
    transition: border-color 0.3s, transform 0.2s;
}
.eco-card:hover { border-color: rgba(99,102,241,0.3); transform: translateY(-2px); }
.eco-card .eco-icon { font-size: 2.2rem; margin-bottom: 12px; }
.eco-card h4 { font-size: 1rem; color: #fff; margin-bottom: 6px; }
.eco-card p { font-size: 0.82rem; color: var(--text-dim); margin-bottom: 12px; }

/* === CTA === */
.cta-section {
    text-align: center;
    padding: 80px 24px;
    position: relative;
}
.cta-section::before {
    content: '';
    position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
    width: 800px; height: 500px;
    background: radial-gradient(circle, var(--accent-glow) 0%, rgba(168,85,247,0.08) 30%, transparent 70%);
    pointer-events: none;
}
.cta-section h2 { font-size: clamp(1.8rem, 3vw, 2.4rem); font-weight: 800; margin-bottom: 16px; }
.cta-section p { color: var(--text-dim); max-width: 650px; margin: 0 auto 32px; font-size: 1.05rem; }

/* === GREEN BADGE === */
.green-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);
    border-radius: 20px; padding: 6px 16px; font-size: 0.78rem;
    color: var(--green); font-weight: 600;
}

/* === FOOTER === */
.footer {
    border-top: 1px solid var(--border);
    padding: 40px 24px;
    text-align: center;
    font-size: 0.78rem;
    color: var(--text-dim);
}
.footer-links { display: flex; gap: 20px; justify-content: center; margin-bottom: 16px; flex-wrap: wrap; }
.footer-links a { color: var(--text-dim); }
.footer-links a:hover { color: #fff; }
.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px; max-width: 1000px; margin: 0 auto 24px;
    text-align: left;
}
.footer-grid h5 { color: #fff; font-size: 0.82rem; margin-bottom: 8px; }
.footer-grid a { display: block; color: var(--text-dim); font-size: 0.78rem; padding: 2px 0; }
.footer-grid a:hover { color: #fff; text-decoration: none; }

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .nav-links { display: none; }
    .hero-stats { gap: 16px; }
    .hero-stat .num { font-size: 1.4rem; }
    .features-grid, .ai-grid { grid-template-columns: 1fr; }
    .comp-table { font-size: 0.75rem; }
    .comp-table th, .comp-table td { padding: 8px 10px; }
    .pricing-grid { grid-template-columns: 1fr; max-width: 360px; }
    .dedicated-grid { grid-template-columns: 1fr; }
    .region-grid { grid-template-columns: repeat(3, 1fr); }
    .gpu-specs { gap: 16px; }
    .infra-grid { grid-template-columns: repeat(3, 1fr); }
    .trust-badges { gap: 20px; }
    .manifesto-grid { grid-template-columns: repeat(2, 1fr); }
    .industry-grid { grid-template-columns: repeat(2, 1fr); }
    .guarantee-grid { gap: 24px; }
    .eco-grid { grid-template-columns: 1fr; max-width: 300px; }
    .footer-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>


<!-- ============================================================ -->
<!-- TRUST TICKER -->
<!-- ============================================================ -->
<div class="trust-ticker">
    <div class="trust-ticker-inner">
        <span>&#9889; <?= $stats['services'] ?> SERVICES ONLINE</span>
        <span>&#127758; 18 GLOBAL REGIONS</span>
        <span>&#128274; ENTERPRISE DDoS PROTECTION</span>
        <span>&#128994; <?= $stats['products'] ?>+ PRODUCTS AVAILABLE</span>
        <span>&#127807; POWERED BY HYDROELECTRICITY</span>
        <span>&#128176; STRIPE &amp; PAYPAL ACCEPTED</span>
        <span>&#129302; <?= $stats['agents'] ?> AI AGENTS IN FLEET</span>
        <span>&#128737; 99.99% UPTIME SLA</span>
        <!-- duplicate for seamless loop -->
        <span>&#9889; <?= $stats['services'] ?> SERVICES ONLINE</span>
        <span>&#127758; 18 GLOBAL REGIONS</span>
        <span>&#128274; ENTERPRISE DDoS PROTECTION</span>
        <span>&#128994; <?= $stats['products'] ?>+ PRODUCTS AVAILABLE</span>
        <span>&#127807; POWERED BY HYDROELECTRICITY</span>
        <span>&#128176; STRIPE &amp; PAYPAL ACCEPTED</span>
        <span>&#129302; <?= $stats['agents'] ?> AI AGENTS IN FLEET</span>
        <span>&#128737; 99.99% UPTIME SLA</span>
    </div>
</div>


<!-- ============================================================ -->
<!-- NAV -->
<!-- ============================================================ -->
<nav class="nav">
<div class="nav-inner">
    <div class="nav-logo">
        <svg viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="#6366f1"/><path d="M8 16h4v8H8zM14 10h4v14h-4zM20 13h4v11h-4z" fill="#fff"/></svg>
        GoHostMe
    </div>
    <div class="nav-links">
        <a href="#infrastructure">Infrastructure</a>
        <a href="#ai-solutions">AI Solutions</a>
        <a href="#pricing">VPS</a>
        <a href="#dedicated">Dedicated</a>
        <a href="#gpu">GPU</a>
        <a href="#industries">Industries</a>
        <a href="/gohostme/products">Store</a>
        <a href="/gohostme/dashboard" style="color:var(--accent-cyan)">Dashboard</a>
        <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary">Deploy Now</a>
    </div>
</div>
</nav>


<!-- ============================================================ -->
<!-- HERO -->
<!-- ============================================================ -->
<section class="hero">
    <div class="hero-eyebrow">&#128308; The Only Platform That Does All Of This</div>
    <h1>Cloud Hosting. AI Voice Agents. AI Call Centers. 12 Industry Solutions. One Platform.</h1>
    <p class="subtitle">GoHostMe isn't just another hosting provider. We combine enterprise cloud infrastructure across 18 global regions with AI voice agents, AI call centers, AI office automation, and turn-key industry solutions — all managed from one AI-native control panel. Powered by Canadian hydroelectricity. Pay with Stripe or PayPal.</p>
    <div class="hero-cta">
        <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary btn-lg btn-pulse">Deploy VPS &mdash; $59.99/mo &rarr;</a>
        <a href="#ai-solutions" class="btn btn-purple btn-lg">AI Solutions &rarr;</a>
        <a href="#pricing" class="btn btn-outline btn-lg">View All Plans</a>
    </div>
    <div class="hero-stats">
        <div class="hero-stat"><div class="num">18</div><div class="label">Global Regions</div></div>
        <div class="hero-stat"><div class="num">1,440</div><div class="label">VM Configurations</div></div>
        <div class="hero-stat"><div class="num">101+</div><div class="label">Products</div></div>
        <div class="hero-stat"><div class="num">442</div><div class="label">OS Images</div></div>
        <div class="hero-stat"><div class="num">5.76 PB</div><div class="label">Storage Capacity</div></div>
        <div class="hero-stat"><div class="num">$0</div><div class="label">Panel License</div></div>
    </div>
</section>


<!-- ============================================================ -->
<!-- TRUST STRIP -->
<!-- ============================================================ -->
<div class="trust-strip">
    <div class="trust-badges">
        <div class="trust-badge"><span class="icon">&#128274;</span> 256-bit SSL Encryption</div>
        <div class="trust-badge"><span class="icon">&#128737;</span> 99.99% Uptime SLA</div>
        <div class="trust-badge"><span class="icon">&#127807;</span> Green Hydroelectric Power</div>
        <div class="trust-badge"><span class="icon">&#128176;</span> Stripe &amp; PayPal</div>
        <div class="trust-badge"><span class="icon">&#129302;</span> AI-Native Platform</div>
        <div class="trust-badge"><span class="icon">&#127464;&#127462;</span> Canadian Infrastructure</div>
    </div>
</div>


<!-- ============================================================ -->
<!-- MANIFESTO — "NOT JUST HOSTING" -->
<!-- ============================================================ -->
<section class="manifesto">
    <div class="section-label">Why We're Different</div>
    <h2>They Sell Hosting.<br>We Sell the Future of Business.</h2>
    <p>Every other hosting company gives you a server and says "good luck." We give you a server with an AI receptionist, an AI sales agent, an AI call center, an AI bookkeeper, industry-specific automation, and a control panel that manages it all. No other platform on Earth does this.</p>
    <div class="manifesto-grid">
        <div class="manifesto-item"><div class="num">27</div><div class="label">Product Categories</div></div>
        <div class="manifesto-item"><div class="num">101+</div><div class="label">Products &amp; Services</div></div>
        <div class="manifesto-item"><div class="num">12</div><div class="label">Industry Solutions</div></div>
        <div class="manifesto-item"><div class="num">18</div><div class="label">Global Regions</div></div>
        <div class="manifesto-item"><div class="num">36,864</div><div class="label">CPU Cores Available</div></div>
    </div>
</section>


<!-- ============================================================ -->
<!-- INFRASTRUCTURE -->
<!-- ============================================================ -->
<section class="section" id="infrastructure">
    <div class="section-label">Infrastructure</div>
    <h2>Enterprise-Grade Cloud Infrastructure</h2>
    <p class="sub">Tier 3+ datacenters across North America and Europe. Real bare-metal, real NVMe, real 10 Gbps networking. Not resold commodity VMs.</p>

    <div class="infra-grid">
        <div class="infra-stat"><div class="num">36,864</div><div class="label">CPU Cores</div></div>
        <div class="infra-stat"><div class="num">288 TB</div><div class="label">Total RAM</div></div>
        <div class="infra-stat"><div class="num">5.76 PB</div><div class="label">Total Storage</div></div>
        <div class="infra-stat"><div class="num">10 Gbps</div><div class="label">Port Capacity</div></div>
        <div class="infra-stat"><div class="num">2,400</div><div class="label">Floating IPs / Region</div></div>
        <div class="infra-stat"><div class="num">100</div><div class="label">Load Balancers / Region</div></div>
        <div class="infra-stat"><div class="num">4,000</div><div class="label">Networks / Region</div></div>
        <div class="infra-stat"><div class="num">16,000</div><div class="label">Secrets (KMS) / Region</div></div>
    </div>

    <div style="text-align:center; margin-top:32px;">
        <span class="green-badge">&#9889; Powered by Hydroelectricity &mdash; Beauharnois, Qu&eacute;bec, Canada</span>
    </div>
</section>


<!-- ============================================================ -->
<!-- FEATURES -->
<!-- ============================================================ -->
<section class="section" id="features">
    <div class="section-label">GoHostMe Panel</div>
    <h2>Included Free With Every Plan</h2>
    <p class="sub">The GoHostMe AI control panel comes free with every VPS, dedicated server, and GPU instance. No license fees. No per-addon charges. Ever.</p>
    <div class="features-grid">
        <div class="feature-card"><div class="feature-icon">&#127760;</div><h3>Domain Management</h3><p>Full DNS zone editing, DNSSEC, domain aliases, bulk operations.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128231;</div><h3>Email Server</h3><p>SPF, DKIM, DMARC, autoresponders, spam filters, webmail. Full stack.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128274;</div><h3>Free SSL &mdash; Every Domain</h3><p>Let's Encrypt auto-renewal, custom certs, SNI, wildcard support.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128450;</div><h3>Multi-Database Admin</h3><p>MySQL, MariaDB, PostgreSQL, MongoDB, Redis. phpMyAdmin included.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128260;</div><h3>S3-Compatible Backups</h3><p>Automated daily backups to object storage. One-click restore. Archive tier.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128640;</div><h3>Git Deployment</h3><p>Webhooks, auto-deploy on push, branch environments, instant rollback.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128736;</div><h3>Real-Time Monitoring</h3><p>CPU, RAM, disk, network dashboards. Health checks. Email + webhook alerts.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128272;</div><h3>Firewall &amp; DDoS</h3><p>Enterprise DDoS mitigation, CSF/LFD, IP blocking, brute-force protection, 2FA.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128204;</div><h3>AI Migration Wizard</h3><p>Migrate from cPanel, Plesk, DirectAdmin, or any SSH server. Zero downtime.</p></div>
        <div class="feature-card"><div class="feature-icon">&#129302;</div><h3>266+ REST API Endpoints</h3><p>Full API coverage. Automate everything. CLI, SDK, and webhook support.</p></div>
        <div class="feature-card"><div class="feature-icon">&#128101;</div><h3>Multi-Server Clustering</h3><p>Manage multiple servers from one panel. Load balancing and DNS failover.</p></div>
        <div class="feature-card"><div class="feature-icon">&#129497;</div><h3>Managed Kubernetes</h3><p>Free control plane. Auto-scaling workers. Zero-downtime rolling deploys.</p></div>
    </div>
</section>


<!-- ============================================================ -->
<!-- GLOBAL REGIONS -->
<!-- ============================================================ -->
<section class="section" id="regions" style="padding-bottom:40px;">
    <div class="section-label">Global Network</div>
    <h2>18 Regions Across 3 Continents</h2>
    <p class="sub">Deploy where your users are. Every region has full VPS, storage, networking, GPU, and Kubernetes capability. vRack private networking connects them all.</p>
    <div class="region-grid">
        <div class="region-card"><div class="region-flag">&#127464;&#127462;</div><div class="region-name">Beauharnois</div><div class="region-code">BHS &bull; QC, Canada</div></div>
        <div class="region-card"><div class="region-flag">&#127464;&#127462;</div><div class="region-name">Toronto</div><div class="region-code">CA-EAST-TOR</div></div>
        <div class="region-card"><div class="region-flag">&#127467;&#127479;</div><div class="region-name">Gravelines</div><div class="region-code">GRA &bull; France</div></div>
        <div class="region-card"><div class="region-flag">&#127467;&#127479;</div><div class="region-name">Roubaix</div><div class="region-code">RBX &bull; France</div></div>
        <div class="region-card"><div class="region-flag">&#127467;&#127479;</div><div class="region-name">Strasbourg</div><div class="region-code">SBG &bull; France</div></div>
        <div class="region-card"><div class="region-flag">&#127467;&#127479;</div><div class="region-name">Paris</div><div class="region-code">EU-WEST-PAR</div></div>
        <div class="region-card"><div class="region-flag">&#127465;&#127466;</div><div class="region-name">Frankfurt</div><div class="region-code">DE1 &bull; Germany</div></div>
        <div class="region-card"><div class="region-flag">&#127468;&#127463;</div><div class="region-name">London</div><div class="region-code">UK1 &bull; UK</div></div>
        <div class="region-card"><div class="region-flag">&#127477;&#127473;</div><div class="region-name">Warsaw</div><div class="region-code">WAW1 &bull; Poland</div></div>
        <div class="region-card"><div class="region-flag">&#127470;&#127481;</div><div class="region-name">Milan</div><div class="region-code">EU-SOUTH-MIL &bull; Italy</div></div>
    </div>
    <div style="text-align:center; margin-top:24px; color:var(--text-dim); font-size:0.85rem;">
        + 8 additional regions &bull; <strong style="color:#fff;">800 VMs per region</strong> &bull; vRack private network across all regions (free)
    </div>
</section>


<!-- ============================================================ -->
<!-- COMPARISON TABLE -->
<!-- ============================================================ -->
<section style="padding:60px 24px; max-width:1100px; margin:0 auto;" id="compare">
    <div class="section-label">Head-to-Head</div>
    <h2>GoHostMe vs. Everyone Else</h2>
    <p class="sub" style="margin-bottom:32px;">We didn't come to compete. We came to replace.</p>
    <table class="comp-table">
        <thead><tr>
            <th>Capability</th>
            <th>cPanel</th>
            <th>DigitalOcean</th>
            <th>AWS</th>
            <th>Hetzner</th>
            <th class="highlight-col">GoHostMe</th>
        </tr></thead>
        <tbody>
            <tr><td>AI Control Panel</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>AI Voice Agents</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>AI Call Center</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>AI Office Suite</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Industry Solutions (12)</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>GPU Servers (NVIDIA A10)</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="check">&#10003;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Managed Kubernetes</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="check">&#10003;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Global Regions (10+)</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="check">&#10003;</td><td class="cross">3</td><td class="highlight-col check">18</td></tr>
            <tr><td>Free DDoS Protection</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td>Extra $</td><td class="check">&#10003;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Free Private Network</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td>Extra $</td><td class="check">&#10003;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Free SSL (All Domains)</td><td class="check">&#10003;</td><td class="cross">&mdash;</td><td>Extra $</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Unlimited Bandwidth</td><td class="cross">&mdash;</td><td class="cross">Capped</td><td class="cross">Per GB</td><td class="check">&#10003;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>White-Label Reseller</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Panel License Fee</td><td>$15+/mo</td><td>N/A</td><td>N/A</td><td>N/A</td><td class="highlight-col"><strong style="color:var(--green)">$0</strong></td></tr>
            <tr><td>Green Energy</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="highlight-col check">&#10003;</td></tr>
        </tbody>
    </table>
    <p style="text-align:center; margin-top:20px; color:var(--text-dim); font-size:0.85rem;">
        <strong style="color:var(--green);">15 for 15.</strong> GoHostMe is the only platform that checks every box. The competition can't even come close.
    </p>
</section>


<!-- ============================================================ -->
<!-- AI SOLUTIONS — THE DIFFERENTIATOR -->
<!-- ============================================================ -->
<section class="ai-section" id="ai-solutions">
    <div style="max-width:1200px; margin:0 auto;">
        <div class="section-label" style="text-align:center;">What No Competitor Has</div>
        <h2 style="text-align:center; font-size:clamp(1.8rem,3.5vw,2.4rem); font-weight:700; margin-bottom:12px;">AI-Powered Business Solutions</h2>
        <p style="text-align:center; color:var(--text-dim); max-width:650px; margin:0 auto 40px; font-size:1rem;">No other hosting provider on Earth offers AI voice agents, AI call centers, AI office automation, and turn-key industry packages. This is what makes us different.</p>

        <div class="ai-grid">
            <!-- AI VOICE AGENTS -->
            <div class="ai-card">
                <div class="ai-icon">&#128266;</div>
                <h3>AI Voice Agents</h3>
                <p>Deploy AI-powered phone agents that answer calls, book appointments, qualify leads, and handle customer service — 24/7, in any language.</p>
                <div class="ai-price">From $29/mo &mdash; Enterprise $499/mo</div>
                <a href="https://gositeme.com/cart?a=add&pid=49" class="btn btn-purple" style="width:100%;justify-content:center;">Get Voice Agent</a>
            </div>

            <!-- AI CALL CENTER -->
            <div class="ai-card">
                <div class="ai-icon">&#128222;</div>
                <h3>AI Call Center</h3>
                <p>Full AI-powered outbound dialer, inbound call center, appointment setter, and collections agent. Replace entire call center teams with AI.</p>
                <div class="ai-price">From $99/mo &mdash; Enterprise $999/mo</div>
                <a href="https://gositeme.com/cart?a=add&pid=53" class="btn btn-purple" style="width:100%;justify-content:center;">Get Call Center</a>
            </div>

            <!-- AI OFFICE SUITE -->
            <div class="ai-card">
                <div class="ai-icon">&#128188;</div>
                <h3>AI Office Suite</h3>
                <p>AI Virtual Receptionist, AI Executive Assistant, AI Customer Service Desk, AI Bookkeeper, AI Sales Agent. Automate your entire office.</p>
                <div class="ai-price">From $39/mo &mdash; Sales Agent $199/mo</div>
                <a href="https://gositeme.com/cart?a=add&pid=70" class="btn btn-purple" style="width:100%;justify-content:center;">Get Office Suite</a>
            </div>

            <!-- AI SMS & CHAT -->
            <div class="ai-card">
                <div class="ai-icon">&#128172;</div>
                <h3>AI SMS &amp; Chat Agents</h3>
                <p>AI-powered live chat widget for your website and SMS agents that handle conversations, qualify leads, and close sales via text.</p>
                <div class="ai-price">From $19/mo &mdash; Business $79/mo</div>
                <a href="https://gositeme.com/cart?a=add&pid=77" class="btn btn-purple" style="width:100%;justify-content:center;">Get Chat Agent</a>
            </div>

            <!-- AI DOCUMENT & FAX -->
            <div class="ai-card">
                <div class="ai-icon">&#128196;</div>
                <h3>AI Document &amp; Fax</h3>
                <p>AI-powered document generation, e-signatures, and cloud fax service. Create contracts, proposals, and invoices automatically.</p>
                <div class="ai-price">From $14.99/mo &mdash; Pro $49/mo</div>
                <a href="https://gositeme.com/cart?a=add&pid=65" class="btn btn-purple" style="width:100%;justify-content:center;">Get Documents</a>
            </div>

            <!-- PHONE NUMBERS -->
            <div class="ai-card">
                <div class="ai-icon">&#128241;</div>
                <h3>Phone Numbers &amp; SIP</h3>
                <p>Local, toll-free, vanity, and international phone numbers. SIP trunks for BYO carrier. Number bundles for call centers.</p>
                <div class="ai-price">From $3/mo &mdash; Bundle $25/mo</div>
                <a href="https://gositeme.com/cart?a=add&pid=59" class="btn btn-purple" style="width:100%;justify-content:center;">Get Number</a>
            </div>
        </div>

        <div style="text-align:center; margin-top:36px;">
            <a href="/gohostme/products" class="btn btn-outline btn-lg">Browse All AI Solutions &rarr;</a>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- VPS PRICING -->
<!-- ============================================================ -->
<section class="section" id="pricing">
    <div class="section-label">Cloud VPS</div>
    <h2>Cloud VPS &mdash; GoHostMe Panel Included Free</h2>
    <p class="sub">NVMe SSD, unlimited bandwidth, DDoS protection, free SSL, S3 backups, GoHostMe AI panel. No hidden fees. Annual discounts available.</p>
    <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="price-card">
            <div class="tier">VPS Starter</div>
            <div class="price">$29.99</div>
            <div class="period">per month</div>
            <ul>
                <li>2 vCPUs</li>
                <li>4 GB RAM</li>
                <li>80 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>DDoS protection</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=101" class="btn btn-outline" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
        <div class="price-card popular">
            <div class="tier">VPS Business</div>
            <div class="price">$59.99</div>
            <div class="period">per month</div>
            <ul>
                <li>4 vCPUs</li>
                <li>8 GB RAM</li>
                <li>160 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>AI migration wizard</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
        <div class="price-card">
            <div class="tier">VPS Pro</div>
            <div class="price">$99.99</div>
            <div class="period">per month</div>
            <ul>
                <li>8 vCPUs</li>
                <li>16 GB RAM</li>
                <li>320 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>Monitoring &amp; alerts</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=103" class="btn btn-outline" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
        <div class="price-card">
            <div class="tier">VPS Enterprise</div>
            <div class="price">$179.99</div>
            <div class="period">per month</div>
            <ul>
                <li>16 vCPUs</li>
                <li>32 GB RAM</li>
                <li>640 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>White-label ready</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=104" class="btn btn-outline" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
    </div>
    <p style="text-align:center; margin-top:20px; font-size:0.83rem; color:var(--text-dim);">
        All plans include: 442 OS images &bull; IPv4 + IPv6 &bull; 10 Gbps port &bull; vRack private networking &bull;
        <a href="/gohostme/products">annual discounts available</a>
    </p>
</section>


<!-- ============================================================ -->
<!-- DEDICATED SERVERS -->
<!-- ============================================================ -->
<section class="section" id="dedicated">
    <div class="section-label">Dedicated Servers</div>
    <h2>Bare-Metal Dedicated Servers</h2>
    <p class="sub">Full root access to real hardware. Intel Xeon / AMD EPYC, ECC RAM, NVMe SSD. No noisy neighbors. Full IPMI/KVM remote console access.</p>
    <div class="dedicated-grid">
        <div class="dedicated-card">
            <h3>Dedicated Starter</h3>
            <div class="price">$149.99</div><div class="period">per month</div>
            <ul>
                <li>Intel Xeon E-2274G (4C/8T)</li>
                <li>32 GB DDR4 ECC</li>
                <li>2&times; 500 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>Full IPMI/KVM access</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=105" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
        <div class="dedicated-card">
            <h3>Dedicated Business</h3>
            <div class="price">$249.99</div><div class="period">per month</div>
            <ul>
                <li>Intel Xeon E-2386G (6C/12T)</li>
                <li>64 GB DDR4 ECC</li>
                <li>2&times; 1 TB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>256 failover IPs</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=106" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
        <div class="dedicated-card">
            <h3>Dedicated Pro</h3>
            <div class="price">$399.99</div><div class="period">per month</div>
            <ul>
                <li>AMD EPYC / Xeon Silver</li>
                <li>128 GB DDR4 ECC</li>
                <li>2&times; 2 TB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>Hardware RAID</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=107" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
        <div class="dedicated-card">
            <h3>Dedicated Enterprise</h3>
            <div class="price">$699.99</div><div class="period">per month</div>
            <ul>
                <li>Dual Xeon Gold / EPYC</li>
                <li>256 GB DDR4 ECC</li>
                <li>4&times; 2 TB NVMe SSD</li>
                <li>10 Gbps unmetered</li>
                <li>Redundant power</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=108" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- GPU SERVERS -->
<!-- ============================================================ -->
<section class="gpu-section" id="gpu">
    <div style="max-width:1200px; margin:0 auto;">
        <div class="section-label" style="text-align:center;">AI &amp; Machine Learning</div>
        <h2 style="text-align:center; font-size:clamp(1.6rem,3vw,2.2rem); font-weight:700; margin-bottom:32px;">GPU Servers &mdash; NVIDIA A10 Tensor Core</h2>
        <div class="gpu-card">
            <h3 style="font-size:1.4rem; color:#fff; margin-bottom:8px;">Purpose-Built for AI/ML Workloads</h3>
            <div style="color:var(--green); font-size:1rem; font-weight:700; margin-bottom:20px;">NVIDIA A10 &mdash; 24 GB VRAM per GPU</div>
            <p style="color:var(--text-dim); max-width:500px; margin:0 auto 24px; font-size:0.9rem;">Train LLMs, run Stable Diffusion, power real-time AI agents, render 3D. Available across multiple global regions.</p>
            <div class="gpu-specs">
                <div class="gpu-spec"><div class="val">30&ndash;120</div><div class="lbl">vCPUs</div></div>
                <div class="gpu-spec"><div class="val">45&ndash;180</div><div class="lbl">GB RAM</div></div>
                <div class="gpu-spec"><div class="val">1&ndash;4</div><div class="lbl">GPUs</div></div>
                <div class="gpu-spec"><div class="val">31.2</div><div class="lbl">TFLOPS FP32</div></div>
                <div class="gpu-spec"><div class="val">24 GB</div><div class="lbl">VRAM / GPU</div></div>
            </div>
            <div style="margin-top:28px;">
                <a href="https://gositeme.com/cart?a=add&pid=109" class="btn btn-primary btn-lg btn-pulse">GPU Server &mdash; from $2,499/mo &rarr;</a>
            </div>
            <p style="color:var(--text-dim); font-size:0.78rem; margin-top:14px;">LLM training &bull; Stable Diffusion &bull; AI voice agents &bull; Computer vision &bull; Scientific computing</p>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- INDUSTRY SOLUTIONS -->
<!-- ============================================================ -->
<section class="section" id="industries">
    <div class="section-label">Industry Solutions</div>
    <h2>Turn-Key AI Packages for 12 Industries</h2>
    <p class="sub">Pre-configured AI agents, voice bots, and automation tailored to your specific industry. Deploy in minutes, not months.</p>

    <div class="industry-grid">
        <div class="industry-card">
            <div class="industry-icon">&#127973;</div>
            <h4>Restaurant</h4>
            <div class="industry-price">$99/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=78" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#127968;</div>
            <h4>Real Estate</h4>
            <div class="industry-price">$149/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=79" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#127973;</div>
            <h4>Medical &amp; Dental</h4>
            <div class="industry-price">$249/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=80" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#9878;</div>
            <h4>Legal</h4>
            <div class="industry-price">$199/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=81" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#128295;</div>
            <h4>Home Services</h4>
            <div class="industry-price">$79/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=82" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#128663;</div>
            <h4>Insurance</h4>
            <div class="industry-price">$179/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=83" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#128663;</div>
            <h4>Automotive</h4>
            <div class="industry-price">$149/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=84" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#128135;</div>
            <h4>Salon &amp; Spa</h4>
            <div class="industry-price">$59/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=85" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#127970;</div>
            <h4>Property Mgmt</h4>
            <div class="industry-price">$129/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=86" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#128722;</div>
            <h4>E-Commerce</h4>
            <div class="industry-price">$99/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=87" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#128200;</div>
            <h4>Accounting &amp; Tax</h4>
            <div class="industry-price">$129/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=88" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
        <div class="industry-card">
            <div class="industry-icon">&#128170;</div>
            <h4>Fitness &amp; Gym</h4>
            <div class="industry-price">$59/mo</div>
            <a href="https://gositeme.com/cart?a=add&pid=89" style="font-size:0.75rem;">Order &rarr;</a>
        </div>
    </div>
    <p style="text-align:center; margin-top:24px;">
        <a href="/gohostme/products" class="btn btn-outline">View All Industry Solutions &rarr;</a>
    </p>
</section>


<!-- ============================================================ -->
<!-- MANAGED SERVICES & ADD-ONS -->
<!-- ============================================================ -->
<section class="section" id="services">
    <div class="section-label">Cloud Services</div>
    <h2>Managed Services &amp; Add-Ons</h2>
    <p class="sub">Scale beyond a single server with managed cloud services integrated into your GoHostMe panel.</p>
    <div class="services-grid">
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=124" style="color:inherit;text-decoration:none"><div class="service-icon">&#128451;</div><h4>Object Storage (S3)</h4><p>From $14.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=123" style="color:inherit;text-decoration:none"><div class="service-icon">&#9881;</div><h4>Managed Kubernetes</h4><p>From $49.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=125" style="color:inherit;text-decoration:none"><div class="service-icon">&#128451;</div><h4>Managed Databases</h4><p>PostgreSQL, MySQL, MongoDB, Redis</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=116" style="color:inherit;text-decoration:none"><div class="service-icon">&#128272;</div><h4>DDoS Protection</h4><p>From $49.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=114" style="color:inherit;text-decoration:none"><div class="service-icon">&#9878;</div><h4>Load Balancers</h4><p>From $29.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=117" style="color:inherit;text-decoration:none"><div class="service-icon">&#128268;</div><h4>vRack Private Net</h4><p>Free — included</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=113" style="color:inherit;text-decoration:none"><div class="service-icon">&#128225;</div><h4>Floating IPs</h4><p>From $9.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=115" style="color:inherit;text-decoration:none"><div class="service-icon">&#128737;</div><h4>Managed DNS</h4><p>From $9.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=126" style="color:inherit;text-decoration:none"><div class="service-icon">&#128231;</div><h4>Email Hosting</h4><p>From $4.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=118" style="color:inherit;text-decoration:none"><div class="service-icon">&#128260;</div><h4>Backup Pro</h4><p>From $9.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=121" style="color:inherit;text-decoration:none"><div class="service-icon">&#128202;</div><h4>Monitoring Pro</h4><p>From $4.99/mo</p></a></div>
        <div class="service-card"><a href="https://gositeme.com/cart?a=add&pid=110" style="color:inherit;text-decoration:none"><div class="service-icon">&#128273;</div><h4>VPN</h4><p>From $4.99/mo</p></a></div>
    </div>
</section>


<!-- ============================================================ -->
<!-- RESELLER -->
<!-- ============================================================ -->
<section class="section" id="reseller" style="text-align:center;">
    <div class="section-label">Reseller &amp; White-Label</div>
    <h2>Build Your Own Hosting &amp; AI Company</h2>
    <p class="sub" style="margin:0 auto 32px;">White-label the entire GoHostMe + AI platform under your brand. Resell VPS, dedicated servers, AI voice agents, and industry solutions.</p>
    <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); max-width:900px;">
        <div class="price-card">
            <div class="tier">Reseller Bronze</div>
            <div class="price">$399</div><div class="period">per month</div>
            <ul>
                <li>Up to 50 clients</li>
                <li>White-label panel</li>
                <li>Full API access</li>
                <li>Custom branding</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=46" class="btn btn-outline" style="width:100%;justify-content:center;">Start Reselling</a>
        </div>
        <div class="price-card popular">
            <div class="tier">Reseller Silver</div>
            <div class="price">$899</div><div class="period">per month</div>
            <ul>
                <li>Up to 200 clients</li>
                <li>White-label everything</li>
                <li>Priority support</li>
                <li>Revenue analytics</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=47" class="btn btn-primary" style="width:100%;justify-content:center;">Start Reselling</a>
        </div>
        <div class="price-card">
            <div class="tier">Reseller Gold</div>
            <div class="price">$2,499</div><div class="period">per month</div>
            <ul>
                <li>Unlimited clients</li>
                <li>Dedicated manager</li>
                <li>Custom integrations</li>
                <li>SLA guarantee</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=48" class="btn btn-outline" style="width:100%;justify-content:center;">Contact Sales</a>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- SECURITY FORTRESS -->
<!-- ============================================================ -->
<section class="security-section" id="security">
    <div style="max-width:1000px; margin:0 auto; text-align:center;">
        <div class="section-label">Security</div>
        <h2 style="font-size:clamp(1.6rem,3vw,2.2rem); font-weight:700; margin-bottom:32px;">Enterprise Security — No Compromises</h2>
        <div class="security-grid">
            <div class="security-item">
                <div class="security-icon">&#128737;</div>
                <div><h4>Enterprise DDoS Mitigation</h4><p>Always-on network-level DDoS protection across all infrastructure. Multi-terabit scrubbing capacity.</p></div>
            </div>
            <div class="security-item">
                <div class="security-icon">&#128274;</div>
                <div><h4>AES-256 Encryption</h4><p>All data encrypted at rest and in transit. TLS 1.3 everywhere. HSTS enforced.</p></div>
            </div>
            <div class="security-item">
                <div class="security-icon">&#128295;</div>
                <div><h4>IPMI/KVM Remote Console</h4><p>Full hardware-level remote access even if SSH is down. HTML5 browser console. Serial over LAN.</p></div>
            </div>
            <div class="security-item">
                <div class="security-icon">&#128272;</div>
                <div><h4>CSF Firewall + 2FA</h4><p>ConfigServer Security & Firewall, Login Failure Daemon, two-factor authentication on all accounts.</p></div>
            </div>
            <div class="security-item">
                <div class="security-icon">&#128736;</div>
                <div><h4>Automated Security Patches</h4><p>Unattended system updates. Vulnerability scanning. Kernel live-patching available.</p></div>
            </div>
            <div class="security-item">
                <div class="security-icon">&#128268;</div>
                <div><h4>vRack Isolation</h4><p>Private network backbone across all servers. Zero exposure to public internet between services.</p></div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- GUARANTEE -->
<!-- ============================================================ -->
<section class="guarantee-section">
    <div class="section-label">Our Promise</div>
    <h2 style="font-size:clamp(1.6rem,3vw,2.2rem); font-weight:700; margin-bottom:8px;">Three Guarantees. Zero Risk.</h2>
    <p style="color:var(--text-dim); font-size:0.95rem;">We put our money where our infrastructure is.</p>
    <div class="guarantee-grid">
        <div class="guarantee-item">
            <div class="icon">&#128737;</div>
            <h4>99.99% Uptime SLA</h4>
            <p>If we miss our SLA, you get service credits. Automatically.</p>
        </div>
        <div class="guarantee-item">
            <div class="icon">&#128640;</div>
            <h4>Free Migration</h4>
            <p>We'll migrate your sites, databases, and emails from any provider. Zero downtime.</p>
        </div>
        <div class="guarantee-item">
            <div class="icon">&#128176;</div>
            <h4>No Hidden Fees</h4>
            <p>The price you see is the price you pay. No bandwidth overage charges. Ever.</p>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- API PREVIEW -->
<!-- ============================================================ -->
<section class="api-section" id="api">
    <div class="section-label">Developer Ready</div>
    <h2 style="font-size:clamp(1.6rem,3vw,2.2rem); font-weight:700; margin-bottom:16px;">266+ API Endpoints. Automate Everything.</h2>
    <p class="sub">Full REST API with authentication, SDKs, webhooks, and CLI tools. Build custom integrations, automate deployments, and manage your entire infrastructure programmatically.</p>

    <div class="code-block">
<span class="comment">// Deploy a new VPS instance via API</span>
<span class="keyword">const</span> response = <span class="keyword">await</span> <span class="func">fetch</span>(<span class="string">'https://gositeme.com/gohostme/api/instances'</span>, {
    <span class="const">method</span>: <span class="string">'POST'</span>,
    <span class="const">headers</span>: {
        <span class="string">'Authorization'</span>: <span class="string">`Bearer ${apiKey}`</span>,
        <span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>
    },
    <span class="const">body</span>: <span class="func">JSON.stringify</span>({
        <span class="const">plan</span>: <span class="string">'vps-business'</span>,
        <span class="const">region</span>: <span class="string">'bhs'</span>,          <span class="comment">// Beauharnois, QC</span>
        <span class="const">image</span>: <span class="string">'ubuntu-22.04'</span>,
        <span class="const">hostname</span>: <span class="string">'my-server'</span>,
        <span class="const">ssh_keys</span>: [<span class="string">'key-123'</span>],
        <span class="const">enable_backups</span>: <span class="keyword">true</span>,
        <span class="const">enable_monitoring</span>: <span class="keyword">true</span>
    })
});

<span class="comment">// Response: Server deployed in ~60 seconds</span>
<span class="comment">// { "id": "srv-abc123", "ip": "15.235.x.x", "status": "active" }</span>
    </div>

    <div style="text-align:center; margin-top:24px;">
        <a href="https://gositeme.com/cart?a=add&pid=43" class="btn btn-outline">API Access from $25/mo</a>
    </div>
</section>


<!-- ============================================================ -->
<!-- ECOSYSTEM -->
<!-- ============================================================ -->
<section class="section" id="ecosystem" style="text-align:center;">
    <div class="section-label">The GoSiteMe Ecosystem</div>
    <h2>One Account. Eight Platforms. Infinite Possibilities.</h2>
    <p class="sub" style="margin:0 auto 32px;">GoHostMe is part of the GoSiteMe ecosystem — the world's first AI-native company. Your GoSiteMe account gives you access to everything.</p>

    <div class="eco-grid">
        <div class="eco-card">
            <div class="eco-icon">&#128187;</div>
            <h4>GoCodeMe</h4>
            <p>AI-powered IDE with 251 MCP tools, 17 AI engines, and voice commands.</p>
            <a href="https://gocodeme.com" class="btn btn-outline" style="font-size:0.82rem;">Visit &rarr;</a>
        </div>
        <div class="eco-card">
            <div class="eco-icon">&#127918;</div>
            <h4>MetaDome</h4>
            <p>VR/AR worlds with 51M+ AI agents in fleet. Build immersive experiences.</p>
            <a href="https://meta-dome.com" class="btn btn-outline" style="font-size:0.82rem;">Visit &rarr;</a>
        </div>
        <div class="eco-card">
            <div class="eco-icon">&#127925;</div>
            <h4>SoundStudioPro</h4>
            <p>AI music production SaaS with real-time audio processing and mixing.</p>
            <a href="https://soundstudiopro.com" class="btn btn-outline" style="font-size:0.82rem;">Visit &rarr;</a>
        </div>
        <div class="eco-card">
            <div class="eco-icon">&#129302;</div>
            <h4>Alfred AI</h4>
            <p>11.3M+ AI agents. 13,262+ tools. The world's largest AI fleet.</p>
            <a href="/gohostme/products" class="btn btn-outline" style="font-size:0.82rem;">Explore &rarr;</a>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- FAQ -->
<!-- ============================================================ -->
<section class="section" id="faq">
    <div class="section-label">FAQ</div>
    <h2>Frequently Asked Questions</h2>
    <p class="sub">Everything you need to know before deploying.</p>

    <div class="faq-list">
        <div class="faq-item">
            <div class="faq-q">Do I really get the GoHostMe panel for free?</div>
            <div class="faq-a">Yes. Every VPS, dedicated server, and GPU instance includes the full GoHostMe AI control panel at no extra cost. No license fees, no per-domain fees, no addon charges. The panel is part of the platform.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Is the bandwidth really unlimited?</div>
            <div class="faq-a">Yes. All plans come with 1 Gbps (or 10 Gbps for enterprise) unmetered bandwidth. There are no overage charges. You'll never get a surprise bandwidth bill from us.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Can you migrate my sites from cPanel / Plesk / another host?</div>
            <div class="faq-a">Absolutely. Our AI Migration Wizard handles sites, databases, email accounts, SSL certificates, and DNS records from any provider. Zero downtime migration is included free with Business plans and above.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">What's the difference between you and DigitalOcean / Hetzner?</div>
            <div class="faq-a">We include everything they charge extra for (control panel, monitoring, backups, DDoS) — and we offer AI voice agents, AI call centers, AI office automation, and 12 industry solutions that they simply don't have. Plus unlimited bandwidth and 18 global regions.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">What payment methods do you accept?</div>
            <div class="faq-a">We accept Stripe (Visa, Mastercard, Amex, Discover) and PayPal. All transactions are processed securely with PCI DSS compliance.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">How fast is provisioning?</div>
            <div class="faq-a">VPS instances deploy in under 60 seconds. Dedicated servers are provisioned within 1-4 hours. GPU instances are available within 2 hours depending on region availability.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">What about the AI voice agents — do they actually work?</div>
            <div class="faq-a">Yes. Our AI voice agents use Whisper STT for speech-to-text, Claude/LLM for intelligence, and Kokoro TTS for natural-sounding speech. They can answer calls, book appointments, qualify leads, and handle customer service 24/7 in multiple languages. We're the only hosting provider that offers this.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q">Can I resell all of this under my own brand?</div>
            <div class="faq-a">Yes. Our Reseller plans ($399-$2,499/mo) give you full white-label access to the entire platform — hosting, AI voice, call center, office suite, and industry solutions. Build your own hosting + AI company.</div>
        </div>
    </div>
</section>


<!-- ============================================================ -->
<!-- FINAL CTA -->
<!-- ============================================================ -->
<section class="cta-section">
    <h2>Ready to Leave the Competition Behind?</h2>
    <p>101+ products. 18 global regions. AI voice agents. AI call centers. 12 industry solutions. No other platform comes close. Deploy in 60 seconds.</p>
    <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
        <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary btn-lg btn-pulse">Deploy VPS &mdash; $59.99/mo &rarr;</a>
        <a href="https://gositeme.com/cart?a=add&pid=105" class="btn btn-gold btn-lg">Dedicated &mdash; $149.99/mo</a>
        <a href="https://gositeme.com/cart?a=add&pid=49" class="btn btn-purple btn-lg">AI Voice Agent &mdash; $29/mo</a>
        <a href="/gohostme/products" class="btn btn-outline btn-lg">Browse All 121 Products</a>
    </div>
    <div style="margin-top: 32px;">
        <span class="green-badge">&#128994; Stripe &amp; PayPal &bull; 99.99% SLA &bull; Free Migration &bull; No Hidden Fees &bull; Canadian Infrastructure</span>
    </div>
</section>


<!-- ============================================================ -->
<!-- FOOTER -->
<!-- ============================================================ -->
<footer class="footer">
    <div class="footer-grid">
        <div>
            <h5>Hosting</h5>
            <a href="https://gositeme.com/cart?a=add&pid=101">VPS Starter — $29.99</a>
            <a href="https://gositeme.com/cart?a=add&pid=102">VPS Business — $59.99</a>
            <a href="https://gositeme.com/cart?a=add&pid=103">VPS Pro — $99.99</a>
            <a href="https://gositeme.com/cart?a=add&pid=104">VPS Enterprise — $179.99</a>
            <a href="https://gositeme.com/cart?a=add&pid=105">Dedicated Starter — $149.99</a>
            <a href="https://gositeme.com/cart?a=add&pid=109">GPU Server — $2,499</a>
        </div>
        <div>
            <h5>AI Solutions</h5>
            <a href="https://gositeme.com/cart?a=add&pid=49">AI Voice Agent — $29</a>
            <a href="https://gositeme.com/cart?a=add&pid=53">AI Call Center — $99</a>
            <a href="https://gositeme.com/cart?a=add&pid=70">AI Receptionist — $49</a>
            <a href="https://gositeme.com/cart?a=add&pid=74">AI Sales Agent — $199</a>
            <a href="https://gositeme.com/cart?a=add&pid=77">AI Live Chat — $19</a>
            <a href="https://gositeme.com/cart?a=add&pid=65">AI Documents — $19</a>
        </div>
        <div>
            <h5>Industries</h5>
            <a href="https://gositeme.com/cart?a=add&pid=78">Restaurant — $99</a>
            <a href="https://gositeme.com/cart?a=add&pid=79">Real Estate — $149</a>
            <a href="https://gositeme.com/cart?a=add&pid=80">Medical — $249</a>
            <a href="https://gositeme.com/cart?a=add&pid=81">Legal — $199</a>
            <a href="https://gositeme.com/cart?a=add&pid=87">E-Commerce — $99</a>
            <a href="https://gositeme.com/cart?a=add&pid=84">Automotive — $149</a>
        </div>
        <div>
            <h5>Ecosystem</h5>
            <a href="https://gositeme.com">GoSiteMe</a>
            <a href="https://gocodeme.com">GoCodeMe — AI IDE</a>
            <a href="https://meta-dome.com">MetaDome — VR</a>
            <a href="https://soundstudiopro.com">SoundStudioPro</a>
            <a href="/gohostme/products">Store — All Products</a>
            <a href="#reseller">Reseller Plans</a>
        </div>
    </div>
    <p style="margin-top:16px;">Infrastructure: Beauharnois &bull; Toronto &bull; Gravelines &bull; Roubaix &bull; Strasbourg &bull; Paris &bull; Frankfurt &bull; London &bull; Warsaw &bull; Milan</p>
    <p style="margin-top:8px;">&copy; <?php echo date('Y'); ?> GoSiteMe Inc. All rights reserved. GoHostMe is a product of <a href="https://gositeme.com" style="color:var(--accent2)">GoSiteMe</a>.</p>
</footer>


<!-- FAQ TOGGLE SCRIPT -->
<script>
document.querySelectorAll('.faq-q').forEach(q => {
    q.addEventListener('click', () => {
        q.parentElement.classList.toggle('open');
    });
});
</script>

</body>
</html>
