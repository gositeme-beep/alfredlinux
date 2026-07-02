<?php
require_once __DIR__ . "/includes/lang.php";
require_once __DIR__ . '/includes/lang_alfred.php';

// Handle plan redirect from pricing page
$selectedPlan = $_GET['plan'] ?? null;
$selectedBilling = $_GET['billing'] ?? 'monthly';

$page_title       = L('alf_meta_title');
$page_description = L('alf_meta_desc');
$page_canonical   = 'https://root.com/alfred.php';
$page_og_url      = $page_canonical;
include __DIR__ . '/includes/site-header.inc.php';
$li = ($current_lang === 'fr') ? 1 : 0; // language index for tool arrays: 0=EN, 1=FR
?>

<style>
/* ═══════════════════════════════════════════════════════════════════
   ALFRED — THE WORLD'S FIRST AI HOSTING ASSISTANT
   ═══════════════════════════════════════════════════════════════════ */

@keyframes pulse-glow { 0%,100% { box-shadow: 0 0 20px rgba(125,0,255,0.3); } 50% { box-shadow: 0 0 40px rgba(125,0,255,0.6); } }
@keyframes float-up { 0% { opacity:0; transform:translateY(30px) } 100% { opacity:1; transform:translateY(0) } }
@keyframes shimmer { 0% { background-position: -200% 0 } 100% { background-position: 200% 0 } }
@keyframes count-up { from { opacity:0; transform:scale(0.5) } to { opacity:1; transform:scale(1) } }

