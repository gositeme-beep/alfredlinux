<?php
$page_title = 'GoCodeMe — AI Development Platform | 13,000+ Tools, 16 AI Engines, Full IDE | GoSiteMe';
$page_description = 'GoCodeMe: The world\'s first AI development platform. 13,000+ MCP tools, 16 AI engines, full browser IDE, Alfred AI assistant, voice commands, WhatsApp control, WordPress, domains, SSL, security, images, video — all by conversation. From $15/mo.';
$page_canonical = 'https://gocodeme.com/';
$page_og_title = 'GoCodeMe — AI Development Platform | 13,000+ Tools, 16 AI Engines';
$page_og_description = 'The world\'s first AI development platform. Full IDE + Alfred AI + hosting. 13,000+ MCP tools, 16 AI engines. From $15/mo.';
include __DIR__ . '/includes/site-header.inc.php';
?>

    <style>
        :root {
            --primary: #0074D9;
            --cyan: #00D4FF;
            --purple: #7D00FF;
            --dark: #0a0a14;
            --dark-card: #1a1a2e;
            --text: #e0e0e0;
            --text-muted: #a8b2d1;
            --success: #10b981;
        }
        /* Reset handled by site-header.inc.php — only override body for this page */
        body { font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); line-height: 1.6; }
        .bg-grid { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(rgba(0,168,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,168,255,0.03) 1px, transparent 1px); background-size: 50px 50px; pointer-events: none; z-index: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 1; }

        /* HEADER */
        .header { padding: 18px 0; background: rgba(10,10,20,0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(0,168,255,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .header .container { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #fff; font-family: 'Space Grotesk', sans-serif; font-weight: 800; font-size: 1.4rem; }
        .logo img { height: 36px; }
        .header-nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn-primary { background: linear-gradient(135deg, #7D00FF, #00A8FF); color: #fff; box-shadow: 0 4px 20px rgba(125,0,255,0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(125,0,255,0.5); }
        .btn-ghost { background: transparent; color: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.15); }
        .btn-ghost:hover { border-color: var(--cyan); color: var(--cyan); }
        .btn-lg { padding: 16px 36px; font-size: 1.05rem; border-radius: 14px; }
        .btn-outline { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.2); }
        .btn-outline:hover { border-color: var(--cyan); background: rgba(0,212,255,0.1); }

        /* HERO */
        .hero { padding: 160px 0 100px; text-align: center; position: relative; overflow: hidden; }
        .hero::before { content: ''; position: absolute; top: -200px; left: 50%; transform: translateX(-50%); width: 900px; height: 900px; background: radial-gradient(circle, rgba(125,0,255,0.18) 0%, rgba(0,212,255,0.05) 40%, transparent 70%); pointer-events: none; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, rgba(125,0,255,0.15), rgba(0,212,255,0.15)); border: 1px solid rgba(0,212,255,0.35); padding: 10px 22px; border-radius: 50px; font-size: 0.85rem; font-weight: 700; color: var(--cyan); margin-bottom: 28px; letter-spacing: 0.5px; text-transform: uppercase; }
        .hero h1 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(2.8rem, 6vw, 4.5rem); font-weight: 900; margin-bottom: 24px; line-height: 1.05; background: linear-gradient(135deg, #fff 0%, #c084fc 40%, #00D4FF 80%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { font-size: 1.2rem; color: var(--text-muted); max-width: 700px; margin: 0 auto 40px; line-height: 1.7; }
        .hero-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 16px; }
        .hero-promo { font-size: 0.85rem; color: rgba(255,255,255,0.4); }
        .hero-promo strong { color: var(--success); }
        .hero-stats { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; padding: 32px 0; margin-top: 48px; border-top: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06); }
        .hero-stats .stat { text-align: center; }
        .hero-stats .stat-num { font-family: 'Space Grotesk', sans-serif; font-size: 2.2rem; font-weight: 900; color: #fff; display: block; }
        .hero-stats .stat-num .accent { color: var(--cyan); }
        .hero-stats .stat-label { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }

        /* SECTIONS */
        section { padding: 100px 0; }
        .section-label { text-align: center; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 3px; color: #c084fc; font-weight: 700; margin-bottom: 16px; }
        h2.section-title { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 12px; line-height: 1.15; }
        .section-sub { text-align: center; color: var(--text-muted); font-size: 1.05rem; max-width: 660px; margin: 0 auto 48px; line-height: 1.7; }

        /* DEMO TIMELINE */
        .demo-timeline { position: relative; padding-left: 36px; max-width: 860px; margin: 0 auto; }
        .demo-timeline::before { content: ''; position: absolute; left: 14px; top: 0; bottom: 0; width: 2px; background: linear-gradient(180deg, rgba(125,0,255,0.5), rgba(0,212,255,0.5)); }
        .demo-step { position: relative; margin-bottom: 40px; }
        .demo-step::before { content: ''; position: absolute; left: -29px; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--cyan)); box-shadow: 0 0 12px rgba(125,0,255,0.5); }
        .demo-cmd { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, rgba(125,0,255,0.15), rgba(125,0,255,0.25)); border: 1px solid rgba(125,0,255,0.3); border-radius: 12px; padding: 10px 18px; font-size: 0.95rem; color: #e2d1f9; margin-bottom: 10px; font-style: italic; }
        .demo-res { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 14px 18px; font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; }
        .demo-res strong { color: var(--success); }
        .dtag { display: inline-block; margin: 3px; padding: 2px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .dtag.g { background: rgba(16,185,129,0.15); color: #10b981; }
        .dtag.p { background: rgba(125,0,255,0.15); color: #c084fc; }
        .dtag.c { background: rgba(0,212,255,0.15); color: #00D4FF; }
        .dtag.o { background: rgba(251,146,60,0.15); color: #fb923c; }
        .dtag.pk { background: rgba(236,72,153,0.15); color: #ec4899; }
        .dtag.i { background: rgba(99,102,241,0.15); color: #818cf8; }

        /* ENGINE CARDS */
        .engine-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; }
        .engine-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 28px 24px; transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; position: relative; overflow: hidden; }
        .engine-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #7d00ff, #00D4FF); opacity: 0; transition: opacity 0.3s; }
        .engine-card:hover { transform: translateY(-4px); border-color: rgba(125,0,255,0.3); box-shadow: 0 12px 40px rgba(125,0,255,0.15); }
        .engine-card:hover::before { opacity: 1; }
        .engine-icon { font-size: 2rem; margin-bottom: 14px; }
        .engine-card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; font-weight: 800; color: #fff; letter-spacing: 1.5px; margin-bottom: 4px; }
        .engine-subtitle { font-size: 0.8rem; color: #c084fc; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
        .engine-card p { font-size: 0.87rem; color: var(--text-muted); line-height: 1.6; }

        /* FEATURE GRID */
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .feature-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 28px; transition: all 0.3s ease; }
        .feature-card:hover { border-color: rgba(0,212,255,0.25); transform: translateY(-3px); }
        .feature-icon { width: 52px; height: 52px; background: linear-gradient(135deg, rgba(125,0,255,0.2), rgba(0,212,255,0.2)); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--cyan); margin-bottom: 18px; }
        .feature-card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 10px; color: #fff; }
        .feature-card p { color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; }
        .feature-card .new-badge { display: inline-block; background: rgba(16,185,129,0.15); color: #10b981; padding: 2px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; margin-left: 6px; }

        /* TOOLS CATEGORIES */
        .tool-cats { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; max-width: 1100px; margin: 0 auto; }
        .tool-cat-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 20px 18px; transition: border-color 0.3s, background 0.3s; }
        .tool-cat-card:hover { border-color: rgba(125,0,255,0.25); background: rgba(255,255,255,0.04); }
        .tool-cat-card .tcc-icon { font-size: 1.5rem; margin-bottom: 10px; }
        .tool-cat-card .tcc-name { font-size: 0.9rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .tool-cat-card .tcc-count { font-size: 0.78rem; color: var(--cyan); font-weight: 600; margin-bottom: 6px; }
        .tool-cat-card .tcc-examples { font-size: 0.76rem; color: var(--text-muted); line-height: 1.5; }
        .tool-cat-card .tcc-new { display: inline-block; background: rgba(16,185,129,0.15); color: #10b981; padding: 1px 6px; border-radius: 4px; font-size: 0.68rem; font-weight: 700; margin-left: 4px; }

        /* COMPARISON TABLE */
        .comp-table { width: 100%; border-collapse: collapse; max-width: 960px; margin: 0 auto; }
        .comp-table th { font-family: 'Space Grotesk', sans-serif; font-size: 0.85rem; font-weight: 700; padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--text-muted); }
        .comp-table th:first-child { color: #fff; }
        .comp-table td { padding: 12px 18px; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .comp-table tr:hover td { background: rgba(255,255,255,0.02); }
        .comp-table .check { color: var(--success); font-weight: 700; }
        .comp-table .cross { color: rgba(239,68,68,0.6); }
        .comp-table .us-col { color: #fff; font-weight: 600; }
        .comp-table .feature-name { color: var(--text); }
        .comp-table thead th:nth-child(2) { color: var(--cyan); border-bottom-color: var(--cyan); }

        /* PRICING */
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; max-width: 1100px; margin: 0 auto 40px; }
        .pricing-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 28px 22px; text-align: center; transition: transform 0.3s, border-color 0.3s; }
        .pricing-card:hover { transform: translateY(-4px); border-color: rgba(125,0,255,0.3); }
        .pricing-card.featured { border-color: rgba(0,212,255,0.4); background: linear-gradient(135deg, rgba(0,212,255,0.05), rgba(125,0,255,0.05)); }
        .pricing-card .plan-name { font-weight: 700; font-size: 0.95rem; color: #fff; margin-bottom: 6px; }
        .pricing-card .plan-price { font-family: 'Space Grotesk', sans-serif; font-size: 2rem; font-weight: 900; color: var(--cyan); margin-bottom: 4px; }
        .pricing-card .plan-price span { font-size: 0.85rem; color: var(--text-muted); font-weight: 400; }
        .pricing-card .plan-tokens { font-size: 0.88rem; color: #c084fc; font-weight: 600; margin-bottom: 14px; }
        .pricing-card .plan-features { font-size: 0.8rem; color: var(--text-muted); line-height: 1.7; }
        .topup-row { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; max-width: 900px; margin: 0 auto; }
        .topup-pill { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 100px; padding: 12px 28px; text-align: center; transition: border-color 0.3s, transform 0.3s; }
        .topup-pill:hover { border-color: rgba(0,212,255,0.4); transform: translateY(-2px); }
        .topup-pill .tp-tokens { font-family: 'Space Grotesk', sans-serif; font-weight: 800; color: #fff; font-size: 1.05rem; }
        .topup-pill .tp-price { color: var(--cyan); font-weight: 700; font-size: 0.95rem; }

        /* DOWNLOAD */
        .download-box { background: var(--dark-card); border: 1px solid rgba(0,212,255,0.2); border-radius: 24px; padding: 52px 48px; text-align: center; max-width: 800px; margin: 0 auto; }
        .download-box h2 { font-family: 'Space Grotesk', sans-serif; font-size: 2rem; margin-bottom: 12px; color: #fff; }
        .download-box p { color: var(--text-muted); margin-bottom: 36px; font-size: 1.05rem; }
        .download-options { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
        .download-btn { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); padding: 14px 22px; border-radius: 12px; color: #fff; text-decoration: none; transition: all 0.3s ease; }
        .download-btn:hover { background: rgba(0,212,255,0.08); border-color: var(--cyan); }
        .download-btn i { font-size: 1.4rem; color: var(--cyan); }
        .download-btn .dl-label { font-size: 0.72rem; color: var(--text-muted); }
        .download-btn .dl-platform { font-weight: 700; font-size: 0.9rem; }
        .download-btn .dl-size { font-size: 0.7rem; color: var(--cyan); margin-top: 2px; }

        /* FAQ */
        .faq-grid { max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px; }
        .faq-item { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 20px 24px; }
        .faq-item h3 { font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .faq-item p { font-size: 0.87rem; color: var(--text-muted); line-height: 1.65; }

        /* FOOTER */
        .footer { padding: 40px 0; border-top: 1px solid rgba(255,255,255,0.05); text-align: center; color: var(--text-muted); font-size: 0.88rem; }
        .footer a { color: var(--cyan); text-decoration: none; }
        .footer a:hover { text-decoration: underline; }

        /* FINAL CTA SECTION */
        .final-cta-section { padding: 100px 0; text-align: center; position: relative; }
        .final-cta-section::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 700px; height: 700px; background: radial-gradient(circle, rgba(125,0,255,0.1) 0%, transparent 70%); pointer-events: none; }
        .final-cta-section h2 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem,4vw,3rem); font-weight: 800; color: #fff; margin-bottom: 16px; }
        .final-cta-section p { font-size: 1.1rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 36px; }
        .cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 14px; }

        /* AUTOPILOT */
        .autopilot-stats { display: flex; justify-content: center; gap: 36px; flex-wrap: wrap; margin-bottom: 56px; }
        .autopilot-stat { text-align: center; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px 32px; min-width: 160px; transition: border-color 0.3s, transform 0.3s; }
        .autopilot-stat:hover { border-color: rgba(0,212,255,0.3); transform: translateY(-3px); }
        .autopilot-stat .ap-num { font-family: 'Space Grotesk', sans-serif; font-size: 2.2rem; font-weight: 900; background: linear-gradient(135deg, #c084fc, #00D4FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; display: block; }
        .autopilot-stat .ap-label { font-size: 0.82rem; color: var(--text-muted); margin-top: 4px; }
        .autopilot-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto 64px; }
        .autopilot-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 28px 24px; transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; position: relative; overflow: hidden; }
        .autopilot-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #7d00ff, #00D4FF); opacity: 0; transition: opacity 0.3s; }
        .autopilot-card:hover { transform: translateY(-4px); border-color: rgba(125,0,255,0.3); box-shadow: 0 12px 40px rgba(125,0,255,0.15); }
        .autopilot-card:hover::before { opacity: 1; }
        .autopilot-card .ap-icon { font-size: 2rem; margin-bottom: 14px; }
        .autopilot-card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .autopilot-card p { font-size: 0.87rem; color: var(--text-muted); line-height: 1.65; }
        .autopilot-steps { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; max-width: 900px; margin: 0 auto; }
        .autopilot-step { flex: 1; min-width: 220px; max-width: 280px; text-align: center; position: relative; }
        .autopilot-step .step-num { display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--cyan)); font-family: 'Space Grotesk', sans-serif; font-weight: 900; font-size: 1.2rem; color: #fff; margin-bottom: 16px; box-shadow: 0 0 24px rgba(125,0,255,0.4); }
        .autopilot-step h4 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
        .autopilot-step p { font-size: 0.84rem; color: var(--text-muted); line-height: 1.6; }
        .autopilot-step-arrow { display: flex; align-items: center; color: rgba(0,212,255,0.3); font-size: 1.5rem; padding-top: 24px; }

        @media (max-width: 768px) {
            .hero { padding: 110px 0 60px; }
            .hero h1 { font-size: 2.2rem; }
            .autopilot-grid { grid-template-columns: 1fr; }
            .autopilot-step-arrow { display: none; }
            .autopilot-stats { gap: 12px; }
            .autopilot-stat { min-width: 130px; padding: 18px 20px; }
            .autopilot-stat .ap-num { font-size: 1.7rem; }
            .hero-stats { gap: 24px; }
            .hero-stats .stat-num { font-size: 1.7rem; }
            .engine-grid { grid-template-columns: 1fr; }
            .feature-grid { grid-template-columns: 1fr; }
            .download-box { padding: 32px 20px; }
            .download-options { flex-direction: column; align-items: center; }
            .comp-table th:not(:first-child):not(:nth-child(2)) { display: none; }
            .comp-table td:not(:first-child):not(:nth-child(2)) { display: none; }
            .header-nav { gap: 6px; }
        }
        @media (max-width: 480px) {
            .tool-cats { grid-template-columns: 1fr 1fr; }
            .pricing-grid { grid-template-columns: 1fr 1fr; }
            .hero-buttons { flex-direction: column; align-items: center; }
        }
    </style>

<!-- ═══ HERO ═══ -->
<section class="hero">
    <div class="container">
        <div class="hero-badge"><i class="fas fa-crown"></i> World's First AI Development Platform</div>
        <h1>GoCodeMe.<br>The IDE That Manages Everything.</h1>
        <p>13,000+ MCP tools. 16 AI engines. Full browser IDE. Alfred AI. Voice commands. Command steering. WhatsApp. WordPress. Domains. SSL. Security. Images. Video. Private on-server AI. Agent-to-Agent protocol. All in one tab. All by conversation.</p>
        <div class="hero-buttons">
            <a href="https://gositeme.com/cart?a=add&pid=18" class="btn btn-primary btn-lg"><i class="fas fa-rocket"></i> Start AI Hosting — $15/mo</a>
            <a href="https://gositeme.com/gocodeme-ide.php" class="btn btn-outline btn-lg"><i class="fas fa-code"></i> Use Online IDE</a>
            <a href="#download" class="btn btn-ghost btn-lg"><i class="fas fa-download"></i> Download</a>
        </div>
        <p class="hero-promo">Use code <strong>LAUNCH50</strong> for 50% off your first year &middot; No contracts &middot; Cancel anytime</p>
        <div class="hero-stats">
            <div class="stat"><span class="stat-num"><span class="accent">13,000+</span></span><span class="stat-label">AI Tools</span></div>
            <div class="stat"><span class="stat-num"><span class="accent">16</span></span><span class="stat-label">Intelligence Engines</span></div>
            <div class="stat"><span class="stat-num">$15</span><span class="stat-label">Per Month</span></div>
            <div class="stat"><span class="stat-num">0</span><span class="stat-label">Commands to Learn</span></div>
            <div class="stat"><span class="stat-num">24/7</span><span class="stat-label">Always On</span></div>
            <div class="stat"><span class="stat-num"><span class="accent">6+</span></span><span class="stat-label">Messaging Apps</span></div>
        </div>
    </div>
</section>

<!-- ═══ LIVE DEMO ═══ -->
<section style="padding: 100px 0; background: linear-gradient(180deg, transparent, rgba(125,0,255,0.04), transparent);">
    <div class="container">
        <div class="section-label">⚡ Live Demo</div>
        <h2 class="section-title">It's 9 PM. You Just Had an Idea.</h2>
        <p class="section-sub">No developer. No agency. No $5,000 invoice. Here's what Alfred does in 12 minutes — all from a conversation.</p>
        <div class="demo-timeline">
            <div class="demo-step">
                <div class="demo-cmd"><i class="fas fa-microphone"></i> "Alfred, set up a WordPress site on mybakery.com"</div>
                <div class="demo-res"><strong>✓ Done in 8 seconds.</strong> WordPress installed. SSL provisioned. DNS configured. Your site is LIVE at <code>https://mybakery.com</code>.<br><span class="dtag g">install-wordpress</span><span class="dtag c">manage-ssl</span><span class="dtag p">manage-dns</span></div>
            </div>
            <div class="demo-step">
                <div class="demo-cmd"><i class="fas fa-microphone"></i> "Install WooCommerce and create 3 products with prices"</div>
                <div class="demo-res"><strong>✓ Done.</strong> WooCommerce activated. 3 products with descriptions, images, and pricing. Payment gateway ready.<br><span class="dtag g">install-plugin</span><span class="dtag p">manage-wordpress</span></div>
            </div>
            <div class="demo-step">
                <div class="demo-cmd"><i class="fas fa-microphone"></i> "Generate a hero image — rustic bakery, warm morning light"</div>
                <div class="demo-res"><strong>✓ Generated in 4.2s.</strong> AI-created 1024×1024 hero image. Saved to <code>/ai-images/</code>. Public URL ready to embed.<br><span class="dtag g">generate-image</span><span class="dtag p">AI Images</span></div>
            </div>
            <div class="demo-step">
                <div class="demo-cmd"><i class="fas fa-microphone"></i> "Create me@mybakery.com and forward to my Gmail"</div>
                <div class="demo-res"><strong>✓ Done.</strong> Email created. Forwarding enabled. SPF, DKIM, DMARC configured automatically.<br><span class="dtag g">create-email</span><span class="dtag c">email-forwarding</span></div>
            </div>
            <div class="demo-step">
                <div class="demo-cmd"><i class="fas fa-microphone"></i> "Review my last commit for bugs and security issues"</div>
                <div class="demo-res"><strong>✓ Code Review complete. Score: 9.1/10.</strong> 1 style suggestion, 0 critical issues, 0 security vulnerabilities. All clear.<br><span class="dtag g">code-review</span><span class="dtag p">AI Code Review</span></div>
            </div>
            <div class="demo-step" style="border-left-color: rgba(0,212,255,0.6);">
                <div class="demo-cmd" style="background:linear-gradient(135deg,rgba(0,212,255,0.15),rgba(0,212,255,0.25));border-color:rgba(0,212,255,0.3);"><i class="fas fa-globe"></i> "Go to our competitor's pricing page and summarize their plans"</div>
                <div class="demo-res"><strong>✓ Browser Agent deployed.</strong> Navigated and extracted 3 pricing tiers with full breakdown. Comparison saved to <code>/reports/</code>.<br><span class="dtag c">browser-navigate</span><span class="dtag c">browser-extract</span><span class="dtag p">NEXUS Browser Agent</span></div>
            </div>
            <div class="demo-step" style="border-left-color: rgba(16,185,129,0.6);">
                <div class="demo-cmd" style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(16,185,129,0.25));border-color:rgba(16,185,129,0.3);"><i class="fas fa-code"></i> "Run this Python script and analyze my sales CSV"</div>
                <div class="demo-res"><strong>✓ Code executed.</strong> 2,847 rows processed. Revenue up 23% YoY. Interactive bar chart artifact generated — view it live in your editor.<br><span class="dtag g">execute-code</span><span class="dtag pk">create-chart</span><span class="dtag p">FORGE + ARTIFACTS</span></div>
            </div>
            <div class="demo-step" style="border-left-color: rgba(236,72,153,0.6);">
                <div class="demo-cmd" style="background:linear-gradient(135deg,rgba(236,72,153,0.15),rgba(236,72,153,0.25));border-color:rgba(236,72,153,0.3);"><i class="fas fa-chart-bar"></i> "Show my traffic this month as a bar chart"</div>
                <div class="demo-res"><strong>✓ Chart generated.</strong> Interactive bar chart — 31 days, 24,847 visitors, peak Tuesday. Live artifact in your editor. Share or embed instantly.<br><span class="dtag pk">create-chart</span><span class="dtag pk">Live Artifacts</span></div>
            </div>
        </div>
        <p style="text-align:center; margin-top:40px; font-size:1.1rem; color:var(--text-muted);">Total time: <strong style="color:#fff;">~12 minutes</strong> &middot; Commands typed: <strong style="color:var(--success);">zero</strong> &middot; Agency cost saved: <strong style="color:#fb923c;">$2,000–$10,000</strong></p>
    </div>
</section>

<!-- ═══ ALFRED AUTOPILOT ═══ -->
<section id="autopilot" style="padding: 100px 0; background: linear-gradient(180deg, transparent, rgba(0,212,255,0.04), rgba(125,0,255,0.04), transparent);">
    <div class="container">
        <div class="section-label"><i class="fas fa-robot"></i> New: Agentic Browsing</div>
        <h2 class="section-title">Alfred Autopilot — Live Browser Agent</h2>
        <p class="section-sub">Give Alfred a goal. Watch him browse, click, type, and scroll in a real Chromium browser — with full human oversight, undo, and confidence scoring on every action.</p>

        <div class="autopilot-stats">
            <div class="autopilot-stat"><span class="ap-num">15</span><span class="ap-label">Action Types</span></div>
            <div class="autopilot-stat"><span class="ap-num">19</span><span class="ap-label">Human Features</span></div>
            <div class="autopilot-stat"><span class="ap-num">29</span><span class="ap-label">API Endpoints</span></div>
            <div class="autopilot-stat"><span class="ap-num">42</span><span class="ap-label">UI Components</span></div>
        </div>

        <div class="autopilot-grid">
            <div class="autopilot-card">
                <div class="ap-icon">&#127760;</div>
                <h3><i class="fas fa-browser" style="color:var(--cyan);margin-right:8px;"></i>Live Browser Control</h3>
                <p>Navigate, click, type, scroll, hover in a real Chromium browser. Watch every action live with binary JPEG streaming. Full viewport or element-level targeting.</p>
            </div>
            <div class="autopilot-card">
                <div class="ap-icon">&#128101;</div>
                <h3><i class="fas fa-shield-check" style="color:var(--success);margin-right:8px;"></i>Human-in-the-Loop</h3>
                <p>Every action requires your approval before executing. Preview cards show exactly what will happen. One-click approve or reject. You stay in full control.</p>
            </div>
            <div class="autopilot-card">
                <div class="ap-icon">&#128200;</div>
                <h3><i class="fas fa-brain" style="color:#c084fc;margin-right:8px;"></i>Confidence & Sentiment</h3>
                <p>Real-time confidence scoring (0–100%) for every action. Sentiment tracking detects when the AI is stuck or frustrated. Automatic pause on low confidence.</p>
            </div>
            <div class="autopilot-card">
                <div class="ap-icon">&#9194;</div>
                <h3><i class="fas fa-history" style="color:#fb923c;margin-right:8px;"></i>Undo & Replay</h3>
                <p>20-level undo stack lets you rollback any action. Full session DVR with screenshot timeline scrubber. Replay entire sessions step by step.</p>
            </div>
            <div class="autopilot-card">
                <div class="ap-icon">&#9881;</div>
                <h3><i class="fas fa-cogs" style="color:var(--cyan);margin-right:8px;"></i>Smart Automation</h3>
                <p>Batch operations, scheduled tasks, reusable templates. Domain geo-fencing and data retention controls for security. Run complex multi-step flows unattended.</p>
            </div>
            <div class="autopilot-card">
                <div class="ap-icon">&#9855;</div>
                <h3><i class="fas fa-universal-access" style="color:#818cf8;margin-right:8px;"></i>Accessibility First</h3>
                <p>Screen reader narration, keyboard-only operation, high-contrast mode. Celebration animations for task completion. Built for everyone, from the ground up.</p>
            </div>
        </div>

        <div class="section-label" style="margin-bottom:32px;">How Autopilot Works</div>
        <div class="autopilot-steps">
            <div class="autopilot-step">
                <div class="step-num">1</div>
                <h4>Tell Alfred What to Do</h4>
                <p>Describe your goal in plain English. "Fill out the contact form on acme.com" — that's it.</p>
            </div>
            <div class="autopilot-step-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="autopilot-step">
                <div class="step-num">2</div>
                <h4>Watch & Approve</h4>
                <p>See the live browser viewport. Each proposed action shows a preview card. Approve, reject, or edit before it runs.</p>
            </div>
            <div class="autopilot-step-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="autopilot-step">
                <div class="step-num">3</div>
                <h4>Alfred Executes</h4>
                <p>Action confirmed — Alfred clicks, types, or navigates. Real-time screenshot updates. Full undo if anything looks wrong.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ 16 INTELLIGENCE ENGINES ═══ -->
<section id="engines">
    <div class="container">
        <div class="section-label">Built Different</div>
        <h2 class="section-title">16 Intelligence Engines.<br>Zero Competition.</h2>
        <p class="section-sub">Alfred doesn't just execute commands. He thinks, remembers, learns, schedules, delegates, monitors, browses, codes, and searches — autonomously.</p>
        <div class="engine-grid">
            <div class="engine-card"><div class="engine-icon">&#129504;</div><h3>ELEPHANT</h3><div class="engine-subtitle">Persistent Memory</div><p>Alfred remembers your preferences, decisions, and project context across every session. Smart pruning ensures only the most relevant memories surface. Never repeat yourself.</p></div>
            <div class="engine-card"><div class="engine-icon">&#128302;</div><h3>ORACLE</h3><div class="engine-subtitle">Semantic Code Intelligence</div><p>Search your codebase by meaning, not keywords. "Find the authentication logic" returns the exact function. Understands 25+ languages with local inference.</p></div>
            <div class="engine-card"><div class="engine-icon">&#128203;</div><h3>PLAYBOOK</h3><div class="engine-subtitle">Self-Healing Workflows</div><p>Save multi-step workflows as natural language templates. Deploy WordPress, audit security, onboard domains — in one command. 10 built-in templates. If a step fails, Alfred adapts.</p></div>
            <div class="engine-card"><div class="engine-icon">&#9200;</div><h3>CLOCKWORK</h3><div class="engine-subtitle">Autonomous Scheduler</div><p>Schedule nightly backups, SSL renewals, security audits that run while you sleep. Cron-powered, persistently stored, with full execution logs and failure alerts.</p></div>
            <div class="engine-card"><div class="engine-icon">&#129435;</div><h3>HIVEMIND</h3><div class="engine-subtitle">Multi-Agent Delegation</div><p>Alfred spawns parallel sub-agents — researchers, analyzers, workers — to tackle complex problems 3× faster. Role-based access keeps everything safe and coordinated.</p></div>
            <div class="engine-card"><div class="engine-icon">&#128737;</div><h3>SENTINEL</h3><div class="engine-subtitle">Proactive Monitoring & Auto-Heal</div><p>Watches your sites 24/7 — uptime, SSL expiry, disk space, response times. When something breaks, Alfred fixes it automatically before you even notice.</p></div>
            <div class="engine-card"><div class="engine-icon">&#127760;</div><h3>NEXUS</h3><div class="engine-subtitle">Browser Agent & Web Intelligence</div><p>A full browser Alfred controls autonomously. Navigate sites, fill forms, extract data, take screenshots, download files — all by conversation. Competitor research to automation.</p></div>
            <div class="engine-card"><div class="engine-icon">&#128296;</div><h3>FORGE</h3><div class="engine-subtitle">Code Interpreter & Sandbox</div><p>Execute Python, Node.js, Bash, Ruby, and PHP in a secure sandbox. Data analysis, chart generation, prototyping, debugging — Alfred runs real code and returns real results.</p></div>
            <div class="engine-card"><div class="engine-icon">&#129504;</div><h3>CORTEX</h3><div class="engine-subtitle">RAG Knowledge Base</div><p>Ingest docs, PDFs, and codebases into a vector-indexed knowledge base. Alfred answers questions with pinpoint accuracy grounded in your own data. Your private AI library.</p></div>
        </div>
    </div>
</section>

<!-- ═══ ALL FEATURES ═══ -->
<section style="background: linear-gradient(135deg, rgba(125,0,255,0.03), rgba(0,212,255,0.03));">
    <div class="container">
        <div class="section-label">Everything Included</div>
        <h2 class="section-title">Every Feature. One Platform.</h2>
        <p class="section-sub">Everything you need to build, manage, and grow your web presence — all through natural conversation with Alfred.</p>
        <div class="feature-grid">
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-microphone"></i></div><h3>Voice Commands</h3><p>Click the mic and speak naturally. "Set up SSL on mysite.com" — Alfred executes it. Browser-native, no extensions needed.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fab fa-whatsapp"></i></div><h3>Messaging Integration</h3><p>Manage your site from WhatsApp, Signal, Discord, Telegram, or SMS. Send a command from your phone, Alfred responds in seconds.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-image"></i></div><h3>AI Image Generation</h3><p>Generate photos, logos, hero images, and product shots from text descriptions. 7 style presets. Saved directly to your site.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-film"></i></div><h3>AI Video & Audio</h3><p>Generate product videos and professional voiceovers from text. Multiple AI models for cinematic quality. Perfect for landing pages and ads.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-eye"></i></div><h3>Vision Analysis</h3><p>Send screenshots, mockups, diagrams — Alfred analyzes them. Screenshot-to-code, UI review, OCR, accessibility audit. All by conversation.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-chart-bar"></i></div><h3>Live Charts & Artifacts</h3><p>Interactive Chart.js charts, Mermaid diagrams, live HTML previews — all generated as artifacts inside your editor. No plugins, no exports.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-lock"></i></div><h3>Private On-Server AI</h3><p>Run AI models directly on your server. Sensitive code and private data never leave your infrastructure. Smart local/cloud routing.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-robot"></i></div><h3>Agent-to-Agent Protocol</h3><p>Alfred discovers and collaborates with other AI agents. Delegate specialized tasks to remote agents. The future of autonomous AI workflows.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-plug"></i></div><h3>MCP Gateway</h3><p>Connect to GitHub, Slack, Postgres, Brave Search, and hundreds of external MCP servers. Use their tools through natural conversation.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-microphone-alt"></i></div><h3>Live Voice Rooms</h3><p>Create real-time voice rooms for Alfred and your team. Multiple participants, all talking to the same AI simultaneously.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fab fa-wordpress"></i></div><h3>WordPress — 11 Tools</h3><p>Install, manage, update, secure, and optimize WordPress through conversation. Plugins, themes, DB optimize, migration — all automated.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-shield-alt"></i></div><h3>Security & Monitoring</h3><p>Malware scanning, permission audits, SSL management, error log analysis. Alfred monitors proactively and auto-fixes common issues 24/7.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-gamepad"></i></div><h3>VR Game Engine</h3><p>Build 3D WebXR games with Three.js — chess, DJ studios, pool, and more. 20+ AI agents, 16 world venues, multiplayer, voice chat, and full SDK.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-coins"></i></div><h3>Solana & Events</h3><p>Sell event tickets with SOL, GSM Token, or USDC. Phantom wallet integration, Jupiter DEX swaps, on-chain payments, and 85% artist revenue splits.</p></div>
        </div>
    </div>
</section>

<!-- ═══ ALL 13,000+ TOOLS BY CATEGORY ═══ -->
<section id="tools">
    <div class="container">
        <div class="section-label">Complete Toolkit</div>
        <h2 class="section-title">13,000+ Tools. Every Category.</h2>
        <p class="section-sub">The most powerful AI toolkit ever built for web development and hosting. Each tool works through natural conversation — no commands to memorize.</p>
        <div class="tool-cats">
            <div class="tool-cat-card"><div class="tcc-icon">📁</div><div class="tcc-name">File Management</div><div class="tcc-count">8 tools</div><div class="tcc-examples">Read, write, delete, rename, copy, set permissions, extract archives</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🗄️</div><div class="tcc-name">Database</div><div class="tcc-count">4 tools</div><div class="tcc-examples">List, create, delete databases, optimize WordPress DB</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🌐</div><div class="tcc-name">Domains, DNS & SSL</div><div class="tcc-count">10 tools</div><div class="tcc-examples">List domains, DNS records, SSL certs, search & register domains</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">📧</div><div class="tcc-name">Email Management</div><div class="tcc-count">5 tools</div><div class="tcc-examples">Create accounts, forwarding, autoresponders, send email</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">💾</div><div class="tcc-name">Backups & Cron</div><div class="tcc-count">9 tools</div><div class="tcc-examples">Full backups, restore, list cron jobs, create schedules</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">⚙️</div><div class="tcc-name">WordPress</div><div class="tcc-count">11 tools</div><div class="tcc-examples">Install, plugins, themes, updates, WP-CLI, DB optimize</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🔀</div><div class="tcc-name">Git Version Control</div><div class="tcc-count">6 tools</div><div class="tcc-examples">Status, log, diff, commit, revert, branches, smart commits</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🛡️</div><div class="tcc-name">Security & Logs</div><div class="tcc-count">6 tools</div><div class="tcc-examples">Malware scan, permissions audit, error logs, access logs</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">📊</div><div class="tcc-name">Analytics & Health</div><div class="tcc-count">5 tools</div><div class="tcc-examples">Visitor stats, bandwidth, traffic report, site health check</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">💳</div><div class="tcc-name">Billing & Commerce</div><div class="tcc-count">14 tools</div><div class="tcc-examples">Invoices, services, pay, order hosting, register domains</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🎨</div><div class="tcc-name">AI Image Generation</div><div class="tcc-count">2 tools</div><div class="tcc-examples">Generate from text, list generated images</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🎬</div><div class="tcc-name">AI Media & Voice</div><div class="tcc-count">10 tools</div><div class="tcc-examples">Video, audio/TTS, vision analysis, process video, download media</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🧠</div><div class="tcc-name">ELEPHANT Memory</div><div class="tcc-count">5 tools</div><div class="tcc-examples">Remember, recall, forget, memory summary, save session</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🔮</div><div class="tcc-name">ORACLE Code Search</div><div class="tcc-count">4 tools</div><div class="tcc-examples">Semantic search, reindex, index stats, auto-index watcher</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">📋</div><div class="tcc-name">PLAYBOOK Workflows</div><div class="tcc-count">3 tools</div><div class="tcc-examples">Run playbook, list playbooks, save custom playbook</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">⏰</div><div class="tcc-name">CLOCKWORK Scheduler</div><div class="tcc-count">4 tools</div><div class="tcc-examples">Schedule tasks, list, delete, view task logs</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🐝</div><div class="tcc-name">HIVEMIND Multi-Agent</div><div class="tcc-count">2 tools</div><div class="tcc-examples">Spawn sub-agents, collect results from parallel agents</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">💻</div><div class="tcc-name">Developer Power Tools</div><div class="tcc-count">12 tools</div><div class="tcc-examples">AI code review, smart commit, dependency audit, project snapshot</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">📚</div><div class="tcc-name">CORTEX RAG Knowledge</div><div class="tcc-count">4 tools</div><div class="tcc-examples">Ingest docs, query knowledge base, list & delete collections</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🔨</div><div class="tcc-name">FORGE Code Interpreter</div><div class="tcc-count">3 tools</div><div class="tcc-examples">Run Python/Node/Bash/PHP/Ruby, list sessions, kill session</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🌐</div><div class="tcc-name">NEXUS Browser Agent</div><div class="tcc-count">6 tools</div><div class="tcc-examples">Browse web, screenshot, click, fill forms, extract data, web search</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🔌</div><div class="tcc-name">MCP Gateway</div><div class="tcc-count">4 tools</div><div class="tcc-examples">Connect/disconnect MCP servers, list, call external tools</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">⚙️</div><div class="tcc-name">Workflow Automation</div><div class="tcc-count">4 tools</div><div class="tcc-examples">Create, execute, list, check status of automation workflows</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🛡️</div><div class="tcc-name">SENTINEL Monitoring</div><div class="tcc-count">3 tools</div><div class="tcc-examples">Enable monitoring, alert history, auto-fix configuration</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">📊</div><div class="tcc-name">Charts & Artifacts</div><div class="tcc-count">4 tools</div><div class="tcc-examples">Create charts, Mermaid diagrams, HTML previews, list artifacts</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🎙️</div><div class="tcc-name">Voice Rooms</div><div class="tcc-count">3 tools</div><div class="tcc-examples">Create room, join with token, list active rooms</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🔒</div><div class="tcc-name">Private On-Server AI</div><div class="tcc-count">4 tools</div><div class="tcc-examples">On-server chat, list models, install models, smart routing</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🤖</div><div class="tcc-name">A2A Protocol</div><div class="tcc-count">4 tools</div><div class="tcc-examples">Discover agents, send tasks, list tasks, publish agent card</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🎮</div><div class="tcc-name">VR Game Engine</div><div class="tcc-count">8 tools</div><div class="tcc-examples">WebXR scenes, AI agents, multiplayer, leaderboards, voice chat, venues</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">🎵</div><div class="tcc-name">SSP Music & Events</div><div class="tcc-count">10 tools</div><div class="tcc-examples">53+ tracks, 16 venues, event ticketing, Solana payments, revenue splits</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">◎</div><div class="tcc-name">Solana & Crypto</div><div class="tcc-count">6 tools</div><div class="tcc-examples">SOL/GSM/USDC payments, Phantom wallet, Jupiter DEX, token swap</div></div>
            <div class="tool-cat-card"><div class="tcc-icon">✝</div><div class="tcc-name">Sanctuary &amp; Brotherhood</div><div class="tcc-count">46 tools</div><div class="tcc-examples">60 Brotherhood agents, 50 languages, 13 games connected, 12 biblical activities, Game Engine SDK, voice commands, 51 scriptures, 13 Names of Jesus, 41-gen Lineage of Perez, 12 Classrooms, Donation Foundation, Gospel Music Studio, Psalms, automix</div></div>
        </div>
        <p style="text-align:center; margin-top:32px;"><a href="https://gositeme.com/alfred.php#tools" class="btn btn-ghost">View All 13,000+ Tools Explained &rarr;</a></p>
    </div>
</section>

<!-- ═══ COMPARISON ═══ -->
<section style="background: linear-gradient(135deg, rgba(0,0,0,0.3), transparent);">
    <div class="container">
        <div class="section-label">Head-to-Head</div>
        <h2 class="section-title">Alfred vs. Everything Else</h2>
        <p class="section-sub">We built what the industry said was impossible. Here's the proof.</p>
        <div style="overflow-x:auto;">
        <table class="comp-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Alfred AI · $15/mo</th>
                    <th>Cursor · $20/mo</th>
                    <th>Lovable · $20/mo</th>
                    <th>cPanel · $15+</th>
                    <th>Replit</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="feature-name">AI Code Editor</td><td class="us-col check">✓ Full IDE</td><td class="check">✓</td><td class="cross">✗</td><td class="cross">✗</td><td class="check">✓</td></tr>
                <tr><td class="feature-name">AI Image Generation</td><td class="us-col check">✓ Built-in</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">AI Video & Audio</td><td class="us-col check">✓ Built-in</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Browser Agent</td><td class="us-col check">✓ NEXUS</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Code Interpreter</td><td class="us-col check">✓ FORGE</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="check">✓</td></tr>
                <tr><td class="feature-name">RAG Knowledge Base</td><td class="us-col check">✓ CORTEX</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Charts & Artifacts</td><td class="us-col check">✓ Built-in</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Private On-Server AI</td><td class="us-col check">✓ Built-in</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">A2A Protocol</td><td class="us-col check">✓ Built-in</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Voice Commands</td><td class="us-col check">✓ Browser native</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">WhatsApp / Messaging</td><td class="us-col check">✓ 6+ platforms</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">WordPress Management</td><td class="us-col check">✓ 11 tools</td><td class="cross">✗</td><td class="cross">✗</td><td style="color:var(--text-muted);">Basic</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Domains & DNS</td><td class="us-col check">✓ Full</td><td class="cross">✗</td><td class="cross">✗</td><td class="check">✓</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Email Management</td><td class="us-col check">✓ With AI</td><td class="cross">✗</td><td class="cross">✗</td><td class="check">✓</td><td class="cross">✗</td></tr>
                <tr><td class="feature-name">Backups & Git</td><td class="us-col check">✓ Both</td><td style="color:var(--text-muted);">Git only</td><td class="cross">✗</td><td style="color:var(--text-muted);">Backups</td><td style="color:var(--text-muted);">Git only</td></tr>
                <tr><td class="feature-name">Hosting Included</td><td class="us-col check">✓ SSD + SSL</td><td class="cross">✗ BYOH</td><td style="color:var(--text-muted);">Limited</td><td class="check">✓</td><td style="color:var(--text-muted);">Limited</td></tr>
                <tr><td class="feature-name">Total AI Tools</td><td class="us-col" style="color:var(--cyan); font-weight:800;">13,000+</td><td>~15</td><td>~8</td><td>0</td><td>~5</td></tr>
            </tbody>
        </table>
        </div>
        <p style="text-align:center; margin-top:24px; font-size:0.88rem; color:var(--text-muted);">Not just an editor. Not just hosting. The entire stack — one place, less money.</p>
    </div>
</section>

<!-- ═══ DOWNLOAD ═══ -->
<section id="download">
    <div class="container">
        <div class="download-box">
            <h2>Use GoCodeMe</h2>
            <p>GoCodeMe is a cloud-powered IDE that runs in your browser. No downloads needed — just sign in and start coding.</p>
            <div class="download-options">
                <a href="/editor/" class="download-btn">
                    <i class="fas fa-code"></i>
                    <span><span class="dl-label">Launch</span><span class="dl-platform">GoCodeMe IDE</span><span class="dl-size">Web-Based</span></span>
                </a>
                <a href="/downloads/Alfred-Browser-3.0.0-win-x64.zip" class="download-btn" download>
                    <i class="fab fa-windows"></i>
                    <span><span class="dl-label">Download</span><span class="dl-platform">Alfred Browser (Windows)</span><span class="dl-size">~109 MB</span></span>
                </a>
                <a href="/downloads/Alfred-Browser-3.0.0-mac-intel.zip" class="download-btn" download>
                    <i class="fab fa-apple"></i>
                    <span><span class="dl-label">Download</span><span class="dl-platform">Alfred Browser (macOS Intel)</span><span class="dl-size">~95 MB</span></span>
                </a>
                <a href="/downloads/Alfred-Browser-3.0.0-mac-arm64.zip" class="download-btn" download>
                    <i class="fab fa-apple"></i>
                    <span><span class="dl-label">Download</span><span class="dl-platform">Alfred Browser (macOS M1/M2/M3)</span><span class="dl-size">~91 MB</span></span>
                </a>
                <a href="/downloads/Alfred-Browser-3.0.0.AppImage" class="download-btn" download>
                    <i class="fab fa-linux"></i>
                    <span><span class="dl-label">Download</span><span class="dl-platform">Alfred Browser (Linux AppImage)</span><span class="dl-size">~105 MB</span></span>
                </a>
                <a href="/downloads/alfred-browser_3.0.0_amd64.deb" class="download-btn" download>
                    <i class="fab fa-ubuntu"></i>
                    <span><span class="dl-label">Download</span><span class="dl-platform">Alfred Browser (Ubuntu .deb)</span><span class="dl-size">~73 MB</span></span>
                </a>
            </div>
            <p style="font-size:0.85rem; color:var(--text-muted);">GoCodeMe IDE v2.0 &middot; Works with your GoSiteMe hosting account &middot; <a href="/editor/" style="color:var(--cyan);">Open in browser</a></p>
        </div>
    </div>
</section>

<!-- ═══ PRICING ═══ -->
<section style="background: linear-gradient(135deg, rgba(125,0,255,0.04), rgba(0,212,255,0.04));">
    <div class="container">
        <div class="section-label">Simple Pricing</div>
        <h2 class="section-title">Start Small. Scale as You Grow.</h2>
        <p class="section-sub">Transparent pricing with no hidden fees. Every plan includes Alfred AI, the full IDE, and all 13,000+ tools.</p>
        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="plan-name">Builder</div>
                <div class="plan-price">$15<span>/mo</span></div>
                <div class="plan-tokens">300,000 tokens/month</div>
                <div class="plan-features">1 Website<br>Alfred AI — 13,000+ Tools<br>Voice + Messaging<br>AI Images, Video & Audio<br>Full GoCodeMe IDE<br>Domain + Free SSL</div>
                <a href="https://gositeme.com/cart?a=add&pid=18" class="btn btn-primary" style="margin-top:18px; display:block; text-align:center;">Get Started</a>
            </div>
            <div class="pricing-card featured">
                <div class="plan-name">Creator</div>
                <div class="plan-price">$22<span>/mo</span></div>
                <div class="plan-tokens">450,000 tokens/month</div>
                <div class="plan-features">3 Websites<br>Everything in Builder<br>30GB NVMe Storage<br>Priority Email Support</div>
                <a href="https://gositeme.com/cart?a=add&pid=32" class="btn btn-primary" style="margin-top:18px; display:block; text-align:center;">Get Started</a>
            </div>
            <div class="pricing-card">
                <div class="plan-name">Professional</div>
                <div class="plan-price">$29<span>/mo</span></div>
                <div class="plan-tokens">600,000 tokens/month</div>
                <div class="plan-features">Everything in Creator<br>Priority AI processing<br>Full Git workflow + PRs<br>Database management<br>SSH/SFTP access</div>
            </div>
            <div class="pricing-card">
                <div class="plan-name">Studio</div>
                <div class="plan-price">$59<span>/mo</span></div>
                <div class="plan-tokens">1,500,000 tokens/month</div>
                <div class="plan-features">Everything in Pro<br>Premium Model access<br>3 parallel AI sessions<br>Team sharing (5 users)<br>Docker orchestration</div>
            </div>
            <div class="pricing-card">
                <div class="plan-name">Business</div>
                <div class="plan-price">$99<span>/mo</span></div>
                <div class="plan-tokens">3,000,000 tokens/month</div>
                <div class="plan-features">Everything in Studio<br>Unlimited Premium Model<br>10 parallel AI sessions<br>25 collaborators<br>SSO/SAML + RBAC</div>
            </div>
        </div>
        <p style="text-align:center; margin:32px 0 20px; font-weight:700; color:rgba(255,255,255,0.5); font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;">⚡ Need More Tokens? Buy a One-Time Pack</p>
        <div class="topup-row">
            <div class="topup-pill"><div class="tp-tokens">100K</div><div class="tp-price">$5</div></div>
            <div class="topup-pill"><div class="tp-tokens">500K</div><div class="tp-price">$19</div></div>
            <div class="topup-pill"><div class="tp-tokens">1M</div><div class="tp-price">$35</div></div>
            <div class="topup-pill"><div class="tp-tokens">5M</div><div class="tp-price">$149</div></div>
        </div>
        <p style="text-align:center; margin-top:24px; font-size:0.85rem; color:rgba(255,255,255,0.4);">Use code <strong style="color:var(--success);">LAUNCH50</strong> for 50% off your first year &middot; <strong style="color:var(--success);">ANNUAL20</strong> for 20% off annual billing</p>
    </div>
</section>

<!-- ═══ FAQ ═══ -->
<section>
    <div class="container">
        <div class="section-label">Got Questions?</div>
        <h2 class="section-title">Frequently Asked Questions</h2>
        <div class="faq-grid">
            <div class="faq-item"><h3>Do I need to know how to code?</h3><p>Not at all. Alfred understands plain English. Say "install WordPress", "create an email account", or "check my site health" — and it's done. Zero commands to learn.</p></div>
            <div class="faq-item"><h3>What's the difference between GoCodeMe and GoSiteMe?</h3><p>GoSiteMe is the hosting company. GoCodeMe is the AI development platform — the IDE and Alfred AI assistant — that's included with your GoSiteMe hosting. They work together. You get both for $15/mo.</p></div>
            <div class="faq-item"><h3>Is this better than Cursor or Replit?</h3><p>For web hosting and development combined, dramatically. Cursor and Replit are great for code but can't manage hosting, domains, email, SSL, or generate images. GoCodeMe does all of that plus 13,000+ tools, persistent memory, voice commands, and hosting is included in the price.</p></div>
            <div class="faq-item"><h3>Can Alfred generate images, videos, and audio?</h3><p>Yes. Alfred uses GoCodeMe's proprietary AI engines to generate photos, logos, hero images, product shots, cinematic videos, and professional voiceovers — all from text descriptions, all saved directly to your site.</p></div>
            <div class="faq-item"><h3>What is the Code Interpreter?</h3><p>Alfred can run real code — Python, Node.js, Bash, Ruby, and PHP — in a secure sandbox on your server. Analyze CSVs, generate charts, process data, run scripts, and see live results. Your data never leaves your server.</p></div>
            <div class="faq-item"><h3>Can Alfred browse the web?</h3><p>Yes. Alfred's browser agent navigates any website, extracts data, fills forms, takes screenshots, and downloads files. Research competitors, monitor prices, scrape data — all by conversation.</p></div>
            <div class="faq-item"><h3>What is Private On-Server AI?</h3><p>Alfred can run AI models directly on your server. Sensitive code, private customer data, confidential documents are processed entirely within your infrastructure — nothing goes to the cloud. Smart routing automatically decides what to process locally vs. in the cloud.</p></div>
            <div class="faq-item"><h3>What is Agent-to-Agent (A2A) protocol?</h3><p>Alfred can discover and collaborate with other AI agents. Delegate specialized tasks — legal review, financial analysis, design generation — to purpose-built remote agents. Alfred coordinates everything and delivers the results. The future of autonomous AI workflows.</p></div>
            <div class="faq-item"><h3>Can I control my site from my phone?</h3><p>Yes. Through WhatsApp, Signal, Discord, Telegram, or SMS. Send "check my traffic" or "install a plugin" from your phone — Alfred responds in seconds. No laptop, no login, no dashboard needed.</p></div>
            <div class="faq-item"><h3>What are tokens and will I run out?</h3><p>Tokens measure AI usage. The Builder plan includes 300,000 tokens/month — roughly 750 conversations. Most users never hit their limit. If you need more, buy a one-time top-up pack (from $5) or upgrade your plan.</p></div>
            <div class="faq-item"><h3>Is WordPress support included?</h3><p>Yes. Alfred includes 11 WordPress tools — install, manage plugins and themes, update everything, optimize the database, search the plugin repository, and run any WP-CLI command — all by conversation.</p></div>
            <div class="faq-item"><h3>Is my data safe?</h3><p>Yes. Alfred is sandboxed to your account — it can only touch YOUR files, YOUR databases, YOUR domains. Impossible to access another customer's data. All connections are encrypted. We don't sell your data or train on your files.</p></div>
            <div class="faq-item"><h3>Can Alfred review my code?</h3><p>Yes. The AI code review engine analyzes your git diffs for bugs, security vulnerabilities, performance issues, and style problems. It scores changes 1-10 and provides specific suggestions with line numbers. Like a senior engineer watching every commit.</p></div>
            <div class="faq-item"><h3>What are Live Artifacts?</h3><p>Artifacts are interactive outputs Alfred generates right inside your editor — Chart.js charts with real data, Mermaid architecture diagrams, live HTML previews with Tailwind and Alpine.js. Ask Alfred to visualize anything and see it instantly, no plugins needed.</p></div>
            <div class="faq-item"><h3>Can I upgrade my plan later?</h3><p>Yes, anytime from your client area. Upgrades are instant. We prorate any remaining balance from your current plan. You can also add Token Top-Up Packs at any time — they're credited instantly and never expire.</p></div>
        </div>
    </div>
</section>

<!-- ═══ VOICE & AI ADD-ONS ═══ -->
<section style="padding:70px 0;background:linear-gradient(180deg,rgba(0,212,255,0.03),transparent);border-top:1px solid rgba(255,255,255,0.04);">
    <div class="container" style="max-width:1100px;margin:0 auto;padding:0 24px;">
        <div style="text-align:center;margin-bottom:36px;">
            <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(0,212,255,0.15);border:1px solid rgba(0,212,255,0.3);padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;color:#06b6d4;margin-bottom:12px;"><i class="fas fa-phone-volume"></i> Boost Your GoCodeMe</span>
            <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.4rem,3vw,2rem);font-weight:700;color:#fff;margin:0 0 10px;">Add Voice & AI to Your Hosting</h2>
            <p style="color:var(--text-muted);max-width:550px;margin:0 auto;">Pair GoCodeMe with AI phone numbers, voice agents, fax, SMS — buy only what you need. À la carte from $3/mo.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:28px;">
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:1.3rem;">📞</div>
                <div style="font-size:0.82rem;color:#fff;font-weight:600;">Phone Numbers</div>
                <div style="font-size:0.95rem;font-weight:800;color:#10b981;">$3/mo</div>
            </div>
            <div style="background:rgba(125,0,255,0.05);border:1px solid rgba(125,0,255,0.2);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:1.3rem;">🤖</div>
                <div style="font-size:0.82rem;color:#fff;font-weight:600;">AI Voice Agents</div>
                <div style="font-size:0.95rem;font-weight:800;color:#7d00ff;">$29/mo</div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:1.3rem;">📠</div>
                <div style="font-size:0.82rem;color:#fff;font-weight:600;">Fax & Docs</div>
                <div style="font-size:0.95rem;font-weight:800;color:#06b6d4;">$15/mo</div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:1.3rem;">💬</div>
                <div style="font-size:0.82rem;color:#fff;font-weight:600;">SMS & Chat</div>
                <div style="font-size:0.95rem;font-weight:800;color:#ff9500;">$19/mo</div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:1.3rem;">🏢</div>
                <div style="font-size:0.82rem;color:#fff;font-weight:600;">Industry AI</div>
                <div style="font-size:0.95rem;font-weight:800;color:#ff3366;">$59/mo</div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;text-align:center;">
                <div style="font-size:1.3rem;">📤</div>
                <div style="font-size:0.82rem;color:#fff;font-weight:600;">Call Centers</div>
                <div style="font-size:0.95rem;font-weight:800;color:#ff3366;">$99/mo</div>
            </div>
        </div>
        <div style="text-align:center;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="/voice-products.php" class="btn btn-primary" style="font-size:0.9rem;"><i class="fas fa-phone-volume" style="margin-right:6px;"></i> Browse 52 Voice Products</a>
            <a href="/alfred.php" class="btn btn-ghost" style="border-color:rgba(0,212,255,0.3);color:#06b6d4;font-size:0.9rem;"><i class="fas fa-robot" style="margin-right:6px;"></i> Meet Alfred AI</a>
        </div>
    </div>
</section>

<!-- ═══ BUSINESS PROGRAMS ═══ -->
<section style="padding:55px 0;background:linear-gradient(180deg,transparent,rgba(125,0,255,0.03));border-top:1px solid rgba(255,255,255,0.04);">
    <div class="container" style="max-width:1100px;margin:0 auto;padding:0 24px;">
        <div style="text-align:center;margin-bottom:28px;">
            <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,215,0,0.15);border:1px solid rgba(255,215,0,0.3);padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;color:#ffd700;margin-bottom:10px;"><i class="fas fa-handshake"></i> Grow With Us</span>
            <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.2rem,2.5vw,1.7rem);font-weight:700;color:#fff;margin:0;">Resell, Refer & Partner</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
            <div style="background:rgba(125,0,255,0.04);border:1px solid rgba(125,0,255,0.12);border-radius:12px;padding:18px;">
                <span style="font-size:1.2rem;">🏷️</span>
                <strong style="color:#fff;margin-left:6px;">White-Label Reseller</strong>
                <span style="color:#a78bfa;font-size:0.85rem;float:right;">$299/mo</span>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:6px 0 0;line-height:1.4;">Your brand, our platform. Unlimited clients, custom pricing.</p>
            </div>
            <div style="background:rgba(16,185,129,0.04);border:1px solid rgba(16,185,129,0.12);border-radius:12px;padding:18px;">
                <span style="font-size:1.2rem;">💸</span>
                <strong style="color:#fff;margin-left:6px;">Affiliate Program</strong>
                <span style="color:#10b981;font-size:0.85rem;float:right;">20% Recurring</span>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:6px 0 0;line-height:1.4;">Refer customers, earn 20% commission. No cap, monthly payouts.</p>
            </div>
            <div style="background:rgba(6,182,212,0.04);border:1px solid rgba(6,182,212,0.12);border-radius:12px;padding:18px;">
                <span style="font-size:1.2rem;">🤝</span>
                <strong style="color:#fff;margin-left:6px;">Agency Partner</strong>
                <span style="color:#06b6d4;font-size:0.85rem;float:right;">Volume Pricing</span>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:6px 0 0;line-height:1.4;">Offer AI hosting to your clients. Priority support included.</p>
            </div>
        </div>
        <p style="text-align:center;margin-top:14px;font-size:0.82rem;color:var(--text-muted);">
            <a href="/voice-products.php#business" style="color:#a78bfa;font-weight:600;">Explore business programs <i class="fas fa-arrow-right" style="margin-left:4px;"></i></a>
            &nbsp;·&nbsp;
            <a href="/affiliates" style="color:#10b981;font-weight:600;">Join Affiliate Program <i class="fas fa-arrow-right" style="margin-left:4px;"></i></a>
        </p>
    </div>
</section>

<!-- ═══ FINAL CTA ═══ -->
<section class="final-cta-section">
    <div class="container">
        <h2>Ready to Let Your Website Run Itself?</h2>
        <p>13,000+ MCP tools. 16 AI engines. Browser agent. Code interpreter. RAG knowledge base. Private on-server AI. Agent-to-Agent protocol. Voice rooms. All-in-one. Starting at $15/month.</p>
        <div class="cta-btns">
            <a href="https://gositeme.com/cart?a=add&pid=18" class="btn btn-primary btn-lg"><i class="fas fa-rocket"></i> Start AI Hosting</a>
            <a href="https://gositeme.com/alfred.php" class="btn btn-outline btn-lg"><i class="fas fa-robot"></i> Meet Alfred</a>
            <a href="#download" class="btn btn-ghost btn-lg"><i class="fas fa-download"></i> Download</a>
        </div>
        <p style="font-size:0.88rem; color:rgba(255,255,255,0.35); margin-top:8px;">Use code <strong style="color:var(--success);">LAUNCH50</strong> for 50% off your first year &middot; No contracts &middot; Cancel anytime</p>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> GoSiteMe &middot; GoCodeMe AI Development Platform &middot;
            <a href="https://gositeme.com">GoSiteMe.com</a> &middot;
            <a href="https://gositeme.com/alfred.php">Meet Alfred</a> &middot;
            <a href="https://gositeme.com/contact">Contact</a> &middot;
            <a href="https://gositeme.com/privacy-policy.php">Privacy</a> &middot;
            1-833-GOSITEME
        </p>
    </div>
</footer>

<!-- ═══ SCHEMA.ORG STRUCTURED DATA ═══ -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "GoCodeMe",
    "applicationCategory": "DeveloperApplication",
    "operatingSystem": "Windows, macOS, Linux, Web Browser",
    "description": "<?php echo htmlspecialchars($page_description, ENT_QUOTES); ?>",
    "url": "https://gocodeme.com/",
    "image": "https://gositeme.com/assets/img/gocodeme-og.png",
    "author": {
        "@type": "Organization",
        "name": "GoSiteMe",
        "url": "https://gositeme.com"
    },
    "offers": {
        "@type": "AggregateOffer",
        "priceCurrency": "CAD",
        "lowPrice": "15",
        "highPrice": "99",
        "offerCount": "5",
        "offers": [
            {"@type": "Offer", "name": "Builder", "price": "15", "priceCurrency": "CAD", "url": "https://gositeme.com/cart?a=add&pid=18", "description": "300K tokens, 1 website, 13,000+ AI tools, full IDE"},
            {"@type": "Offer", "name": "Creator", "price": "22", "priceCurrency": "CAD", "url": "https://gositeme.com/cart?a=add&pid=32", "description": "450K tokens, 3 websites, 30GB NVMe, priority support"},
            {"@type": "Offer", "name": "Professional", "price": "29", "priceCurrency": "CAD", "url": "https://gositeme.com/cart?a=add&pid=19", "description": "600K tokens, 5 websites, Git + SSH/SFTP, database management"},
            {"@type": "Offer", "name": "Studio", "price": "59", "priceCurrency": "CAD", "url": "https://gositeme.com/cart?a=add&pid=20", "description": "1.5M tokens, 10 websites, team sharing, premium models"},
            {"@type": "Offer", "name": "Business", "price": "99", "priceCurrency": "CAD", "url": "https://gositeme.com/cart?a=add&pid=21", "description": "3M tokens, 25 websites, SSO/SAML, 10 parallel AI sessions"}
        ]
    },
    "featureList": "13,000+ MCP Tools, 16 AI Engines, Full Browser IDE, AI Image Generation, AI Video & Audio, Browser Agent (NEXUS), Code Interpreter (FORGE), RAG Knowledge Base (CORTEX), WordPress Management, Voice Commands, WhatsApp/Signal/Discord/Telegram, Agent-to-Agent Protocol, Private On-Server AI, Git Version Control, Live Charts & Artifacts, Voice Rooms, VR Game Engine, SSP Music & Events, Solana Crypto Payments",
    "softwareVersion": "1.99",
    "datePublished": "2025-01-01",
    "dateModified": "<?php echo date('Y-m-d'); ?>"
}
</script>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {"@type": "Question", "name": "Do I need to know how to code?", "acceptedAnswer": {"@type": "Answer", "text": "Not at all. Alfred understands plain English. Say 'install WordPress', 'create an email account', or 'check my site health' — and it's done. Zero commands to learn."}},
        {"@type": "Question", "name": "What's the difference between GoCodeMe and GoSiteMe?", "acceptedAnswer": {"@type": "Answer", "text": "GoSiteMe is the hosting company. GoCodeMe is the AI development platform — the IDE and Alfred AI assistant — that's included with your GoSiteMe hosting. They work together. You get both for $15/mo."}},
        {"@type": "Question", "name": "Is this better than Cursor or Replit?", "acceptedAnswer": {"@type": "Answer", "text": "For web hosting and development combined, dramatically. Cursor and Replit are great for code but can't manage hosting, domains, email, SSL, or generate images. GoCodeMe does all of that plus 13,000+ tools, persistent memory, voice commands, and hosting is included in the price."}},
        {"@type": "Question", "name": "Can Alfred generate images, videos, and audio?", "acceptedAnswer": {"@type": "Answer", "text": "Yes. Alfred uses GoCodeMe's proprietary AI engines to generate photos, logos, hero images, product shots, cinematic videos, and professional voiceovers — all from text descriptions, all saved directly to your site."}},
        {"@type": "Question", "name": "What is the Code Interpreter?", "acceptedAnswer": {"@type": "Answer", "text": "Alfred can run real code — Python, Node.js, Bash, Ruby, and PHP — in a secure sandbox on your server. Analyze CSVs, generate charts, process data, run scripts, and see live results. Your data never leaves your server."}},
        {"@type": "Question", "name": "Can Alfred browse the web?", "acceptedAnswer": {"@type": "Answer", "text": "Yes. Alfred's browser agent navigates any website, extracts data, fills forms, takes screenshots, and downloads files. Research competitors, monitor prices, scrape data — all by conversation."}},
        {"@type": "Question", "name": "What is Private On-Server AI?", "acceptedAnswer": {"@type": "Answer", "text": "Alfred can run AI models directly on your server. Sensitive code, private customer data, confidential documents are processed entirely within your infrastructure — nothing goes to the cloud."}},
        {"@type": "Question", "name": "What is Agent-to-Agent (A2A) protocol?", "acceptedAnswer": {"@type": "Answer", "text": "Alfred can discover and collaborate with other AI agents. Delegate specialized tasks — legal review, financial analysis, design generation — to purpose-built remote agents. Alfred coordinates everything and delivers the results."}},
        {"@type": "Question", "name": "Can I control my site from my phone?", "acceptedAnswer": {"@type": "Answer", "text": "Yes. Through WhatsApp, Signal, Discord, Telegram, or SMS. Send 'check my traffic' or 'install a plugin' from your phone — Alfred responds in seconds."}},
        {"@type": "Question", "name": "What are tokens and will I run out?", "acceptedAnswer": {"@type": "Answer", "text": "Tokens measure AI usage. The Builder plan includes 300,000 tokens/month — roughly 750 conversations. Most users never hit their limit. If you need more, buy a one-time top-up pack (from $5) or upgrade your plan."}},
        {"@type": "Question", "name": "Is WordPress support included?", "acceptedAnswer": {"@type": "Answer", "text": "Yes. Alfred includes 11 WordPress tools — install, manage plugins and themes, update everything, optimize the database, search the plugin repository, and run any WP-CLI command — all by conversation."}},
        {"@type": "Question", "name": "Is my data safe?", "acceptedAnswer": {"@type": "Answer", "text": "Yes. Alfred is sandboxed to your account — it can only touch YOUR files, YOUR databases, YOUR domains. Impossible to access another customer's data. All connections are encrypted."}},
        {"@type": "Question", "name": "Can Alfred review my code?", "acceptedAnswer": {"@type": "Answer", "text": "Yes. The AI code review engine analyzes your git diffs for bugs, security vulnerabilities, performance issues, and style problems. It scores changes 1-10 and provides specific suggestions with line numbers."}},
        {"@type": "Question", "name": "What are Live Artifacts?", "acceptedAnswer": {"@type": "Answer", "text": "Artifacts are interactive outputs Alfred generates right inside your editor — Chart.js charts with real data, Mermaid architecture diagrams, live HTML previews with Tailwind and Alpine.js."}},
        {"@type": "Question", "name": "Can I upgrade my plan later?", "acceptedAnswer": {"@type": "Answer", "text": "Yes, anytime from your client area. Upgrades are instant. We prorate any remaining balance from your current plan. You can also add Token Top-Up Packs at any time."}}
    ]
}
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