.alfred-hero { padding: 140px 0 100px; text-align: center; position: relative; overflow: hidden; }
.alfred-hero::before {
    content: ''; position: absolute; top: -300px; left: 50%; transform: translateX(-50%);
    width: 1000px; height: 1000px;
    background: radial-gradient(circle, rgba(125,0,255,0.2) 0%, rgba(0,212,255,0.05) 40%, transparent 70%);
    pointer-events: none; animation: pulse-glow 4s ease-in-out infinite;
}
.world-first-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 10px 24px; border-radius: 100px;
    background: linear-gradient(135deg, rgba(125,0,255,0.2), rgba(0,212,255,0.2));
    border: 1px solid rgba(0,212,255,0.4);
    color: #00D4FF; font-size: 0.9rem; font-weight: 700;
    margin-bottom: 32px; letter-spacing: 1px; text-transform: uppercase;
    animation: float-up 0.6s ease-out;
}
.alfred-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.8rem, 6vw, 4.5rem); font-weight: 900; line-height: 1.05; margin-bottom: 28px;
    background: linear-gradient(135deg, #fff 0%, #c084fc 40%, #00D4FF 80%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.alfred-hero .hero-sub { font-size: 1.35rem; color: var(--text-muted); max-width: 780px; margin: 0 auto 20px; line-height: 1.7; }
.alfred-hero .hero-sub2 { font-size: 1.05rem; color: rgba(255,255,255,0.5); max-width: 600px; margin: 0 auto 44px; line-height: 1.5; }
.hero-ctas { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 12px; }
.hero-ctas .btn { padding: 16px 36px; font-size: 1.05rem; font-weight: 700; border-radius: 14px; }
.hero-ctas .btn-primary { position: relative; overflow: hidden; }
.hero-ctas .btn-primary::after { content: ''; position: absolute; top: 0; left: -200%; width: 200%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent); animation: shimmer 3s ease-in-out infinite; }
.hero-ctas .btn-voice { background: linear-gradient(135deg, rgba(125,0,255,0.3), rgba(0,212,255,0.3)); border: 1px solid rgba(0,212,255,0.4); color: #fff; transition: all 0.3s ease; }
.hero-ctas .btn-voice:hover { background: linear-gradient(135deg, rgba(125,0,255,0.5), rgba(0,212,255,0.5)); border-color: rgba(0,212,255,0.7); transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,212,255,0.3); }
.hero-ctas .btn-voice i { margin-right: 8px; }
.hero-price-note { text-align: center; color: rgba(255,255,255,0.4); font-size: 0.9rem; margin-bottom: 60px; }
.hero-price-note strong { color: #10b981; }
.hero-stats { display: flex; justify-content: center; gap: 48px; flex-wrap: wrap; padding: 32px 0; border-top: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06); }
.hero-stats .stat { text-align: center; }
.hero-stats .stat-num { font-family: 'Space Grotesk', sans-serif; font-size: 2.5rem; font-weight: 800; color: #fff; display: block; animation: count-up 0.8s ease-out; }
.hero-stats .stat-num .accent { color: #00D4FF; }
.hero-stats .stat-label { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; }

.commercial-section { padding: 100px 0; position: relative; }
.commercial-section .container { max-width: 900px; margin: 0 auto; padding: 0 24px; }
.scene-label { text-align: center; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 3px; color: #c084fc; font-weight: 700; margin-bottom: 16px; }
.commercial-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 16px; line-height: 1.15; }
.commercial-section .scene-intro { text-align: center; font-size: 1.15rem; color: var(--text-muted); max-width: 650px; margin: 0 auto 48px; line-height: 1.7; }
.scene-timeline { position: relative; padding-left: 36px; }
.scene-timeline::before { content: ''; position: absolute; left: 14px; top: 0; bottom: 0; width: 2px; background: linear-gradient(180deg, rgba(125,0,255,0.5), rgba(0,212,255,0.5)); }
.scene-step { position: relative; margin-bottom: 48px; }
.scene-step::before { content: ''; position: absolute; left: -29px; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--cyan)); box-shadow: 0 0 12px rgba(125,0,255,0.5); }
.voice-cmd { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, rgba(125,0,255,0.15), rgba(125,0,255,0.25)); border: 1px solid rgba(125,0,255,0.3); border-radius: 12px; padding: 12px 20px; font-size: 1rem; color: #e2d1f9; margin-bottom: 12px; font-style: italic; }
.voice-cmd i { color: #c084fc; }
.result { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 16px 20px; font-size: 0.95rem; color: var(--text-light); line-height: 1.7; }
.result strong { color: #10b981; }
.result .tag { display: inline-block; margin: 4px 4px 4px 0; padding: 2px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; }
.tag.green { background: rgba(16,185,129,0.15); color: #10b981; }
.tag.purple { background: rgba(125,0,255,0.15); color: #c084fc; }
.tag.cyan { background: rgba(0,212,255,0.15); color: #00D4FF; }
.tag.orange { background: rgba(251,146,60,0.15); color: #fb923c; }
.tag.red { background: rgba(239,68,68,0.15); color: #ef4444; }

.comparison-section { padding: 100px 0; }
.comparison-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 12px; }
.section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto 48px; }

.imggen-section { padding: 100px 0; }
.imggen-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 12px; }
.imggen-section .section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 650px; margin: 0 auto 48px; }
.imggen-demo { max-width: 800px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.imggen-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; overflow: hidden; }
.imggen-card .prompt-bar { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.88rem; color: #c084fc; }
.imggen-card .prompt-bar i { margin-right: 8px; color: var(--cyan); }
.imggen-card .preview { height: 200px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(125,0,255,0.1), rgba(0,212,255,0.1)); position: relative; }
.imggen-card .preview .placeholder { font-size: 3.5rem; opacity: 0.6; }
.imggen-card .preview .gen-label { position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.6); padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; color: #10b981; font-weight: 600; }
.imggen-card .meta { padding: 12px 20px; font-size: 0.82rem; color: var(--text-muted); display: flex; justify-content: space-between; }

.tokens-section { padding: 100px 0; }
.tokens-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 12px; }
.tokens-section .section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 650px; margin: 0 auto 48px; }
.token-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; max-width: 1000px; margin: 0 auto; }
.token-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 28px 24px; text-align: center; transition: transform 0.3s, border-color 0.3s; }
.token-card:hover { transform: translateY(-4px); border-color: rgba(125,0,255,0.3); }
.token-card.featured { border-color: rgba(0,212,255,0.4); background: linear-gradient(135deg, rgba(0,212,255,0.05), rgba(125,0,255,0.05)); }
.token-card .plan-name { font-weight: 700; font-size: 1.05rem; color: #fff; margin-bottom: 6px; }
.token-card .plan-price { font-family: 'Space Grotesk', sans-serif; font-size: 2rem; font-weight: 800; color: var(--cyan); margin-bottom: 4px; }
.token-card .plan-price .mo { font-size: 0.9rem; color: var(--text-muted); font-weight: 400; }
.token-card .token-count { font-size: 0.95rem; color: #c084fc; font-weight: 600; margin-bottom: 16px; }
.token-card .token-equiv { font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; }
.token-card .token-equiv strong { color: rgba(255,255,255,0.7); }

.tools-overview { padding: 100px 0; }
.tools-overview h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 12px; }
.tools-overview .section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 660px; margin: 0 auto 60px; }
.tool-category { margin-bottom: 56px; }
.cat-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.06); }
.cat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
.cat-icon.purple { background: rgba(125,0,255,0.15); color: #c084fc; }
.cat-icon.cyan { background: rgba(0,212,255,0.15); color: #00D4FF; }
.cat-icon.green { background: rgba(16,185,129,0.15); color: #10b981; }
.cat-icon.orange { background: rgba(251,146,60,0.15); color: #fb923c; }
.cat-icon.blue { background: rgba(59,130,246,0.15); color: #3b82f6; }
.cat-icon.red { background: rgba(239,68,68,0.15); color: #ef4444; }
.cat-icon.pink { background: rgba(236,72,153,0.15); color: #ec4899; }
.cat-icon.yellow { background: rgba(250,204,21,0.15); color: #facc15; }
.cat-icon.indigo { background: rgba(99,102,241,0.15); color: #818cf8; }
.cat-icon.teal { background: rgba(20,184,166,0.15); color: #14b8a6; }
.cat-title { font-size: 1.2rem; font-weight: 700; color: #fff; }
.cat-count { font-size: 0.82rem; color: var(--text-muted); font-weight: 400; }
.tool-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.tool-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 18px 20px; transition: border-color 0.3s, background 0.3s; }
.tool-card:hover { border-color: rgba(125,0,255,0.25); background: rgba(255,255,255,0.04); }
.tool-card .tool-name { font-weight: 700; font-size: 0.92rem; color: #fff; margin-bottom: 6px; }
.tool-card .tool-desc { font-size: 0.84rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 10px; }
.tool-card .try-saying { font-size: 0.8rem; color: #c084fc; font-style: italic; }
.tool-card .try-saying::before { content: '\1F4AC '; }

.safety-section { padding: 100px 0; }
.safety-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 12px; }
.safety-section .section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto 48px; }
.safety-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1000px; margin: 0 auto; }
.safety-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 28px 24px; }
.safety-card .icon { font-size: 1.6rem; margin-bottom: 14px; }
.safety-card h3 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
.safety-card p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }

.addons-section { padding: 100px 0; }
.addons-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; margin-bottom: 12px; }
.addons-section .section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 700px; margin: 0 auto 48px; }
.addons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; }
.addon-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px; transition: transform 0.3s, border-color 0.3s; }
.addon-card:hover { transform: translateY(-4px); border-color: rgba(16,185,129,0.4); }
.addon-card .addon-icon { font-size: 1.5rem; margin-bottom: 12px; }
.addon-card h3 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
.addon-card .addon-price { font-size: 1.1rem; font-weight: 700; color: var(--cyan); margin-bottom: 8px; }
.addon-card p { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; }
.topup-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; max-width: 900px; margin: 0 auto 48px; }
.topup-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px; text-align: center; transition: transform 0.3s, border-color 0.3s; }
.topup-card:hover { transform: translateY(-4px); border-color: rgba(0,212,255,0.4); }
.topup-card .topup-amount { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 4px; }
.topup-card .topup-price { font-size: 1.1rem; font-weight: 700; color: var(--cyan); margin-bottom: 8px; }
.topup-card .topup-note { font-size: 0.82rem; color: var(--text-muted); }
.topup-card .topup-savings { font-size: 0.78rem; color: #10b981; font-weight: 600; margin-top: 6px; }

.final-cta { padding: 100px 0; text-align: center; position: relative; }
.final-cta::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 600px; height: 600px; background: radial-gradient(circle, rgba(125,0,255,0.12) 0%, transparent 70%); pointer-events: none; }
.final-cta h2 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: #fff; margin-bottom: 16px; }
.final-cta .subtitle { font-size: 1.15rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 36px; line-height: 1.7; }
.cta-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 16px; }
.cta-buttons .btn { padding: 16px 40px; font-size: 1.05rem; font-weight: 700; border-radius: 14px; }
.final-cta .promo { font-size: 0.9rem; color: rgba(255,255,255,0.4); }
.final-cta .promo strong { color: #10b981; }

.faq-section { padding: 80px 0 100px; }
.faq-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 700; color: #fff; margin-bottom: 40px; }
.faq-grid { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; }
.faq-item { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 20px 24px; }
.faq-item h3 { font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
.faq-item p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }

/* ─── VOICE COMMANDS & MESSAGING ─── */
.voice-msg-section { padding: 120px 0; position: relative; overflow: hidden; }
.voice-msg-section::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 900px; height: 900px; background: radial-gradient(circle, rgba(125,0,255,0.08) 0%, rgba(0,212,255,0.04) 40%, transparent 70%); pointer-events: none; }
.voice-msg-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: #fff; margin-bottom: 12px; line-height: 1.15; }
.voice-msg-section .section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 700px; margin: 0 auto 20px; line-height: 1.7; }
.cherry-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; border-radius: 100px; background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(251,146,60,0.15)); border: 1px solid rgba(251,146,60,0.3); color: #fb923c; font-size: 0.82rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 24px; }
.platform-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; max-width: 900px; margin: 0 auto 56px; }
.platform-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px 16px; text-align: center; transition: transform 0.3s, border-color 0.3s; }
.platform-card:hover { transform: translateY(-4px); border-color: rgba(0,212,255,0.3); }
.platform-card .p-icon { font-size: 2rem; margin-bottom: 12px; display: block; }
.platform-card .p-name { font-weight: 700; font-size: 0.95rem; color: #fff; margin-bottom: 4px; }
.platform-card .p-status { font-size: 0.78rem; color: #10b981; font-weight: 600; }
.msg-demos { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px; }
.msg-demo { display: flex; gap: 16px; align-items: flex-start; }
.msg-demo .msg-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.msg-demo .msg-icon.whatsapp { background: rgba(37,211,102,0.15); color: #25d366; }
.msg-demo .msg-icon.signal { background: rgba(59,130,246,0.15); color: #3b82f6; }
.msg-demo .msg-icon.discord { background: rgba(88,101,242,0.15); color: #5865f2; }
.msg-demo .msg-icon.sms { background: rgba(16,185,129,0.15); color: #10b981; }
.msg-demo .msg-body { flex: 1; }
.msg-bubble { display: inline-block; padding: 12px 18px; border-radius: 16px 16px 16px 4px; font-size: 0.92rem; margin-bottom: 8px; font-style: italic; background: linear-gradient(135deg, rgba(125,0,255,0.2), rgba(125,0,255,0.3)); border: 1px solid rgba(125,0,255,0.3); color: #e2d1f9; }
.msg-reply { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 4px 16px 16px 16px; padding: 12px 18px; font-size: 0.88rem; color: var(--text-light); line-height: 1.6; }
.msg-reply strong { color: #10b981; }
.voice-kicker { text-align: center; margin-top: 56px; padding: 36px; background: linear-gradient(135deg, rgba(125,0,255,0.06), rgba(0,212,255,0.06)); border: 1px solid rgba(0,212,255,0.15); border-radius: 20px; max-width: 720px; margin-left: auto; margin-right: auto; }
.voice-kicker p { font-size: 1.15rem; color: var(--text-muted); line-height: 1.8; }
.voice-kicker p strong { color: #fff; }
.voice-kicker .accent { color: #00D4FF; }

/* ─── INTELLIGENCE ENGINES ─── */
.engines-section { padding: 120px 0; position: relative; overflow: hidden; }
.engines-section::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 1000px; height: 1000px; background: radial-gradient(circle, rgba(125,0,255,0.1) 0%, rgba(0,212,255,0.05) 30%, transparent 60%); pointer-events: none; }
.engines-section h2 { text-align: center; font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem, 4.5vw, 3.2rem); font-weight: 800; line-height: 1.15; margin-bottom: 12px; background: linear-gradient(135deg, #fff 0%, #c084fc 50%, #00D4FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.engines-section .section-sub { text-align: center; color: var(--text-muted); font-size: 1.1rem; max-width: 700px; margin: 0 auto 56px; line-height: 1.7; }
.engine-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto 40px; }
.engine-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 32px 28px; transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; position: relative; overflow: hidden; }
.engine-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #7d00ff, #00D4FF); opacity: 0; transition: opacity 0.3s; }
.engine-card:hover { transform: translateY(-4px); border-color: rgba(125,0,255,0.3); box-shadow: 0 12px 40px rgba(125,0,255,0.15); }
.engine-card:hover::before { opacity: 1; }
.engine-icon { font-size: 2.2rem; margin-bottom: 16px; }
.engine-card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.25rem; font-weight: 800; color: #fff; letter-spacing: 2px; margin-bottom: 4px; }
.engine-subtitle { font-size: 0.85rem; color: #c084fc; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px; }
.engine-card p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.65; }
.engine-plus { max-width: 1100px; margin: 0 auto; background: linear-gradient(135deg, rgba(125,0,255,0.06), rgba(0,212,255,0.06)); border: 1px solid rgba(0,212,255,0.15); border-radius: 20px; padding: 32px 36px; text-align: center; }
.engine-plus h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 10px; }
.engine-plus p { font-size: 0.95rem; color: var(--text-muted); line-height: 1.7; max-width: 700px; margin: 0 auto; }

/* ─── NEW: PRIVATE AI / ON-SERVER ─── */
.private-ai-section { padding: 100px 0; position: relative; }
.private-ai-section::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 800px; height: 800px; background: radial-gradient(circle, rgba(16,185,129,0.06) 0%, transparent 65%); pointer-events: none; }
.private-ai-demo { max-width: 860px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.pai-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(16,185,129,0.15); border-radius: 18px; padding: 28px 24px; transition: border-color 0.3s, transform 0.3s; }
.pai-card:hover { border-color: rgba(16,185,129,0.35); transform: translateY(-3px); }
.pai-icon { font-size: 2rem; margin-bottom: 14px; }
.pai-card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
.pai-card p { font-size: 0.87rem; color: var(--text-muted); line-height: 1.6; }
.pai-badge { display: inline-block; margin-top: 10px; padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; background: rgba(16,185,129,0.15); color: #10b981; }

/* ─── NEW: A2A PROTOCOL ─── */
.a2a-section { padding: 100px 0; position: relative; overflow: hidden; }
.a2a-section::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 900px; height: 900px; background: radial-gradient(circle, rgba(99,102,241,0.08) 0%, transparent 65%); pointer-events: none; }
.a2a-demo { max-width: 900px; margin: 56px auto 0; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
.a2a-step { background: rgba(255,255,255,0.02); border: 1px solid rgba(99,102,241,0.2); border-radius: 16px; padding: 22px 24px; }
.a2a-step .a2a-cmd { font-size: 0.95rem; color: #818cf8; font-style: italic; margin-bottom: 10px; }
.a2a-step .a2a-cmd i { margin-right: 8px; }
.a2a-step .a2a-res { font-size: 0.88rem; color: var(--text-muted); line-height: 1.65; }
.a2a-step .a2a-res strong { color: #10b981; }
.a2a-network { display: flex; flex-direction: column; gap: 14px; }
.a2a-node { display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 14px 18px; transition: border-color 0.3s; }
.a2a-node:hover { border-color: rgba(99,102,241,0.3); }
.a2a-node .a2a-dot { width: 10px; height: 10px; border-radius: 50%; background: #818cf8; flex-shrink: 0; box-shadow: 0 0 8px rgba(99,102,241,0.5); }
.a2a-node .a2a-name { font-size: 0.9rem; font-weight: 600; color: #fff; }
.a2a-node .a2a-cap { font-size: 0.78rem; color: var(--text-muted); }

/* ─── NEW: CHARTS & ARTIFACTS ─── */
.artifacts-section { padding: 100px 0; }
.artifacts-demo { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.artifact-card { background: var(--dark-card); border: 1px solid rgba(236,72,153,0.15); border-radius: 16px; overflow: hidden; transition: transform 0.3s, border-color 0.3s; }
.artifact-card:hover { transform: translateY(-4px); border-color: rgba(236,72,153,0.35); }
.artifact-preview { height: 140px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(236,72,153,0.08), rgba(99,102,241,0.08)); position: relative; }
.artifact-preview .art-icon { font-size: 3rem; opacity: 0.7; }
.artifact-preview .art-badge { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; color: #ec4899; font-weight: 600; }
.artifact-info { padding: 14px 16px; }
.artifact-info .art-type { font-size: 0.78rem; color: #ec4899; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.artifact-info .art-desc { font-size: 0.84rem; color: var(--text-muted); }

/* ─── NEW: VOICE ROOMS ─── */
.voice-rooms-section { padding: 100px 0; }
.vr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; max-width: 900px; margin: 0 auto; }
.vr-card { background: var(--dark-card); border: 1px solid rgba(125,0,255,0.15); border-radius: 16px; padding: 28px 24px; text-align: center; transition: transform 0.3s, border-color 0.3s; }
.vr-card:hover { transform: translateY(-4px); border-color: rgba(125,0,255,0.35); }
.vr-card .vr-icon { font-size: 2.2rem; margin-bottom: 14px; }
.vr-card h3 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
.vr-card p { font-size: 0.86rem; color: var(--text-muted); line-height: 1.6; }

@media (max-width: 768px) {
    .alfred-hero { padding: 100px 0 60px; }
    .hero-stats { gap: 24px; }
    .hero-stats .stat-num { font-size: 1.8rem; }
    .scene-timeline { padding-left: 28px; }
    .tool-grid { grid-template-columns: 1fr; }
    .token-grid { grid-template-columns: 1fr 1fr; }
    .platform-grid { grid-template-columns: repeat(3, 1fr); }
    .msg-demo { flex-direction: column; }
    .msg-demo .msg-icon { width: 40px; height: 40px; }
    .engine-grid { grid-template-columns: 1fr; }
    .private-ai-demo { grid-template-columns: 1fr; }
    .a2a-demo { grid-template-columns: 1fr; }
    .imggen-demo { grid-template-columns: 1fr; }
}
@media (max-width: 480px) { .token-grid { grid-template-columns: 1fr; } .platform-grid { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="container">

<?php if ($selectedPlan): 
    $planNames = ['starter' => 'Starter', 'professional' => 'Professional', 'enterprise' => 'Enterprise', 'enterprise_plus' => 'Enterprise Plus'];
    $planLabel = $planNames[$selectedPlan] ?? ucfirst(htmlspecialchars($selectedPlan));
?>
<div style="background:linear-gradient(135deg,rgba(108,92,231,0.15),rgba(0,212,255,0.1));border:1px solid rgba(108,92,231,0.3);border-radius:12px;padding:20px 28px;margin:120px auto 0;max-width:600px;text-align:center;">
    <p style="margin:0 0 12px;color:#a29bfe;font-weight:600;font-size:1.1rem;"><i class="fas fa-check-circle" style="margin-right:6px;"></i>You selected the <?php echo $planLabel; ?> plan (<?php echo htmlspecialchars($selectedBilling); ?>)</p>
    <p style="margin:0 0 16px;color:#8a8a9a;font-size:0.9rem;">Sign in or create an account to start your subscription.</p>
    <a href="#" onclick="openModal('loginModal');return false;" class="btn btn-primary" style="margin-right:8px;">Sign In</a>
    <a href="#" onclick="openModal('registerModal');return false;" class="btn btn-voice">Create Account</a>
</div>
<?php endif; ?>

<!-- ═══ HERO ═══ -->
<section class="alfred-hero">
    <div class="world-first-badge"><i class="fas fa-crown"></i> <?php echo L('alf_hero_badge'); ?></div>
    <h1><?php echo L('alf_hero_h1'); ?></h1>
    <p class="hero-sub"><?php echo L('alf_hero_sub'); ?></p>
    <p class="hero-sub2"><?php echo L('alf_hero_sub2'); ?></p>
    <div class="hero-ctas">
        <a href="<?php echo billing_link('cart.php?a=add&pid=18'); ?>" class="btn btn-primary"><?php echo L('alf_hero_cta_start'); ?></a>
        <a href="/alfred-voice-live/" class="btn btn-voice"><i class="fas fa-microphone"></i> Try Alfred Voice</a>
        <a href="#commercial" class="btn btn-ghost"><?php echo L('alf_hero_cta_watch'); ?> <i class="fas fa-play-circle" style="margin-left:6px;"></i></a>
    </div>
    <p class="hero-price-note"><?php echo L('alf_hero_promo'); ?></p>
    <div class="hero-stats">
        <div class="stat"><span class="stat-num"><span class="accent">13,000+</span></span><span class="stat-label"><?php echo L('alf_stat_tools'); ?></span></div>
        <div class="stat"><span class="stat-num"><span class="accent">17+</span></span><span class="stat-label"><?php echo L('alf_engines_label'); ?></span></div>
        <div class="stat"><span class="stat-num">89</span><span class="stat-label"><?php echo L('alf_stat_categories'); ?></span></div>
        <div class="stat"><span class="stat-num">0</span><span class="stat-label"><?php echo L('alf_stat_commands'); ?></span></div>
        <div class="stat"><span class="stat-num">24/7</span><span class="stat-label"><?php echo L('alf_stat_always'); ?></span></div>
        <div class="stat"><span class="stat-num"><span class="accent">&infin;</span></span><span class="stat-label"><?php echo L('alf_stat_possibilities'); ?></span></div>
    </div>
</section>

<!-- ═══ THE COMMERCIAL ═══ -->
<section class="commercial-section" id="commercial">
    <div class="container">
        <div class="scene-label"><?php echo L('alf_scene_label'); ?></div>
        <h2><?php echo L('alf_scene_h2'); ?></h2>
        <p class="scene-intro"><?php echo L('alf_scene_intro'); ?></p>
        <div class="scene-timeline">
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v1'); ?></div>
                <div class="result"><?php echo L('alf_scene_r1'); ?><br><span class="tag green">install-wordpress</span> <span class="tag cyan">manage-ssl</span> <span class="tag purple">manage-dns</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v2'); ?></div>
                <div class="result"><?php echo L('alf_scene_r2'); ?><br><span class="tag green">search-plugins</span> <span class="tag purple">install-plugin</span> <span class="tag cyan">manage-wordpress</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v3'); ?></div>
                <div class="result"><?php echo L('alf_scene_r3'); ?><br><span class="tag green">generate-image</span> <span class="tag purple">Only on GoSiteMe</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v4'); ?></div>
                <div class="result"><?php echo L('alf_scene_r4'); ?><br><span class="tag green">create-email</span> <span class="tag cyan">manage-email</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v5'); ?></div>
                <div class="result"><?php echo L('alf_scene_r5'); ?><br><span class="tag green">check-site-health</span> <span class="tag cyan">security-scan</span> <span class="tag purple">audit-permissions</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v6'); ?></div>
                <div class="result"><?php echo L('alf_scene_r6'); ?><br><span class="tag green">create-backup</span> <span class="tag purple">git-commit</span> <span class="tag cyan">git-init</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v7'); ?></div>
                <div class="result"><?php echo L('alf_scene_r7'); ?><br><span class="tag green">code-review</span> <span class="tag purple">v2 Engine</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v8'); ?></div>
                <div class="result"><?php echo L('alf_scene_r8'); ?><br><span class="tag green">alfred-remember</span> <span class="tag purple">ELEPHANT Memory</span></div>
            </div>
            <div class="scene-step">
                <div class="voice-cmd"><i class="fas fa-microphone"></i> <?php echo L('alf_scene_v9'); ?></div>
                <div class="result"><?php echo L('alf_scene_r9'); ?><br><span class="tag green">schedule-task</span> <span class="tag cyan">run-playbook</span> <span class="tag purple">CLOCKWORK</span></div>
            </div>
            <div class="scene-step" style="border-left-color: rgba(0,212,255,0.6);">
                <div class="voice-cmd" style="background:linear-gradient(135deg, rgba(0,212,255,0.15), rgba(0,212,255,0.25)); border-color:rgba(0,212,255,0.3);"><i class="fas fa-globe"></i> <?php echo L('alf_scene_v10'); ?></div>
                <div class="result"><?php echo L('alf_scene_r10'); ?><br><span class="tag cyan">browser-navigate</span> <span class="tag green">browser-extract</span> <span class="tag purple">v9 BROWSER AGENT</span></div>
            </div>
            <div class="scene-step" style="border-left-color: rgba(16,185,129,0.6);">
                <div class="voice-cmd" style="background:linear-gradient(135deg, rgba(16,185,129,0.15), rgba(16,185,129,0.25)); border-color:rgba(16,185,129,0.3);"><i class="fas fa-code"></i> <?php echo L('alf_scene_v11'); ?></div>
                <div class="result"><?php echo L('alf_scene_r11'); ?><br><span class="tag green">execute-code</span> <span class="tag cyan">create-artifact</span> <span class="tag purple">v9 CODE INTERPRETER</span></div>
            </div>
            <div class="scene-step" style="border-left-color: rgba(251,146,60,0.6);">
                <div class="voice-cmd" style="background:linear-gradient(135deg, rgba(251,146,60,0.15), rgba(251,146,60,0.25)); border-color:rgba(251,146,60,0.3);"><i class="fas fa-brain"></i> <?php echo L('alf_scene_v12'); ?></div>
                <div class="result"><?php echo L('alf_scene_r12'); ?><br><span class="tag orange">rag-ingest</span> <span class="tag green">rag-query</span> <span class="tag purple">v9 RAG KNOWLEDGE</span></div>
            </div>
            <div class="scene-step" style="border-left-color: rgba(239,68,68,0.6);">
                <div class="voice-cmd" style="background:linear-gradient(135deg, rgba(239,68,68,0.15), rgba(239,68,68,0.25)); border-color:rgba(239,68,68,0.3);"><i class="fas fa-shield-alt"></i> <?php echo L('alf_scene_v13'); ?></div>
                <div class="result"><?php echo L('alf_scene_r13'); ?><br><span class="tag red">monitor-start</span> <span class="tag green">auto-heal</span> <span class="tag purple">v9 PROACTIVE MONITORING</span></div>
            </div>
            <!-- NEW demo steps -->
            <div class="scene-step" style="border-left-color: rgba(236,72,153,0.6);">
                <div class="voice-cmd" style="background:linear-gradient(135deg, rgba(236,72,153,0.15), rgba(236,72,153,0.25)); border-color:rgba(236,72,153,0.3);"><i class="fas fa-chart-bar"></i> "Show my traffic this month as a bar chart"</div>
                <div class="result"><strong>&check; Chart generated.</strong> Interactive bar chart created — 31 days, 24,847 total visits, peak on Tuesday. View live artifact in your editor. Export as PNG or embed on your site.<br><span class="tag" style="background:rgba(236,72,153,0.15);color:#ec4899;">create-chart</span> <span class="tag" style="background:rgba(236,72,153,0.15);color:#ec4899;">LIVE ARTIFACTS</span></div>
            </div>
            <div class="scene-step" style="border-left-color: rgba(99,102,241,0.6);">
                <div class="voice-cmd" style="background:linear-gradient(135deg, rgba(99,102,241,0.15), rgba(99,102,241,0.25)); border-color:rgba(99,102,241,0.3);"><i class="fas fa-plug"></i> "Connect to GitHub and create an issue for the bug we just found"</div>
                <div class="result"><strong>&check; Connected &amp; done.</strong> MCP Gateway linked to GitHub. Issue #847 created: "Fix authentication edge case in login flow". Assigned to you. Labels: bug, priority-high.<br><span class="tag" style="background:rgba(99,102,241,0.15);color:#818cf8;">mcp-connect</span> <span class="tag" style="background:rgba(99,102,241,0.15);color:#818cf8;">mcp-call-tool</span> <span class="tag purple">MCP GATEWAY</span></div>
            </div>
            <div class="scene-step" style="border-left-color: rgba(16,185,129,0.6);">
                <div class="voice-cmd" style="background:linear-gradient(135deg, rgba(16,185,129,0.15), rgba(16,185,129,0.25)); border-color:rgba(16,185,129,0.3);"><i class="fas fa-lock"></i> "Process this privately — keep it on-server, don't send to cloud"</div>
                <div class="result"><strong>&check; Routed to on-server AI.</strong> Request processed entirely on your server. Zero data left your infrastructure. Result delivered in 1.4s.<br><span class="tag green">on-server-ai</span> <span class="tag green">private-routing</span> <span class="tag purple">PRIVATE AI</span></div>
            </div>
        </div>
        <p style="text-align:center; margin-top:48px; font-size:1.2rem; color:var(--text-muted);">
            <?php echo L('alf_scene_time'); ?><br>
            <?php echo L('alf_scene_cost'); ?>
        </p>
    </div>
</section>

<!-- ═══ INTELLIGENCE ENGINES ═══ -->
<section class="engines-section" id="engines">
    <div class="scene-label"><?php echo L('alf_engines_label'); ?></div>
    <h2><?php echo L('alf_engines_h2'); ?></h2>
    <p class="section-sub"><?php echo L('alf_engines_sub'); ?></p>
    <div class="engine-grid">
        <div class="engine-card">
            <div class="engine-icon">&#129504;</div>
            <h3><?php echo L('alf_eng_elephant_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_elephant_sub'); ?></div>
            <p><?php echo L('alf_eng_elephant_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#128302;</div>
            <h3><?php echo L('alf_eng_oracle_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_oracle_sub'); ?></div>
            <p><?php echo L('alf_eng_oracle_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#128203;</div>
            <h3><?php echo L('alf_eng_playbook_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_playbook_sub'); ?></div>
            <p><?php echo L('alf_eng_playbook_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#9200;</div>
            <h3><?php echo L('alf_eng_clockwork_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_clockwork_sub'); ?></div>
            <p><?php echo L('alf_eng_clockwork_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#129435;</div>
            <h3><?php echo L('alf_eng_hivemind_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_hivemind_sub'); ?></div>
            <p><?php echo L('alf_eng_hivemind_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#128737;</div>
            <h3><?php echo L('alf_eng_sentinel_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_sentinel_sub'); ?></div>
            <p><?php echo L('alf_eng_sentinel_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#127760;</div>
            <h3><?php echo L('alf_eng_nexus_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_nexus_sub'); ?></div>
            <p><?php echo L('alf_eng_nexus_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#128296;</div>
            <h3><?php echo L('alf_eng_forge_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_forge_sub'); ?></div>
            <p><?php echo L('alf_eng_forge_p'); ?></p>
        </div>
        <div class="engine-card">
            <div class="engine-icon">&#129504;</div>
            <h3><?php echo L('alf_eng_cortex_h'); ?></h3>
            <div class="engine-subtitle"><?php echo L('alf_eng_cortex_sub'); ?></div>
            <p><?php echo L('alf_eng_cortex_p'); ?></p>
        </div>
    </div>
    <div class="engine-plus">
        <h3><?php echo L('alf_eng_plus_h'); ?></h3>
        <p><?php echo L('alf_eng_plus_p'); ?></p>
    </div>
</section>

<!-- ═══ v9.0 SUPERPOWERS ═══ -->
<section class="engines-section" id="superpowers" style="padding-top: 60px;">
    <div class="scene-label" style="background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(16,185,129,0.15)); border-color: rgba(0,212,255,0.3); color: #00D4FF;"><?php echo L('alf_super_label'); ?></div>
    <h2><?php echo L('alf_super_h2'); ?></h2>
    <p class="section-sub"><?php echo L('alf_super_sub'); ?></p>
    <div class="engine-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
        <div class="engine-card" style="border-color: rgba(0,212,255,0.15);">
            <div class="engine-icon">&#127760;</div>
            <h3><?php echo L('alf_super_browser_h'); ?></h3>
            <div class="engine-subtitle" style="color:#00D4FF;"><?php echo L('alf_super_browser_tag'); ?></div>
            <p><?php echo L('alf_super_browser_p'); ?></p>
        </div>
        <div class="engine-card" style="border-color: rgba(16,185,129,0.15);">
            <div class="engine-icon">&#128187;</div>
            <h3><?php echo L('alf_super_interp_h'); ?></h3>
            <div class="engine-subtitle" style="color:#10b981;"><?php echo L('alf_super_interp_tag'); ?></div>
            <p><?php echo L('alf_super_interp_p'); ?></p>
        </div>
        <div class="engine-card" style="border-color: rgba(251,146,60,0.15);">
            <div class="engine-icon">&#128218;</div>
            <h3><?php echo L('alf_super_rag_h'); ?></h3>
            <div class="engine-subtitle" style="color:#fb923c;"><?php echo L('alf_super_rag_tag'); ?></div>
            <p><?php echo L('alf_super_rag_p'); ?></p>
        </div>
        <div class="engine-card" style="border-color: rgba(239,68,68,0.15);">
            <div class="engine-icon">&#128737;</div>
            <h3><?php echo L('alf_super_monitor_h'); ?></h3>
            <div class="engine-subtitle" style="color:#ef4444;"><?php echo L('alf_super_monitor_tag'); ?></div>
            <p><?php echo L('alf_super_monitor_p'); ?></p>
        </div>
        <div class="engine-card" style="border-color: rgba(99,102,241,0.15);">
            <div class="engine-icon">&#9881;</div>
            <h3><?php echo L('alf_super_workflow_h'); ?></h3>
            <div class="engine-subtitle" style="color:#818cf8;"><?php echo L('alf_super_workflow_tag'); ?></div>
            <p><?php echo L('alf_super_workflow_p'); ?></p>
        </div>
        <div class="engine-card" style="border-color: rgba(236,72,153,0.15);">
            <div class="engine-icon">&#128202;</div>
            <h3><?php echo L('alf_super_artifact_h'); ?></h3>
            <div class="engine-subtitle" style="color:#ec4899;"><?php echo L('alf_super_artifact_tag'); ?></div>
            <p><?php echo L('alf_super_artifact_p'); ?></p>
        </div>
        <div class="engine-card" style="border-color: rgba(250,204,21,0.15);">
            <div class="engine-icon">&#127918;</div>
            <h3><?php echo L('alf_super_steering_h'); ?></h3>
            <div class="engine-subtitle" style="color:#facc15;"><?php echo L('alf_super_steering_tag'); ?></div>
            <p><?php echo L('alf_super_steering_p'); ?></p>
        </div>
    </div>
</section>

<!-- ═══ NEW: PRIVATE ON-SERVER AI ═══ -->
<section class="private-ai-section" id="private-ai">
    <div class="scene-label" style="color:#10b981;">&#128274; Your Data. Your Server. Full Stop.</div>
    <h2 style="text-align:center; font-family:'Space Grotesk',sans-serif; font-size:clamp(2rem,4vw,3rem); font-weight:800; color:#fff; margin-bottom:12px;">Private On-Server AI</h2>
    <p class="section-sub" style="max-width:680px;">Some tasks are too sensitive for the cloud. Alfred runs AI models <strong style="color:#10b981;">directly on your server</strong> — your code, your data, your infrastructure. Nothing leaves. Ever.</p>
    <div class="private-ai-demo">
        <div class="pai-card">
            <div class="pai-icon">&#128274;</div>
            <h3>100% On-Server Processing</h3>
            <p>Sensitive code, private customer data, confidential docs — processed entirely within your server. Zero egress. Zero cloud exposure.</p>
            <span class="pai-badge">PRIVATE</span>
        </div>
        <div class="pai-card">
            <div class="pai-icon">&#9889;</div>
            <h3>Smart Cloud/Local Routing</h3>
            <p>Alfred automatically chooses: complex reasoning goes to the cloud, sensitive or routine tasks route locally. Best of both worlds, intelligently.</p>
            <span class="pai-badge">INTELLIGENT</span>
        </div>
        <div class="pai-card">
            <div class="pai-icon">&#127758;</div>
            <h3>Multiple On-Server Models</h3>
            <p>Run multiple AI models locally — coding specialists, general assistants, fast mini-models for quick replies. Install new models by asking Alfred.</p>
            <span class="pai-badge">FLEXIBLE</span>
        </div>
        <div class="pai-card">
            <div class="pai-icon">&#128200;</div>
            <h3>Usage Analytics</h3>
            <p>See exactly which requests go local vs cloud, response times, and token savings. Full transparency on how your AI is being used.</p>
            <span class="pai-badge">TRANSPARENT</span>
        </div>
    </div>
    <div style="max-width:860px; margin:40px auto 0; background:linear-gradient(135deg, rgba(16,185,129,0.06), rgba(0,212,255,0.04)); border:1px solid rgba(16,185,129,0.2); border-radius:20px; padding:32px 36px; text-align:center;">
        <p style="font-size:1.05rem; color:var(--text-muted); line-height:1.8;">
            <strong style="color:#fff;">Ask Alfred:</strong> <em style="color:#10b981;">"Keep this private — process it on-server"</em><br>
            Alfred routes your request to the local AI engine. Your data never leaves your machine.<br>
            <span style="font-size:0.85rem; color:rgba(255,255,255,0.3);">Cursor can't do this. cPanel can't do this. Nobody else runs AI on YOUR server.</span>
        </p>
    </div>
</section>

<!-- ═══ NEW: LIVE CHARTS & ARTIFACTS ═══ -->
<section class="artifacts-section" id="artifacts">
    <div class="scene-label" style="color:#ec4899;">&#128202; Live Inside Your Editor</div>
    <h2 style="text-align:center; font-family:'Space Grotesk',sans-serif; font-size:clamp(2rem,4vw,3rem); font-weight:800; color:#fff; margin-bottom:12px;">Charts. Diagrams. Live Previews.</h2>
    <p class="section-sub" style="max-width:680px;">Ask Alfred to visualize anything. Interactive charts, architecture diagrams, and live HTML previews appear <strong style="color:#ec4899;">right inside your editor</strong> — no exports, no plugins, no switching tabs.</p>
    <div class="artifacts-demo">
        <div class="artifact-card">
            <div class="artifact-preview">
                <div class="art-icon">&#128202;</div>
                <div class="art-badge">Chart.js</div>
            </div>
            <div class="artifact-info">
                <div class="art-type">Interactive Charts</div>
                <div class="art-desc">Bar, line, pie, doughnut, radar, scatter, bubble — all interactive, all live.</div>
            </div>
        </div>
        <div class="artifact-card">
            <div class="artifact-preview">
                <div class="art-icon">&#128336;</div>
                <div class="art-badge">Mermaid</div>
            </div>
            <div class="artifact-info">
                <div class="art-type">Diagrams</div>
                <div class="art-desc">Flowcharts, sequence diagrams, Gantt charts, ER diagrams, class diagrams.</div>
            </div>
        </div>
        <div class="artifact-card">
            <div class="artifact-preview">
                <div class="art-icon">&#128084;</div>
                <div class="art-badge">Live HTML</div>
            </div>
            <div class="artifact-info">
                <div class="art-type">HTML Previews</div>
                <div class="art-desc">Live rendered HTML with Tailwind CSS and Alpine.js — interactive, right in your chat.</div>
            </div>
        </div>
        <div class="artifact-card">
            <div class="artifact-preview">
                <div class="art-icon">&#127775;</div>
                <div class="art-badge">Saved</div>
            </div>
            <div class="artifact-info">
                <div class="art-type">Artifact Library</div>
                <div class="art-desc">All generated artifacts saved with URLs. Browse, share, or embed any time.</div>
            </div>
        </div>
    </div>
    <p style="text-align:center; margin-top:32px; font-size:0.95rem; color:var(--text-muted);">
        <strong style="color:#fff;">Try it:</strong> <em style="color:#ec4899;">"Show my revenue for the last 6 months as a bar chart"</em> — Alfred generates it live in your editor in seconds.
    </p>
</section>

<!-- ═══ NEW: A2A PROTOCOL ═══ -->
<section class="a2a-section" id="a2a">
    <div class="scene-label" style="color:#818cf8;">&#129302; The Future of AI Collaboration</div>
    <h2 style="text-align:center; font-family:'Space Grotesk',sans-serif; font-size:clamp(2rem,4vw,3rem); font-weight:800; color:#fff; margin-bottom:12px;">Agent-to-Agent Protocol</h2>
    <p class="section-sub" style="max-width:700px;">Alfred doesn't just work alone. He can <strong style="color:#818cf8;">discover, connect to, and collaborate with other AI agents</strong> — delegating specialized tasks to remote agents and receiving results back. The future of autonomous AI workflows is here.</p>
    <div class="a2a-demo">
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="a2a-step">
                <div class="a2a-cmd"><i class="fas fa-search"></i> "Alfred, find a specialist agent for legal document review"</div>
                <div class="a2a-res"><strong>&#10003; Agent discovered.</strong> Found LegalAI at agent.legaltech.io — specializes in contract analysis, GDPR compliance, liability review. Ready to connect.</div>
            </div>
            <div class="a2a-step">
                <div class="a2a-cmd"><i class="fas fa-paper-plane"></i> "Send our terms of service to them for a compliance review"</div>
                <div class="a2a-res"><strong>&#10003; Task delegated.</strong> Task ID: a2a_7821. LegalAI is reviewing 3,400 words. Estimated completion: 90 seconds. Alfred will notify you when results arrive.</div>
            </div>
            <div class="a2a-step">
                <div class="a2a-cmd"><i class="fas fa-check-circle"></i> "What did they find?"</div>
                <div class="a2a-res"><strong>&#10003; Results received.</strong> 2 compliance issues found: GDPR data retention clause missing, arbitration clause not enforceable in Quebec. Full report saved to /reports/tos-review.pdf.</div>
            </div>
        </div>
        <div class="a2a-network">
            <p style="font-size:0.8rem; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,0.3); font-weight:700; margin-bottom:8px;">Alfred connects to</p>
            <div class="a2a-node">
                <div class="a2a-dot"></div>
                <div><div class="a2a-name">GitHub Agent</div><div class="a2a-cap">Code repos, issues, pull requests</div></div>
            </div>
            <div class="a2a-node">
                <div class="a2a-dot"></div>
                <div><div class="a2a-name">Analytics Agent</div><div class="a2a-cap">Traffic, conversion, SEO data</div></div>
            </div>
            <div class="a2a-node">
                <div class="a2a-dot"></div>
                <div><div class="a2a-name">Finance Agent</div><div class="a2a-cap">Invoicing, payments, bookkeeping</div></div>
            </div>
            <div class="a2a-node">
                <div class="a2a-dot"></div>
                <div><div class="a2a-name">Design Agent</div><div class="a2a-cap">UI generation, brand assets</div></div>
            </div>
            <div class="a2a-node">
                <div class="a2a-dot"></div>
                <div><div class="a2a-name">Any A2A Agent</div><div class="a2a-cap">Any agent that publishes an Agent Card</div></div>
            </div>
            <div style="text-align:center; margin-top:16px; padding:14px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.2); border-radius:12px; font-size:0.82rem; color:rgba(255,255,255,0.4);">
                Alfred publishes its own Agent Card — other agents can connect to <em>you</em> too.
            </div>
        </div>
    </div>
</section>

<!-- ═══ NEW: VOICE ROOMS ═══ -->
<section class="voice-rooms-section" id="voice-rooms">
    <div class="scene-label" style="color:#c084fc;">&#127908; Real-Time Collaboration</div>
    <h2 style="text-align:center; font-family:'Space Grotesk',sans-serif; font-size:clamp(2rem,4vw,3rem); font-weight:800; color:#fff; margin-bottom:12px;">Live Voice Rooms</h2>
    <p class="section-sub" style="max-width:660px;">Create a live voice room and have a real-time conversation with Alfred and your team. Multiple participants, all talking to the same AI — simultaneously. Perfect for live demos, client calls, and team standups.</p>
    <div class="vr-grid">
        <div class="vr-card">
            <div class="vr-icon">&#127908;</div>
            <h3>Create a Room</h3>
            <p>Spin up a live voice room instantly. Alfred joins automatically. Share the link — your team joins in seconds. Up to 10 participants.</p>
        </div>
        <div class="vr-card">
            <div class="vr-icon">&#128101;</div>
            <h3>Multi-Participant</h3>
            <p>Everyone in the room can speak to Alfred. Team member asks a question, Alfred responds. Developer asks a follow-up, Alfred answers. All live.</p>
        </div>
        <div class="vr-card">
            <div class="vr-icon">&#9889;</div>
            <h3>Instant Token</h3>
            <p>Join any room securely with a generated token. No accounts needed for guests. Full voice quality, real-time responses.</p>
        </div>
    </div>
    <p style="text-align:center; margin-top:36px; font-size:0.95rem; color:var(--text-muted);">
        <strong style="color:#fff;">Try it:</strong> <em style="color:#c084fc;">"Alfred, create a voice room for my client demo"</em> — Alfred generates the room and share link in seconds.
    </p>
</section>

<!-- ═══ IMAGE GENERATION ═══ -->
<section class="imggen-section" id="image-generation">
    <h2><?php echo L('alf_img_h2_v6'); ?></h2>
    <p class="section-sub"><?php echo L('alf_img_sub_v6'); ?></p>
    <div class="imggen-demo">
        <div class="imggen-card">
            <div class="prompt-bar"><i class="fas fa-magic"></i> <?php echo L('alf_img_p1'); ?></div>
            <div class="preview"><div class="placeholder">&#9749;</div><div class="gen-label"><?php echo L('alf_img_gen'); ?> 4.1s</div></div>
            <div class="meta"><span><?php echo L('alf_img_style'); ?> photo</span><span>1024&times;1024</span></div>
        </div>
        <div class="imggen-card">
            <div class="prompt-bar"><i class="fas fa-magic"></i> <?php echo L('alf_img_p2'); ?></div>
            <div class="preview"><div class="placeholder">&#9889;</div><div class="gen-label"><?php echo L('alf_img_gen'); ?> 3.8s</div></div>
            <div class="meta"><span><?php echo L('alf_img_style'); ?> logo</span><span>512&times;512</span></div>
        </div>
        <div class="imggen-card">
            <div class="prompt-bar"><i class="fas fa-magic"></i> <?php echo L('alf_img_p3'); ?></div>
            <div class="preview"><div class="placeholder">&#129366;</div><div class="gen-label"><?php echo L('alf_img_gen'); ?> 4.5s</div></div>
            <div class="meta"><span><?php echo L('alf_img_style'); ?> product</span><span>1024&times;1024</span></div>
        </div>
        <div class="imggen-card">
            <div class="prompt-bar"><i class="fas fa-magic"></i> <?php echo L('alf_img_p4'); ?></div>
            <div class="preview"><div class="placeholder">&#127912;</div><div class="gen-label"><?php echo L('alf_img_gen'); ?> 3.2s</div></div>
            <div class="meta"><span><?php echo L('alf_img_style'); ?> abstract</span><span>1792&times;1024</span></div>
        </div>
    </div>
    <p style="text-align:center; margin-top:36px; font-size:0.95rem; color:var(--text-muted);">
        <?php echo L('alf_img_presets'); ?> <strong style="color:#fff;"><?php echo L('alf_img_presets_list'); ?></strong><br>
        <?php echo L('alf_img_powered_v6'); ?>
    </p>
</section>

<!-- ═══ VOICE COMMANDS & MESSAGING ═══ -->
<section class="voice-msg-section" id="voice-messaging">
    <div style="text-align:center;">
        <div class="cherry-badge"><i class="fas fa-fire"></i> <?php echo L('alf_vm_badge'); ?></div>
    </div>
    <h2><?php echo L('alf_vm_h2'); ?></h2>
    <p class="section-sub"><?php echo L('alf_vm_sub'); ?></p>
    <div class="platform-grid">
        <div class="platform-card"><span class="p-icon"><i class="fab fa-whatsapp" style="color:#25d366;"></i></span><div class="p-name">WhatsApp</div><div class="p-status"><?php echo L('alf_vm_connected'); ?></div></div>
        <div class="platform-card"><span class="p-icon"><i class="fas fa-comment-dots" style="color:#3b82f6;"></i></span><div class="p-name">Signal</div><div class="p-status"><?php echo L('alf_vm_connected'); ?></div></div>
        <div class="platform-card"><span class="p-icon"><i class="fab fa-discord" style="color:#5865f2;"></i></span><div class="p-name">Discord</div><div class="p-status"><?php echo L('alf_vm_connected'); ?></div></div>
        <div class="platform-card"><span class="p-icon"><i class="fab fa-telegram" style="color:#0088cc;"></i></span><div class="p-name">Telegram</div><div class="p-status"><?php echo L('alf_vm_connected'); ?></div></div>
        <div class="platform-card"><span class="p-icon"><i class="fas fa-sms" style="color:#10b981;"></i></span><div class="p-name">SMS</div><div class="p-status"><?php echo L('alf_vm_connected'); ?></div></div>
        <div class="platform-card"><span class="p-icon"><i class="fas fa-microphone" style="color:#c084fc;"></i></span><div class="p-name"><?php echo L('alf_vm_voice'); ?></div><div class="p-status"><?php echo L('alf_vm_browser'); ?></div></div>
    </div>
    <h3 style="text-align:center; font-family:'Space Grotesk',sans-serif; font-size:1.4rem; font-weight:700; color:#fff; margin-bottom:32px;"><?php echo L('alf_vm_how'); ?></h3>
    <div class="msg-demos">
        <div class="msg-demo"><div class="msg-icon whatsapp"><i class="fab fa-whatsapp"></i></div><div class="msg-body"><div class="msg-bubble"><?php echo L('alf_vm_q1'); ?></div><div class="msg-reply"><?php echo L('alf_vm_a1'); ?></div></div></div>
        <div class="msg-demo"><div class="msg-icon signal"><i class="fas fa-comment-dots"></i></div><div class="msg-body"><div class="msg-bubble"><?php echo L('alf_vm_q2'); ?></div><div class="msg-reply"><?php echo L('alf_vm_a2'); ?></div></div></div>
        <div class="msg-demo"><div class="msg-icon discord"><i class="fab fa-discord"></i></div><div class="msg-body"><div class="msg-bubble"><?php echo L('alf_vm_q3'); ?></div><div class="msg-reply"><?php echo L('alf_vm_a3'); ?></div></div></div>
        <div class="msg-demo"><div class="msg-icon sms"><i class="fas fa-sms"></i></div><div class="msg-body"><div class="msg-bubble"><?php echo L('alf_vm_q4'); ?></div><div class="msg-reply"><?php echo L('alf_vm_a4'); ?></div></div></div>
    </div>
    <div class="voice-kicker">
        <p><?php echo L('alf_vm_kicker1'); ?><br><?php echo L('alf_vm_kicker2'); ?><br><?php echo L('alf_vm_kicker3'); ?><br><?php echo L('alf_vm_kicker4'); ?><br><br><?php echo L('alf_vm_kicker5'); ?><br><?php echo L('alf_vm_kicker6'); ?><br><br><span style="font-size:0.9rem; color:rgba(255,255,255,0.4);"><?php echo L('alf_vm_kicker7'); ?></span></p>
    </div>
</section>

<!-- ═══ COMPARISON ═══ -->
<section class="comparison-section" id="compare">
    <div class="scene-label" style="color:#00D4FF;">&#127942; Head-to-Head</div>
    <h2 style="text-align:center;font-family:'Space Grotesk',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:800;color:#fff;margin-bottom:12px;">Why Alfred IDE Wins</h2>
    <p class="section-sub">We tested 32 real features against the biggest names in hosting &amp; dev tools.<br><strong style="color:#00D4FF;">Alfred IDE has every one. Nobody else comes close.</strong></p>

<style>
.ring-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 960px; margin: 0 auto 48px; }
.ring-card { position: relative; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.06); border-radius: 24px; padding: 32px 20px 24px; text-align: center; transition: transform 0.25s, border-color 0.3s; }
.ring-card:hover { transform: translateY(-4px); border-color: rgba(0,212,255,0.25); }
.ring-wrap { position: relative; width: 130px; height: 130px; margin: 0 auto 16px; }
.ring-wrap svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.ring-bg { fill: none; stroke: rgba(255,255,255,0.06); stroke-width: 10; }
.ring-us { fill: none; stroke-width: 10; stroke-linecap: round; stroke-dasharray: 339.292; stroke-dashoffset: 339.292; transition: stroke-dashoffset 1.4s cubic-bezier(0.4, 0, 0.2, 1); filter: drop-shadow(0 0 8px var(--ring-glow)); }
.ring-them { fill: none; stroke: rgba(239,68,68,0.25); stroke-width: 7; stroke-linecap: round; stroke-dasharray: 263.894; stroke-dashoffset: 263.894; transition: stroke-dashoffset 1.6s cubic-bezier(0.4, 0, 0.2, 1) 0.3s; }
.ring-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; }
.ring-center .rc-us { font-family: 'Space Grotesk', sans-serif; font-size: 1.8rem; font-weight: 900; color: #fff; line-height: 1; display: block; }
.ring-center .rc-of { font-size: 0.7rem; color: rgba(255,255,255,0.3); font-weight: 600; display: block; margin-top: 2px; }
.ring-cat { font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; }
.ring-feats { font-size: 0.72rem; color: rgba(255,255,255,0.4); line-height: 1.65; }
.ring-feats .excl { color: #10b981; font-weight: 700; }
.ring-legend { display: flex; justify-content: center; gap: 32px; margin-bottom: 16px; flex-wrap: wrap; }
.ring-legend span { display: inline-flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 600; }
.ring-legend .dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
.ring-legend .dot-us { background: #00D4FF; box-shadow: 0 0 10px rgba(0,212,255,0.4); }
.ring-legend .dot-them { background: rgba(239,68,68,0.4); }
.ring-stats { display: flex; justify-content: center; gap: 48px; flex-wrap: wrap; padding: 28px 0; border-top: 1px solid rgba(255,255,255,0.06); max-width: 700px; margin: 0 auto; }
.ring-stats .rs { text-align: center; }
.ring-stats .rs-num { font-family: 'Space Grotesk', sans-serif; font-size: 2rem; font-weight: 900; line-height: 1; display: block; }
.ring-stats .rs-label { font-size: 0.72rem; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; display: block; }
.ring-card.in-view .ring-us { stroke-dashoffset: var(--us-offset); }
.ring-card.in-view .ring-them { stroke-dashoffset: var(--them-offset); }
.ring-card[data-cat="hosting"] { --ring-color: #00D4FF; --ring-glow: rgba(0,212,255,0.4); }
.ring-card[data-cat="ai"]      { --ring-color: #c084fc; --ring-glow: rgba(192,132,252,0.4); }
.ring-card[data-cat="dev"]     { --ring-color: #10b981; --ring-glow: rgba(16,185,129,0.4); }
.ring-card[data-cat="auto"]    { --ring-color: #fb923c; --ring-glow: rgba(251,146,60,0.4); }
.ring-card[data-cat="comms"]   { --ring-color: #f472b6; --ring-glow: rgba(244,114,182,0.4); }
.ring-card[data-cat="total"]   { --ring-color: #00D4FF; --ring-glow: rgba(0,212,255,0.5); }
.ring-us { stroke: var(--ring-color); }
.comp-board { max-width: 960px; margin: 0 auto 40px; padding: 28px 32px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; }
.comp-board-title { text-align: center; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.3); margin-bottom: 24px; }
.comp-hero { display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.06); }
.comp-hero-name { font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; font-weight: 900; color: #fff; }
.comp-hero-bar-wrap { flex: 1; max-width: 420px; height: 18px; background: rgba(255,255,255,0.04); border-radius: 100px; overflow: hidden; }
.comp-hero-bar { height: 100%; border-radius: 100px; background: linear-gradient(90deg, #00D4FF, #10b981); box-shadow: 0 0 20px rgba(0,212,255,0.3); width: 0; transition: width 1.4s cubic-bezier(0.4,0,0.2,1); }
.comp-hero-score { font-family: 'Space Grotesk', sans-serif; font-size: 1.4rem; font-weight: 900; color: #10b981; white-space: nowrap; }
.comp-bar-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.comp-bar-item { display: flex; flex-direction: column; gap: 6px; }
.comp-bar-top { display: flex; align-items: center; justify-content: space-between; }
.comp-bar-name { font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.55); }
.comp-bar-score { font-family: 'Space Grotesk', sans-serif; font-size: 0.78rem; font-weight: 800; color: rgba(239,68,68,0.6); }
.comp-bar-track { height: 8px; background: rgba(255,255,255,0.04); border-radius: 100px; overflow: hidden; }
.comp-bar-fill { height: 100%; border-radius: 100px; background: rgba(239,68,68,0.35); width: 0; transition: width 1.6s cubic-bezier(0.4,0,0.2,1) 0.4s; }
.comp-bar-note { font-size: 0.62rem; color: rgba(255,255,255,0.2); font-style: italic; }
.comp-board.in-view .comp-hero-bar { width: 100%; }
.comp-board.in-view .comp-bar-fill { width: var(--fill); }
@media (max-width: 820px) { .ring-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; } .comp-bar-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 520px) { .ring-grid { grid-template-columns: 1fr; max-width: 320px; } .ring-wrap { width: 110px; height: 110px; } .comp-bar-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
</style>

<?php
$rings = [
  ['hosting', '🖥️', 'Hosting &amp; Server', 11, 11, 4, ['*AI File Management', '*Terminal Commands', 'Database Ops', 'DNS', 'Email', 'SSL', 'WordPress', 'Security', 'Backups', 'Analytics', '*Billing (14 tools)']],
  ['ai', '🤖', 'AI Capabilities', 5, 5, 1, ['Image Generation', '*Video Generation', '*Audio / TTS', '*Vision Analysis', 'Content / AI Assist']],
  ['dev', '💻', 'Developer Tools', 7, 7, 2, ['Full IDE', 'AI Code Review', 'Smart Git Commits', '*Dependency Audit', '*Browser Agent', '*Code Interpreter', '*RAG Knowledge']],
  ['auto', '⚙️', 'Automation &amp; Intel', 6, 6, 1, ['Proactive Monitoring', 'Autonomous Scheduling', 'Workflow Automation', '*Multi-Agent AI', '*Charts &amp; Artifacts', '*MCP Gateway']],
  ['comms', '💬', 'Communication', 4, 4, 1, ['Persistent Memory', '*Voice Commands', '*Messaging (5+ platforms)', 'NL Code Writing']],
];
$outerC = 339.292; $innerC = 263.894;
?>
    <div class="ring-legend">
      <span><span class="dot dot-us"></span> <span style="color:#fff;">Alfred IDE</span></span>
      <span><span class="dot dot-them"></span> <span style="color:rgba(255,255,255,0.35);">Best of Cursor, GoDaddy, Bluehost &amp; more</span></span>
    </div>
    <div class="ring-grid">
<?php foreach ($rings as $r):
    $cat=$r[0];$icon=$r[1];$label=$r[2];$usScore=$r[3];$total=$r[4];$bestComp=$r[5];$feats=$r[6];
    $usOffset=round($outerC*(1-$usScore/$total),1);$themOffset=round($innerC*(1-$bestComp/$total),1);
    $exclCount=count(array_filter($feats,fn($f)=>str_starts_with($f,'*')));
?>
      <div class="ring-card" data-cat="<?php echo $cat; ?>" style="--us-offset:<?php echo $usOffset; ?>; --them-offset:<?php echo $themOffset; ?>;">
        <div class="ring-wrap"><svg viewBox="0 0 120 120"><circle class="ring-bg" cx="60" cy="60" r="54"/><circle class="ring-us" cx="60" cy="60" r="54"/><circle class="ring-bg" cx="60" cy="60" r="42" style="stroke-width:7;"/><circle class="ring-them" cx="60" cy="60" r="42"/></svg>
          <div class="ring-center"><span class="rc-us"><?php echo $usScore; ?>/<?php echo $total; ?></span><span class="rc-of">vs <?php echo $bestComp; ?>/<?php echo $total; ?></span></div>
        </div>
        <div class="ring-cat" style="color:var(--ring-color);"><?php echo $icon; ?> <?php echo $label; ?></div>
        <div class="ring-feats"><?php foreach ($feats as $f): $isExcl=str_starts_with($f,'*'); $name=$isExcl?substr($f,1):$f; ?><?php if($isExcl): ?><span class="excl"><?php echo $name; ?></span><br><?php else: ?><?php echo $name; ?><br><?php endif; ?><?php endforeach; ?><?php if($exclCount>0): ?><span style="font-size:0.65rem;color:#10b981;margin-top:4px;display:inline-block;"><?php echo $exclCount; ?> exclusive</span><?php endif; ?></div>
      </div>
<?php endforeach; ?>
      <div class="ring-card" data-cat="total" style="--us-offset:0; --them-offset:<?php echo round($innerC*(1-9/32),1); ?>; border-color:rgba(0,212,255,0.2); background:linear-gradient(135deg,rgba(0,212,255,0.06),rgba(125,0,255,0.04));">
        <div class="ring-wrap"><svg viewBox="0 0 120 120"><circle class="ring-bg" cx="60" cy="60" r="54"/><circle class="ring-us" cx="60" cy="60" r="54"/><circle class="ring-bg" cx="60" cy="60" r="42" style="stroke-width:7;"/><circle class="ring-them" cx="60" cy="60" r="42"/></svg>
          <div class="ring-center"><span class="rc-us" style="font-size:2.2rem;color:#10b981;">32</span><span class="rc-of">vs best 9</span></div>
        </div>
        <div class="ring-cat" style="color:#00D4FF;">🏆 Overall</div>
        <div class="ring-feats"><strong style="color:#fff;font-size:0.8rem;">Every feature. From $15/mo.</strong><br>13,000+ AI tools · 17 engines<br><span class="excl">15 features nobody else has</span></div>
      </div>
    </div>
    <div class="comp-board">
      <div class="comp-board-title">Feature score out of 32 — real companies, real results</div>
      <div class="comp-hero"><span class="comp-hero-name">🏆 Alfred IDE</span><div class="comp-hero-bar-wrap"><div class="comp-hero-bar"></div></div><span class="comp-hero-score">32 / 32</span></div>
<?php $competitors=[['Cursor',9,'IDE only — no hosting'],['GoDaddy',6,'Hosting — no AI tools'],['Bluehost',5,'Hosting — no AI tools'],['Hostinger',5,'Hosting — basic AI chat'],['Cloudways',6,'PaaS — no IDE or AI'],['cPanel',7,'Panel — no AI anything'],['DigitalOcean',4,'Infra — no mgmt tools'],['Replit',5,'IDE — no hosting mgmt']]; ?>
      <div class="comp-bar-grid"><?php foreach($competitors as $c): $pct=round(($c[1]/32)*100); ?><div class="comp-bar-item"><div class="comp-bar-top"><span class="comp-bar-name"><?php echo $c[0]; ?></span><span class="comp-bar-score"><?php echo $c[1]; ?> / 32</span></div><div class="comp-bar-track"><div class="comp-bar-fill" style="--fill:<?php echo $pct; ?>%;"></div></div><div class="comp-bar-note"><?php echo $c[2]; ?></div></div><?php endforeach; ?></div>
    </div>
    <div class="ring-stats">
      <div class="rs"><span class="rs-num" style="color:#10b981;">32/32</span><span class="rs-label">Features</span></div>
      <div class="rs"><span class="rs-num" style="color:#00D4FF;">13,000+</span><span class="rs-label">AI Tools</span></div>
      <div class="rs"><span class="rs-num" style="color:#c084fc;">9</span><span class="rs-label">Engines</span></div>
      <div class="rs"><span class="rs-num" style="color:#10b981;">From $15</span><span class="rs-label">Per Month</span></div>
    </div>
<script>(function(){ const obs=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in-view');}});},{threshold:0.3}); document.querySelectorAll('.ring-card,.comp-board').forEach(c=>obs.observe(c)); })();</script>
</section>

<!-- ═══ TOKEN PRICING ═══ -->
<section class="tokens-section" id="tokens">
    <h2><?php echo L('alf_tok_h2'); ?></h2>
    <p class="section-sub"><?php echo L('alf_tok_sub'); ?></p>
    <div class="token-grid">
        <div class="token-card featured">
            <div class="plan-name"><?php echo L('alf_tok_builder'); ?></div>
            <div class="plan-price">$15<span class="mo">/mo</span></div>
            <div class="token-count">300,000 <?php echo L('alf_tok_per_month'); ?></div>
            <div class="token-equiv"><?php echo L('alf_tok_roughly'); ?><br><strong><?php echo L('alf_tok_conversations'); ?></strong><br><strong><?php echo L('alf_tok_ai_ops'); ?></strong><br><strong><?php echo L('alf_tok_img_gens'); ?></strong></div>
            <a href="<?php echo billing_link('cart.php?a=add&pid=18'); ?>" class="btn btn-primary" style="margin-top:18px; display:block; padding:12px; border-radius:10px; font-size:0.9rem;">Get Started</a>
        </div>
        <div class="token-card">
            <div class="plan-name">Creator</div>
            <div class="plan-price">$22<span class="mo">/mo</span></div>
            <div class="token-count">450,000 <?php echo L('alf_tok_per_month'); ?></div>
            <div class="token-equiv">The sweet spot<br><strong>3 Websites</strong><br><strong>30GB NVMe Storage</strong><br><strong>Priority Email Support</strong></div>
            <a href="<?php echo billing_link('cart.php?a=add&pid=32'); ?>" class="btn btn-ghost" style="margin-top:18px; display:block; padding:12px; border-radius:10px; font-size:0.9rem;">Get Started</a>
        </div>
        <div class="token-card">
            <div class="plan-name"><?php echo L('alf_tok_pro'); ?></div>
            <div class="plan-price">$29<span class="mo">/mo</span></div>
            <div class="token-count">600,000 <?php echo L('alf_tok_per_month'); ?></div>
            <div class="token-equiv"><?php echo L('alf_tok_2x'); ?><br><strong><?php echo L('alf_tok_freelancers'); ?></strong><br><strong><?php echo L('alf_tok_small_teams'); ?></strong><br><strong><?php echo L('alf_tok_side_projects'); ?></strong></div>
        </div>
        <div class="token-card">
            <div class="plan-name"><?php echo L('alf_tok_studio'); ?></div>
            <div class="plan-price">$59<span class="mo">/mo</span></div>
            <div class="token-count">1,500,000 <?php echo L('alf_tok_per_month'); ?></div>
            <div class="token-equiv"><?php echo L('alf_tok_5x'); ?><br><strong><?php echo L('alf_tok_startups'); ?></strong><br><strong><?php echo L('alf_tok_dev_studios'); ?></strong><br><strong><?php echo L('alf_tok_opus_access'); ?></strong></div>
        </div>
        <div class="token-card">
            <div class="plan-name"><?php echo L('alf_tok_business'); ?></div>
            <div class="plan-price">$99<span class="mo">/mo</span></div>
            <div class="token-count">3,000,000 <?php echo L('alf_tok_per_month'); ?></div>
            <div class="token-equiv"><?php echo L('alf_tok_10x'); ?><br><strong><?php echo L('alf_tok_agencies'); ?></strong><br><strong><?php echo L('alf_tok_enterprise_teams'); ?></strong><br><strong><?php echo L('alf_tok_unlimited_opus'); ?></strong></div>
        </div>
    </div>
    <p style="text-align:center; margin-top:36px; font-size:0.9rem; color:var(--text-muted); max-width:700px; margin-left:auto; margin-right:auto;"><?php echo L('alf_tok_how'); ?></p>
    <div style="margin-top:64px;">
        <h3 style="text-align:center; font-size:1.4rem; font-weight:700; color:#fff; margin-bottom:8px;"><?php echo L('alf_topup_h3'); ?></h3>
        <p style="text-align:center; color:var(--text-muted); font-size:0.95rem; margin-bottom:32px;"><?php echo L('alf_topup_sub'); ?></p>
        <div class="topup-grid">
            <div class="topup-card"><div class="topup-amount">100K</div><div class="topup-price">$5</div><div class="topup-note">~250 conversations</div><a href="<?php echo billing_link('cart.php?a=add&pid=24'); ?>" class="btn btn-ghost" style="margin-top:12px; display:inline-block; padding:8px 20px; font-size:0.85rem; border-radius:8px;">Buy Now</a></div>
            <div class="topup-card"><div class="topup-amount">500K</div><div class="topup-price">$19</div><div class="topup-note">~1,250 conversations</div><div class="topup-savings">24% savings</div><a href="<?php echo billing_link('cart.php?a=add&pid=25'); ?>" class="btn btn-ghost" style="margin-top:12px; display:inline-block; padding:8px 20px; font-size:0.85rem; border-radius:8px;">Buy Now</a></div>
            <div class="topup-card"><div class="topup-amount">1M</div><div class="topup-price">$35</div><div class="topup-note">~2,500 conversations</div><div class="topup-savings">30% savings</div><a href="<?php echo billing_link('cart.php?a=add&pid=26'); ?>" class="btn btn-ghost" style="margin-top:12px; display:inline-block; padding:8px 20px; font-size:0.85rem; border-radius:8px;">Buy Now</a></div>
            <div class="topup-card"><div class="topup-amount">5M</div><div class="topup-price">$149</div><div class="topup-note">~12,500 conversations</div><div class="topup-savings">40% savings</div><a href="<?php echo billing_link('cart.php?a=add&pid=27'); ?>" class="btn btn-ghost" style="margin-top:12px; display:inline-block; padding:8px 20px; font-size:0.85rem; border-radius:8px;">Buy Now</a></div>
        </div>
    </div>
</section>

<!-- ═══ POWER-UP ADD-ONS ═══ -->
<section class="addons-section" id="addons">
    <h2><?php echo L('alf_addons_h2'); ?></h2>
    <p class="section-sub"><?php echo L('alf_addons_sub'); ?></p>
    <div class="addons-grid">
        <div class="addon-card"><div class="addon-icon">&#129504;</div><h3>Premium Model Access</h3><div class="addon-price">+$19/mo</div><p><?php echo L('alf_addon_opus'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#128483;</div><h3>Extra AI Sessions (3)</h3><div class="addon-price">+$9/mo</div><p><?php echo L('alf_addon_sessions'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#128101;</div><h3>Team Seats (5 Pack)</h3><div class="addon-price">+$49/mo</div><p><?php echo L('alf_addon_team'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#9889;</div><h3>Priority Support SLA</h3><div class="addon-price">+$14.99/mo</div><p><?php echo L('alf_addon_priority'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#128190;</div><h3>Automated Backups</h3><div class="addon-price">+$4.99/mo</div><p><?php echo L('alf_addon_backup'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#127760;</div><h3>Custom Domain Mapping</h3><div class="addon-price">+$2.99/mo</div><p><?php echo L('alf_addon_domain'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#127912;</div><h3>AI Image Pack (300/mo)</h3><div class="addon-price">+$9.99/mo</div><p><?php echo L('alf_addon_images'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#127909;</div><h3>AI Video Pack (50/mo)</h3><div class="addon-price">+$24.99/mo</div><p><?php echo L('alf_addon_video'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#128187;</div><h3>Dedicated Resources</h3><div class="addon-price">+$29.99/mo</div><p><?php echo L('alf_addon_dedicated'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#127991;</div><h3>White-Label Branding</h3><div class="addon-price">+$19.99/mo</div><p><?php echo L('alf_addon_whitelabel'); ?></p></div>
        <div class="addon-card"><div class="addon-icon">&#127758;</div><h3>Premium CDN &amp; Edge Cache</h3><div class="addon-price">+$9.99/mo</div><p>Global edge caching across 200+ locations. 60% faster loads with auto image optimization and DDoS protection.</p></div>
        <div class="addon-card"><div class="addon-icon">&#128450;</div><h3>Managed Database</h3><div class="addon-price">+$12.99/mo</div><p>Dedicated MySQL with auto-backups, read replicas, and 256MB Redis cache. 99.95% uptime SLA.</p></div>
        <div class="addon-card"><div class="addon-icon">&#128200;</div><h3>AI Analytics Dashboard</h3><div class="addon-price">+$7.99/mo</div><p>Real-time token usage analytics, model performance tracking, cost-per-generation, and exportable ROI reports.</p></div>
        <div class="addon-card"><div class="addon-icon">&#128736;</div><h3>Staging Environment</h3><div class="addon-price">+$5.99/mo</div><p>1-click staging with production cloning, branch-based previews, and automatic SSL. Test before going live.</p></div>
    </div>
    <p style="text-align:center; margin-top:32px; font-size:0.9rem; color:var(--text-muted);">All add-ons attach to any AI platform plan. <a href="<?php echo billing_link('cart.php?gid=6'); ?>" style="color:var(--cyan);">View plans &rarr;</a></p>
</section>

<!-- ═══ ALL 13,000+ TOOLS ═══ -->
<section class="tools-overview" id="tools">
    <h2><?php echo L('alf_tools_h2'); ?></h2>
    <p class="section-sub"><?php echo L('alf_tools_sub'); ?></p>
<?php foreach ($ALFRED_TOOL_CATS as $cat): ?>
    <div class="tool-category">
        <div class="cat-header">
            <div class="cat-icon <?php echo $cat['color']; ?>"><i class="<?php echo $cat['icon']; ?>"></i></div>
            <div><span class="cat-title"><?php echo L($cat['key']); ?></span> <span class="cat-count">&middot; <?php echo $cat['count']; ?> <?php echo L('alf_tools_label'); ?><?php if (!empty($cat['new'])): ?> &middot; <span style="color:#10b981;">NEW</span><?php endif; ?></span></div>
        </div>
        <div class="tool-grid">
<?php   foreach ($cat['tools'] as $t):
            $is_new = isset($t[6]) && $t[6];
            $style_attr = $is_new ? ' style="border-color:rgba(0,212,255,0.2); background:rgba(0,212,255,0.03);"' : '';
            $new_badge = $is_new ? ' <span style="background:rgba(16,185,129,0.15); color:#10b981; padding:2px 8px; border-radius:6px; font-size:0.75rem; margin-left:8px;">NEW</span>' : '';
?>
            <div class="tool-card"<?php echo $style_attr; ?>>
                <div class="tool-name"><?php echo ($li === 1) ? $t[1] : $t[0]; ?><?php echo $new_badge; ?></div>
                <div class="tool-desc"><?php echo ($li === 1) ? $t[3] : $t[2]; ?></div>
                <div class="try-saying"><?php echo ($li === 1) ? $t[5] : $t[4]; ?></div>
            </div>
<?php   endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</section>

<!-- ═══ SAFETY ═══ -->
<section class="safety-section" id="safety">
    <h2><?php echo L('alf_safe_h2'); ?></h2>
    <p class="section-sub"><?php echo L('alf_safe_sub'); ?></p>
    <div class="safety-grid">
        <div class="safety-card"><div class="icon">&#128176;</div><h3><?php echo L('alf_safe_bills_h'); ?></h3><p><?php echo L('alf_safe_bills_p'); ?></p></div>
        <div class="safety-card"><div class="icon">&#128274;</div><h3><?php echo L('alf_safe_sandbox_h'); ?></h3><p><?php echo L('alf_safe_sandbox_p'); ?></p></div>
        <div class="safety-card"><div class="icon">&#128260;</div><h3><?php echo L('alf_safe_git_h'); ?></h3><p><?php echo L('alf_safe_git_p'); ?></p></div>
        <div class="safety-card"><div class="icon">&#128065;</div><h3><?php echo L('alf_safe_transparent_h'); ?></h3><p><?php echo L('alf_safe_transparent_p'); ?></p></div>
        <div class="safety-card"><div class="icon">&#128465;</div><h3><?php echo L('alf_safe_delete_h'); ?></h3><p><?php echo L('alf_safe_delete_p'); ?></p></div>
        <div class="safety-card"><div class="icon">&#129489;&#8205;&#128187;</div><h3><?php echo L('alf_safe_humans_h'); ?></h3><p><?php echo L('alf_safe_humans_p'); ?></p></div>
    </div>
</section>

<!-- ═══ PROFESSIONAL SERVICES ═══ -->
<section class="addons-section" id="services">
    <h2>Professional Services</h2>
    <p class="section-sub">Expert training, integration, and ongoing support for your AI infrastructure</p>
    <div class="addons-grid" style="max-width:900px; margin:0 auto;">
        <div class="addon-card"><div class="addon-icon">&#127891;</div><h3>AI Quick Start Session</h3><div class="addon-price">$149 <small>one-time</small></div><p>2-hour 1-on-1 live session. Platform walkthrough, Alfred mastery, recording included, 30-day follow-up.</p><a href="<?php echo billing_link('cart.php?a=add&pid=36'); ?>" class="btn btn-ghost" style="margin-top:10px; display:inline-block; padding:6px 16px; font-size:0.8rem; border-radius:8px;">Book Now</a></div>
        <div class="addon-card"><div class="addon-icon">&#127979;</div><h3>Full-Day AI Workshop</h3><div class="addon-price">$499 <small>one-time</small></div><p>6-hour intensive for up to 5 people. Custom curriculum, hands-on projects, prompt engineering, 60-day support.</p><a href="<?php echo billing_link('cart.php?a=add&pid=37'); ?>" class="btn btn-ghost" style="margin-top:10px; display:inline-block; padding:6px 16px; font-size:0.8rem; border-radius:8px;">Book Now</a></div>
        <div class="addon-card"><div class="addon-icon">&#128296;</div><h3>Custom AI Integration</h3><div class="addon-price">$1,499 <small>one-time</small></div><p>Dedicated solution architect. API integration, automation, staff training (10 people), 90-day post-delivery support.</p><a href="<?php echo billing_link('cart.php?a=add&pid=38'); ?>" class="btn btn-ghost" style="margin-top:10px; display:inline-block; padding:6px 16px; font-size:0.8rem; border-radius:8px;">Book Now</a></div>
        <div class="addon-card"><div class="addon-icon">&#128736;</div><h3>AI Server Support</h3><div class="addon-price">from $49/mo</div><p>Remote diagnostics, firmware updates, health reports, and warranty coordination for your custom AI hardware.</p><a href="<?php echo billing_link('store/ai-server-support'); ?>" class="btn btn-ghost" style="margin-top:10px; display:inline-block; padding:6px 16px; font-size:0.8rem; border-radius:8px;">View Plans</a></div>
    </div>
    <p style="text-align:center; margin-top:24px; font-size:0.85rem; color:var(--text-muted);">Use code <strong style="color:var(--cyan);">TRAIN15</strong> for 15% off training sessions.</p>
</section>

<!-- ═══ VOICE & AI PRODUCTS ═══ -->
<section style="padding:80px 0;background:linear-gradient(180deg,rgba(0,212,255,0.03),transparent);border-top:1px solid rgba(255,255,255,0.04);">
    <div class="container" style="max-width:1100px;margin:0 auto;padding:0 24px;">
        <div style="text-align:center;margin-bottom:40px;">
            <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(0,212,255,0.15);border:1px solid rgba(0,212,255,0.3);padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;color:#06b6d4;margin-bottom:12px;"><i class="fas fa-phone-volume"></i> Voice & AI Products</span>
            <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.5rem,3vw,2.2rem);font-weight:700;color:#fff;margin:0 0 12px;">Alfred Powers 52 Voice & AI Products</h2>
            <p style="color:var(--text-muted);max-width:600px;margin:0 auto;">Buy exactly what you need — a $3 phone number, a $29 AI agent, or a full call center. À la carte, no contracts.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:32px;">
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:18px;text-align:center;">
                <div style="font-size:1.5rem;">📞</div>
                <div style="font-size:0.85rem;color:#fff;font-weight:600;">Phone Numbers</div>
                <div style="font-size:1rem;font-weight:800;color:#10b981;">From $3/mo</div>
            </div>
            <div style="background:rgba(125,0,255,0.05);border:1px solid rgba(125,0,255,0.2);border-radius:12px;padding:18px;text-align:center;">
                <div style="font-size:1.5rem;">🤖</div>
                <div style="font-size:0.85rem;color:#fff;font-weight:600;">AI Agents</div>
                <div style="font-size:1rem;font-weight:800;color:#7d00ff;">From $29/mo</div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:18px;text-align:center;">
                <div style="font-size:1.5rem;">📠</div>
                <div style="font-size:0.85rem;color:#fff;font-weight:600;">Fax & Docs</div>
                <div style="font-size:1rem;font-weight:800;color:#06b6d4;">From $15/mo</div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:18px;text-align:center;">
                <div style="font-size:1.5rem;">📤</div>
                <div style="font-size:0.85rem;color:#fff;font-weight:600;">Call Centers</div>
                <div style="font-size:1rem;font-weight:800;color:#ff3366;">From $99/mo</div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:18px;text-align:center;">
                <div style="font-size:1.5rem;">🏢</div>
                <div style="font-size:0.85rem;color:#fff;font-weight:600;">12 Industries</div>
                <div style="font-size:1rem;font-weight:800;color:#ff9500;">From $59/mo</div>
            </div>
        </div>
        <div style="text-align:center;">
            <a href="/voice-products.php" class="btn btn-primary" style="margin-right:10px;"><i class="fas fa-phone-volume" style="margin-right:6px;"></i> Browse All 52 Products</a>
            <a href="tel:+18077982850" class="btn btn-ghost" style="border-color:rgba(0,212,255,0.3);color:#06b6d4;"><i class="fas fa-phone" style="margin-right:6px;"></i> Call Alfred Live</a>
        </div>
    </div>
</section>

<!-- ═══ BUSINESS PROGRAMS ═══ -->
<section style="padding:60px 0;background:linear-gradient(180deg,transparent,rgba(125,0,255,0.03));border-top:1px solid rgba(255,255,255,0.04);">
    <div class="container" style="max-width:1100px;margin:0 auto;padding:0 24px;">
        <div style="text-align:center;margin-bottom:32px;">
            <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,215,0,0.15);border:1px solid rgba(255,215,0,0.3);padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;color:#ffd700;margin-bottom:12px;"><i class="fas fa-handshake"></i> Business Programs</span>
            <h2 style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.3rem,2.5vw,1.8rem);font-weight:700;color:#fff;margin:0;">Resell, Refer & Partner With Us</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
            <div style="background:rgba(125,0,255,0.04);border:1px solid rgba(125,0,255,0.12);border-radius:14px;padding:22px;">
                <span style="font-size:1.3rem;">🏷️</span>
                <strong style="color:#fff;margin-left:8px;">White-Label Reseller</strong>
                <span style="color:#a78bfa;font-size:0.85rem;float:right;">From $299/mo</span>
                <p style="font-size:0.82rem;color:var(--text-muted);margin:8px 0 0;line-height:1.4;">Rebrand our Voice & AI platform. Your logo, your pricing, unlimited clients.</p>
            </div>
            <div style="background:rgba(16,185,129,0.04);border:1px solid rgba(16,185,129,0.12);border-radius:14px;padding:22px;">
                <span style="font-size:1.3rem;">💸</span>
                <strong style="color:#fff;margin-left:8px;">Affiliate Program</strong>
                <span style="color:#10b981;font-size:0.85rem;float:right;">20% Recurring</span>
                <p style="font-size:0.82rem;color:var(--text-muted);margin:8px 0 0;line-height:1.4;">Refer customers, earn 20% commission on every sale. No cap, monthly payouts.</p>
            </div>
            <div style="background:rgba(6,182,212,0.04);border:1px solid rgba(6,182,212,0.12);border-radius:14px;padding:22px;">
                <span style="font-size:1.3rem;">🤝</span>
                <strong style="color:#fff;margin-left:8px;">Agency Partner</strong>
                <span style="color:#06b6d4;font-size:0.85rem;float:right;">Volume Pricing</span>
                <p style="font-size:0.82rem;color:var(--text-muted);margin:8px 0 0;line-height:1.4;">Offer AI voice to your clients. Co-branded portals, priority support, revenue sharing.</p>
            </div>
        </div>
        <p style="text-align:center;margin-top:16px;font-size:0.85rem;color:var(--text-muted);">
            <a href="/voice-products.php#business" style="color:#a78bfa;font-weight:600;">Learn more about our business programs <i class="fas fa-arrow-right" style="margin-left:4px;"></i></a>
        </p>
    </div>
</section>

<!-- ═══ FINAL CTA ═══ -->
<section class="final-cta">
    <h2><?php echo L('alf_cta_h2'); ?></h2>
    <p class="subtitle"><?php echo L('alf_cta_sub'); ?></p>
    <div class="cta-buttons">
        <a href="<?php echo billing_link('cart.php?a=add&pid=18'); ?>" class="btn btn-primary"><?php echo L('alf_cta_start'); ?></a>
        <a href="/alfred-voice-live/" class="btn btn-voice"><i class="fas fa-microphone"></i> <?php echo L('alf_cta_voice'); ?></a>
        <a href="<?php echo billing_link('store/ai-domain-hosting-connected-with-ai-editor'); ?>" class="btn btn-ghost"><?php echo L('alf_cta_domain'); ?></a>
    </div>
    <p class="promo"><?php echo L('alf_cta_promo'); ?></p>
</section>

<!-- ═══ FAQ ═══ -->
<section class="faq-section" id="faq">
    <h2><?php echo L('alf_faq_h2'); ?></h2>
    <div class="faq-grid">
<?php for ($i = 1; $i <= 20; $i++): ?>
        <div class="faq-item"><h3><?php echo L("alf_faq{$i}_q"); ?></h3><p><?php echo L("alf_faq{$i}_a"); ?></p></div>
<?php endfor; ?>
        <!-- NEW FAQs -->
        <div class="faq-item"><h3>Can Alfred run AI privately on my server?</h3><p>Yes. Alfred has a built-in on-server AI engine that processes requests entirely within your infrastructure. Sensitive code, private data, confidential documents — nothing leaves your server. Smart routing automatically chooses on-server processing for privacy-sensitive requests and cloud for complex reasoning tasks.</p></div>
        <div class="faq-item"><h3>Can Alfred connect to GitHub, Slack, or other external services?</h3><p>Yes, through the MCP Gateway. Alfred connects to external MCP servers — GitHub, Slack, Brave Search, Postgres, and hundreds more — and makes their tools available through natural conversation. Ask Alfred to "create a GitHub issue" or "post to Slack" without any manual setup.</p></div>
        <div class="faq-item"><h3>Can Alfred generate charts and data visualizations?</h3><p>Yes. Alfred generates interactive Chart.js charts (bar, line, pie, doughnut, radar, scatter, bubble), Mermaid diagrams (flowcharts, Gantt, ER diagrams, sequence diagrams), and live HTML previews with Tailwind CSS and Alpine.js — all as live artifacts directly inside your editor. No exporting, no plugins, no extra software.</p></div>
        <div class="faq-item"><h3>What are Voice Rooms?</h3><p>Voice Rooms let you create a live multi-participant voice session with Alfred. Your whole team can join and speak to Alfred simultaneously in real-time. Perfect for client demos, team standups, and live collaborative sessions. Create a room, share the link, and everyone's talking to the same Alfred instantly.</p></div>
        <div class="faq-item"><h3>What is Agent-to-Agent (A2A) protocol?</h3><p>A2A is the next frontier of AI collaboration. Alfred can discover remote AI agents, send them tasks, and receive results — delegating specialized work to purpose-built agents. Alfred also publishes its own Agent Card so other agents can connect to and use Alfred's capabilities. It's how AI systems collaborate autonomously at scale.</p></div>
    </div>
</section>

</div>

<!-- Schema.org: Alfred AI SoftwareApplication -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SoftwareApplication","name":"Alfred AI","applicationCategory":"Artificial Intelligence Platform","operatingSystem":"Web, Voice, API","url":"https://root.com/alfred.php","description":"Voice-first AI platform with 13,000+ tools, fleet management, consciousness layer, and marketplace. 17 AI engines, 89 tool categories, voice rooms, A2A protocol, RAG knowledge base, browser agent, code interpreter, and private on-server AI.","featureList":"13,000+ AI Tools, Voice Control, Fleet Management, Consciousness Layer, Marketplace, Legal Aid, Code Interpreter, RAG Knowledge Base, Browser Agent, Voice Rooms, A2A Protocol","offers":{"@type":"AggregateOffer","lowPrice":"3.99","highPrice":"24.99","priceCurrency":"USD","offerCount":"4","url":"https://root.com/alfred.php#tokens"},"provider":{"@type":"Organization","name":"GoSiteMe","url":"https://root.com"}}
</script>
<!-- Schema.org: WebPage -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"Alfred AI — Voice-First AI Platform with 13,000+ Tools","description":"Alfred AI: the world's first voice-first AI platform. 13,000+ tools, 17 AI engines, fleet management, consciousness layer, marketplace, and more.","url":"https://root.com/alfred.php","isPartOf":{"@type":"WebSite","name":"GoSiteMe","url":"https://root.com"},"primaryImageOfPage":{"@type":"ImageObject","url":"https://root.com/assets/hero-banner.png"}}
</script>
<!-- Schema.org: FAQPage -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Can Alfred run AI privately on my server?","acceptedAnswer":{"@type":"Answer","text":"Yes. Alfred has a built-in on-server AI engine that processes requests entirely within your infrastructure. Nothing leaves your server. Smart routing automatically chooses on-server processing for privacy-sensitive requests and cloud for complex reasoning tasks."}},{"@type":"Question","name":"Can Alfred connect to GitHub, Slack, or other external services?","acceptedAnswer":{"@type":"Answer","text":"Yes, through the MCP Gateway. Alfred connects to external MCP servers — GitHub, Slack, Brave Search, Postgres, and hundreds more — and makes their tools available through natural conversation."}},{"@type":"Question","name":"Can Alfred generate charts and data visualizations?","acceptedAnswer":{"@type":"Answer","text":"Yes. Alfred generates interactive Chart.js charts, Mermaid diagrams, and live HTML previews with Tailwind CSS and Alpine.js — all as live artifacts directly inside your editor."}},{"@type":"Question","name":"What are Voice Rooms?","acceptedAnswer":{"@type":"Answer","text":"Voice Rooms let you create a live multi-participant voice session with Alfred. Your whole team can join and speak to Alfred simultaneously in real-time. Perfect for client demos, team standups, and live collaborative sessions."}},{"@type":"Question","name":"What is Agent-to-Agent (A2A) protocol?","acceptedAnswer":{"@type":"Answer","text":"A2A is the next frontier of AI collaboration. Alfred can discover remote AI agents, send them tasks, and receive results — delegating specialized work to purpose-built agents. Alfred also publishes its own Agent Card so other agents can connect to and use Alfred's capabilities."}}]}
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
