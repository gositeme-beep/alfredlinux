<?php
$page_title       = 'Developer Portal — Build with Alfred API | GoSiteMe';
$page_description = 'Access 13,000+ AI tools via 6 providers, voice agents, fleet management, deep research, creative AI, and more through the Alfred REST API. SDKs for Node.js, Python, and PHP.';
$page_canonical   = 'https://gositeme.com/developer-portal.php';
$page_og_title    = 'Build with Alfred API — Developer Portal';
$page_og_description = '13,000+ AI tools via REST API. SDKs for Node.js, Python, PHP. Voice agents, fleet management, deep research, creative AI, robotics. Ship AI features in minutes.';
$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int) $_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>

<style>
/* ===== DEVELOPER PORTAL — SCOPED STYLES ===== */

.dp-page {
    --dp-bg: #0a0a14;
    --dp-surface: #12121e;
    --dp-surface-2: #1a1a2e;
    --dp-border: rgba(255,255,255,.06);
    --dp-text: #e2e8f0;
    --dp-text-muted: #94a3b8;
    --dp-accent: #6c5ce7;
    --dp-accent-light: #a29bfe;
    --dp-blue: #0984e3;
    --dp-green: #00b894;
    --dp-orange: #e17055;
    --dp-red: #d63031;
    --dp-radius: 16px;
    --dp-radius-sm: 12px;
    --dp-transition: .3s cubic-bezier(.4,0,.2,1);
    --dp-shadow: 0 8px 32px rgba(0,0,0,.35);
    --dp-glow: 0 0 60px rgba(108,92,231,.15);
    --dp-gradient: linear-gradient(135deg, #6c5ce7, #0984e3);
    --dp-gradient-soft: linear-gradient(135deg, rgba(108,92,231,.12), rgba(9,132,227,.12));
}

.dp-page { background: var(--dp-bg); color: var(--dp-text); overflow-x: hidden; }
.dp-page *, .dp-page *::before, .dp-page *::after { box-sizing: border-box; }

/* ---------- Layout ---------- */
.dp-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.dp-section { padding: 80px 0; }
.dp-section--alt { background: var(--dp-surface); }

/* ---------- Typography ---------- */
.dp-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 16px; border-radius: 50px; font-size: .78rem; font-weight: 600;
    letter-spacing: .5px; text-transform: uppercase;
    background: var(--dp-gradient-soft); color: var(--dp-accent-light);
    border: 1px solid rgba(108,92,231,.2);
}
.dp-section-title {
    font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800; line-height: 1.15; margin: 0 0 12px;
    background: var(--dp-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.dp-section-sub {
    font-size: 1.1rem; color: var(--dp-text-muted); max-width: 640px; margin: 0 auto 48px; line-height: 1.6;
}
.dp-section-header { text-align: center; margin-bottom: 48px; }

/* ---------- Buttons ---------- */
.dp-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 32px; border: none; border-radius: 50px; cursor: pointer;
    font-size: 1rem; font-weight: 700; text-decoration: none;
    background: var(--dp-gradient); color: #fff;
    box-shadow: 0 4px 24px rgba(108,92,231,.35);
    transition: transform var(--dp-transition), box-shadow var(--dp-transition);
    font-family: inherit;
}
.dp-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(108,92,231,.5); color: #fff; text-decoration: none; }
.dp-btn--ghost {
    background: transparent; border: 2px solid rgba(255,255,255,.15);
    color: var(--dp-text); box-shadow: none;
}
.dp-btn--ghost:hover { border-color: var(--dp-accent); color: var(--dp-accent-light); background: rgba(108,92,231,.08); }
.dp-btn--sm { padding: 10px 22px; font-size: .88rem; }
.dp-btn--block { width: 100%; justify-content: center; }

/* ---------- Hero ---------- */
.dp-hero {
    position: relative; padding: 140px 0 90px; text-align: center;
    background: radial-gradient(ellipse at 50% 0%, rgba(108,92,231,.18) 0%, transparent 70%);
    overflow: hidden;
}
.dp-hero::before {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='g' width='60' height='60' patternUnits='userSpaceOnUse'%3E%3Cpath d='M60 0H0v60' fill='none' stroke='rgba(255,255,255,.03)' stroke-width='.5'/%3E%3C/pattern%3E%3C/defs%3E%3Crect fill='url(%23g)' width='60' height='60'/%3E%3C/svg%3E");
    opacity: .6;
}
.dp-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.4rem, 5.5vw, 4rem); font-weight: 800; line-height: 1.1;
    background: var(--dp-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; margin: 0 0 20px; position: relative;
}
.dp-hero__sub {
    font-size: clamp(1rem, 2vw, 1.25rem); color: var(--dp-text-muted);
    max-width: 680px; margin: 0 auto 36px; line-height: 1.7; position: relative;
}
.dp-hero__actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 48px; position: relative; }

/* Code preview */
.dp-hero__code {
    max-width: 720px; margin: 0 auto; text-align: left; position: relative;
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); overflow: hidden;
    box-shadow: var(--dp-shadow), var(--dp-glow);
}
.dp-hero__code-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 20px; background: rgba(255,255,255,.03); border-bottom: 1px solid var(--dp-border);
}
.dp-hero__code-dots { display: flex; gap: 6px; }
.dp-hero__code-dots span { width: 10px; height: 10px; border-radius: 50%; }
.dp-hero__code-dots span:nth-child(1) { background: var(--dp-red); }
.dp-hero__code-dots span:nth-child(2) { background: var(--dp-orange); }
.dp-hero__code-dots span:nth-child(3) { background: var(--dp-green); }
.dp-hero__code-copy {
    background: none; border: none; color: var(--dp-text-muted); cursor: pointer;
    font-size: .82rem; display: flex; align-items: center; gap: 4px;
    transition: color var(--dp-transition); padding: 4px 8px; border-radius: 6px;
}
.dp-hero__code-copy:hover { color: var(--dp-accent-light); }
.dp-hero__code pre {
    margin: 0; padding: 20px; overflow-x: auto; font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: .88rem; line-height: 1.65; color: var(--dp-text);
}
.dp-hero__code pre .c-cmd { color: var(--dp-green); }
.dp-hero__code pre .c-flag { color: var(--dp-orange); }
.dp-hero__code pre .c-url { color: var(--dp-blue); }
.dp-hero__code pre .c-str { color: #ffeaa7; }
.dp-hero__code pre .c-key { color: var(--dp-accent-light); }

/* ---------- Quick Start Cards ---------- */
.dp-quickstart { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
.dp-quickstart__card {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); padding: 36px 28px; text-align: center;
    transition: transform var(--dp-transition), box-shadow var(--dp-transition), border-color var(--dp-transition);
    text-decoration: none; color: inherit; display: block; position: relative; overflow: hidden;
}
.dp-quickstart__card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--dp-gradient); opacity: 0; transition: opacity var(--dp-transition);
}
.dp-quickstart__card:hover { transform: translateY(-4px); box-shadow: var(--dp-shadow); border-color: rgba(108,92,231,.2); }
.dp-quickstart__card:hover::before { opacity: 1; }
.dp-quickstart__icon {
    width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px; font-size: 1.5rem;
    background: var(--dp-gradient-soft); color: var(--dp-accent-light);
}
.dp-quickstart__card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.2rem; font-weight: 700; margin: 0 0 10px; }
.dp-quickstart__card p { color: var(--dp-text-muted); font-size: .95rem; line-height: 1.6; margin: 0 0 18px; }
.dp-quickstart__link { color: var(--dp-accent-light); font-weight: 600; font-size: .9rem; text-decoration: none; }
.dp-quickstart__link:hover { text-decoration: underline; }

/* ---------- API Overview ---------- */
.dp-api-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; }
.dp-api-card {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); padding: 32px 28px;
    transition: transform var(--dp-transition), border-color var(--dp-transition);
}
.dp-api-card:hover { transform: translateY(-3px); border-color: rgba(108,92,231,.15); }
.dp-api-card__head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
.dp-api-card__icon {
    width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.dp-api-card__icon--chat { background: rgba(108,92,231,.15); color: var(--dp-accent-light); }
.dp-api-card__icon--tools { background: rgba(0,184,148,.15); color: var(--dp-green); }
.dp-api-card__icon--agents { background: rgba(9,132,227,.15); color: var(--dp-blue); }
.dp-api-card__icon--fleet { background: rgba(225,112,85,.15); color: var(--dp-orange); }
.dp-api-card__icon--voice { background: rgba(162,155,254,.15); color: var(--dp-accent-light); }
.dp-api-card__icon--market { background: rgba(253,203,110,.15); color: #fdcb6e; }
.dp-api-card__icon--music { background: rgba(162,155,254,.15); color: #a29bfe; }
.dp-api-card__icon--events { background: rgba(0,206,201,.15); color: #00cec9; }
.dp-api-card__icon--sanctuary { background: rgba(212,165,74,.15); color: #d4a54a; }
.dp-api-card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 700; margin: 0; }
.dp-api-card ul { list-style: none; padding: 0; margin: 0 0 18px; }
.dp-api-card ul li {
    padding: 6px 0; font-size: .92rem; color: var(--dp-text-muted); display: flex; align-items: center; gap: 8px;
}
.dp-api-card ul li::before { content: '→'; color: var(--dp-accent); font-weight: 700; }
.dp-api-card__link { color: var(--dp-accent-light); font-weight: 600; font-size: .9rem; text-decoration: none; }
.dp-api-card__link:hover { text-decoration: underline; }

/* ---------- SDKs Section ---------- */
.dp-sdk-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; }
.dp-sdk-card {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); overflow: hidden;
}
.dp-sdk-card__header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; background: rgba(255,255,255,.02); border-bottom: 1px solid var(--dp-border);
}
.dp-sdk-card__lang { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1rem; }
.dp-sdk-card__lang i { font-size: 1.2rem; }
.dp-sdk-card__install {
    font-size: .78rem; padding: 4px 12px; border-radius: 6px;
    background: rgba(0,184,148,.12); color: var(--dp-green); font-family: 'JetBrains Mono', monospace;
}
.dp-sdk-card pre {
    margin: 0; padding: 20px; overflow-x: auto;
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: .84rem; line-height: 1.7; color: var(--dp-text);
}
.dp-sdk-card pre .c-kw { color: var(--dp-accent-light); }
.dp-sdk-card pre .c-fn { color: var(--dp-blue); }
.dp-sdk-card pre .c-str { color: #ffeaa7; }
.dp-sdk-card pre .c-cm { color: var(--dp-text-muted); font-style: italic; }
.dp-sdk-card pre .c-var { color: var(--dp-green); }
.dp-sdk-card pre .c-op { color: var(--dp-orange); }
.dp-sdk-card__copy {
    background: none; border: none; color: var(--dp-text-muted); cursor: pointer;
    font-size: .82rem; padding: 4px 8px; border-radius: 6px;
    transition: color var(--dp-transition);
}
.dp-sdk-card__copy:hover { color: var(--dp-accent-light); }

/* ---------- API Keys ---------- */
.dp-keys { max-width: 900px; margin: 0 auto; }
.dp-keys__empty {
    text-align: center; padding: 60px 24px;
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius);
}
.dp-keys__empty i { font-size: 3rem; color: var(--dp-accent); margin-bottom: 20px; display: block; }
.dp-keys__empty p { color: var(--dp-text-muted); font-size: 1.05rem; margin: 0 0 24px; }
.dp-keys__table-wrap {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); overflow: hidden;
}
.dp-keys__toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px; border-bottom: 1px solid var(--dp-border);
}
.dp-keys__toolbar h3 { margin: 0; font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; }
.dp-keys__table {
    width: 100%; border-collapse: collapse; font-size: .9rem;
}
.dp-keys__table th {
    text-align: left; padding: 14px 20px; color: var(--dp-text-muted); font-weight: 600;
    font-size: .8rem; text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 1px solid var(--dp-border); background: rgba(255,255,255,.02);
}
.dp-keys__table td { padding: 14px 20px; border-bottom: 1px solid var(--dp-border); }
.dp-keys__table tr:last-child td { border-bottom: none; }
.dp-keys__table tr:hover td { background: rgba(108,92,231,.04); }
.dp-key-prefix { font-family: 'JetBrains Mono', monospace; font-size: .82rem; color: var(--dp-green); }
.dp-key-status {
    display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px;
    border-radius: 50px; font-size: .78rem; font-weight: 600;
}
.dp-key-status--active { background: rgba(0,184,148,.12); color: var(--dp-green); }
.dp-key-status--inactive { background: rgba(214,48,49,.12); color: var(--dp-red); }

/* ---------- Rate Limits ---------- */
.dp-rates { max-width: 900px; margin: 0 auto; }
.dp-rates__table-wrap {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); overflow: hidden;
}
.dp-rates__table { width: 100%; border-collapse: collapse; font-size: .92rem; }
.dp-rates__table th {
    text-align: left; padding: 16px 24px; color: var(--dp-text-muted); font-weight: 600;
    font-size: .8rem; text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 1px solid var(--dp-border); background: rgba(255,255,255,.02);
}
.dp-rates__table td { padding: 14px 24px; border-bottom: 1px solid var(--dp-border); }
.dp-rates__table tr:last-child td { border-bottom: none; }
.dp-rates__table tr:hover td { background: rgba(108,92,231,.04); }
.dp-rates__tier {
    font-weight: 700; font-family: 'Space Grotesk', sans-serif;
    display: flex; align-items: center; gap: 8px;
}
.dp-rates__tier-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dp-rates__val { font-family: 'JetBrains Mono', monospace; font-size: .85rem; }

/* ---------- Webhook Accordion ---------- */
.dp-webhooks { max-width: 900px; margin: 0 auto; }
.dp-accordion { display: flex; flex-direction: column; gap: 12px; }
.dp-accordion__item {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius-sm); overflow: hidden;
    transition: border-color var(--dp-transition);
}
.dp-accordion__item.is-open { border-color: rgba(108,92,231,.2); }
.dp-accordion__trigger {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px; background: none; border: none; color: var(--dp-text);
    font-size: 1rem; font-weight: 700; font-family: 'Space Grotesk', sans-serif;
    cursor: pointer; text-align: left; gap: 12px;
    transition: background var(--dp-transition);
}
.dp-accordion__trigger:hover { background: rgba(255,255,255,.02); }
.dp-accordion__trigger i.fa-chevron-down {
    transition: transform var(--dp-transition); font-size: .85rem; color: var(--dp-text-muted);
}
.dp-accordion__item.is-open .dp-accordion__trigger i.fa-chevron-down { transform: rotate(180deg); }
.dp-accordion__body { display: none; padding: 0 24px 20px; }
.dp-accordion__item.is-open .dp-accordion__body { display: block; }
.dp-event {
    display: flex; align-items: flex-start; gap: 12px; padding: 10px 0;
    border-bottom: 1px solid var(--dp-border);
}
.dp-event:last-child { border-bottom: none; }
.dp-event__name {
    font-family: 'JetBrains Mono', monospace; font-size: .82rem; padding: 3px 10px;
    background: rgba(108,92,231,.1); color: var(--dp-accent-light); border-radius: 6px;
    white-space: nowrap; flex-shrink: 0;
}
.dp-event__desc { color: var(--dp-text-muted); font-size: .88rem; line-height: 1.5; }

/* ---------- Playground ---------- */
.dp-playground { max-width: 900px; margin: 0 auto; }
.dp-playground__wrap {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); overflow: hidden;
}
.dp-playground__toolbar {
    display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
    padding: 20px 24px; border-bottom: 1px solid var(--dp-border); background: rgba(255,255,255,.02);
}
.dp-playground__select, .dp-playground__input {
    padding: 10px 14px; border-radius: 8px; border: 1px solid var(--dp-border);
    background: var(--dp-surface-2); color: var(--dp-text); font-size: .9rem;
    font-family: inherit; outline: none; transition: border-color var(--dp-transition);
}
.dp-playground__select:focus, .dp-playground__input:focus { border-color: var(--dp-accent); }
.dp-playground__select { min-width: 220px; }
.dp-playground__input { flex: 1; min-width: 200px; }
.dp-playground__body { display: grid; grid-template-columns: 1fr 1fr; min-height: 300px; }
.dp-playground__req, .dp-playground__res {
    padding: 20px; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: .84rem; line-height: 1.6;
}
.dp-playground__req { border-right: 1px solid var(--dp-border); }
.dp-playground__req-label, .dp-playground__res-label {
    font-family: 'Space Grotesk', sans-serif; font-size: .78rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px; color: var(--dp-text-muted); margin-bottom: 10px;
}
.dp-playground__textarea {
    width: 100%; min-height: 220px; background: var(--dp-bg); border: 1px solid var(--dp-border);
    border-radius: 8px; color: var(--dp-text); padding: 14px; font-family: 'JetBrains Mono', monospace;
    font-size: .84rem; line-height: 1.6; resize: vertical; outline: none;
    transition: border-color var(--dp-transition);
}
.dp-playground__textarea:focus { border-color: var(--dp-accent); }
.dp-playground__output {
    background: var(--dp-bg); border: 1px solid var(--dp-border); border-radius: 8px;
    padding: 14px; min-height: 220px; overflow: auto; white-space: pre-wrap; word-break: break-word;
    color: var(--dp-text-muted); font-family: 'JetBrains Mono', monospace; font-size: .84rem; line-height: 1.6;
}
.dp-playground__footer {
    display: flex; justify-content: flex-end; padding: 16px 24px;
    border-top: 1px solid var(--dp-border); background: rgba(255,255,255,.02);
}

/* ---------- Footer CTA ---------- */
.dp-cta {
    text-align: center; padding: 100px 24px;
    background: radial-gradient(ellipse at 50% 100%, rgba(108,92,231,.15) 0%, transparent 70%);
}
.dp-cta h2 {
    font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800; margin: 0 0 16px;
}
.dp-cta p { color: var(--dp-text-muted); font-size: 1.1rem; margin: 0 0 32px; }

/* ---------- Modal ---------- */
.dp-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7);
    z-index: 9999; align-items: center; justify-content: center; padding: 24px;
    backdrop-filter: blur(4px);
}
.dp-modal-overlay.is-open { display: flex; }
.dp-modal {
    background: var(--dp-surface); border: 1px solid var(--dp-border);
    border-radius: var(--dp-radius); width: 100%; max-width: 480px; padding: 32px;
    position: relative;
}
.dp-modal h3 { font-family: 'Space Grotesk', sans-serif; margin: 0 0 20px; font-size: 1.3rem; }
.dp-modal__close {
    position: absolute; top: 16px; right: 16px; background: none; border: none;
    color: var(--dp-text-muted); font-size: 1.2rem; cursor: pointer;
    transition: color var(--dp-transition);
}
.dp-modal__close:hover { color: var(--dp-text); }
.dp-modal__field { margin-bottom: 16px; }
.dp-modal__field label { display: block; font-size: .88rem; font-weight: 600; margin-bottom: 6px; color: var(--dp-text-muted); }
.dp-modal__field input {
    width: 100%; padding: 10px 14px; background: var(--dp-bg); border: 1px solid var(--dp-border);
    border-radius: 8px; color: var(--dp-text); font-size: .95rem; outline: none;
    transition: border-color var(--dp-transition); font-family: inherit;
}
.dp-modal__field input:focus { border-color: var(--dp-accent); }

/* ---------- Responsive ---------- */
@media (max-width: 768px) {
    .dp-section { padding: 60px 0; }
    .dp-playground__body { grid-template-columns: 1fr; }
    .dp-playground__req { border-right: none; border-bottom: 1px solid var(--dp-border); }
    .dp-rates__table-wrap { overflow-x: auto; }
    .dp-keys__table-wrap { overflow-x: auto; }
    .dp-api-grid { grid-template-columns: 1fr; }
    .dp-sdk-grid { grid-template-columns: 1fr; }
    .dp-quickstart { grid-template-columns: 1fr; }
    .dp-hero { padding: 80px 0 60px; }
    .dp-playground__toolbar { flex-direction: column; align-items: stretch; }
    .dp-playground__select { min-width: 100%; }
}
@media (max-width: 480px) {
    .dp-container { padding: 0 16px; }
    .dp-hero__actions { flex-direction: column; align-items: center; }
    .dp-playground__body { min-height: auto; }
}
</style>

<main id="main" class="dp-page">

    <!-- ========== 1. HERO ========== -->
    <section class="dp-hero" aria-labelledby="dp-hero-title">
        <div class="dp-container">
            <span class="dp-badge" data-aos="fade-up"><i class="fas fa-code"></i> Developer Portal</span>
            <h1 id="dp-hero-title" data-aos="fade-up" data-aos-delay="100">Build with Alfred API</h1>
            <p class="dp-hero__sub" data-aos="fade-up" data-aos-delay="200">
                Access 13,000+ AI tools via 6 providers, voice agents, fleet management, deep research, creative AI, and more through our REST API.
            </p>
            <div class="dp-hero__actions" data-aos="fade-up" data-aos-delay="300">
                <a href="#api-keys" class="dp-btn"><i class="fas fa-key"></i> Get API Key</a>
                <a href="/docs/api-reference" class="dp-btn dp-btn--ghost"><i class="fas fa-book"></i> Read Documentation</a>
                <a href="/docs/swagger.php" class="dp-btn dp-btn--ghost"><i class="fas fa-bolt"></i> Interactive API Explorer</a>
            </div>
            <div class="dp-hero__code" data-aos="fade-up" data-aos-delay="400">
                <div class="dp-hero__code-header">
                    <div class="dp-hero__code-dots"><span></span><span></span><span></span></div>
                    <button class="dp-hero__code-copy" data-copy="hero-curl" aria-label="Copy code"><i class="fas fa-copy"></i> Copy</button>
                </div>
                <pre id="hero-curl"><span class="c-cmd">curl</span> <span class="c-flag">-X POST</span> <span class="c-url">https://api.gositeme.com/v1/chat</span> \
  <span class="c-flag">-H</span> <span class="c-str">"Authorization: Bearer YOUR_API_KEY"</span> \
  <span class="c-flag">-H</span> <span class="c-str">"Content-Type: application/json"</span> \
  <span class="c-flag">-d</span> <span class="c-str">'{"message": "Deploy WordPress to staging.example.com"}'</span></pre>
            </div>
        </div>
    </section>

    <!-- ========== 2. QUICK START ========== -->
    <section class="dp-section" aria-labelledby="dp-qs-title">
        <div class="dp-container">
            <div class="dp-section-header">
                <span class="dp-badge" data-aos="fade-up"><i class="fas fa-rocket"></i> Quick Start</span>
                <h2 class="dp-section-title" id="dp-qs-title" data-aos="fade-up" data-aos-delay="100">Get Started in 3 Steps</h2>
            </div>
            <div class="dp-quickstart">
                <a href="#api-keys" class="dp-quickstart__card" data-aos="fade-up" data-aos-delay="100">
                    <div class="dp-quickstart__icon"><i class="fas fa-key"></i></div>
                    <h3>Get Your API Key</h3>
                    <p>Sign up for a free account and generate your API key instantly. No credit card required to start building.</p>
                    <span class="dp-quickstart__link">Generate Key →</span>
                </a>
                <a href="/docs/getting-started" class="dp-quickstart__card" data-aos="fade-up" data-aos-delay="200">
                    <div class="dp-quickstart__icon"><i class="fas fa-terminal"></i></div>
                    <h3>Make Your First Call</h3>
                    <p>Follow our step-by-step guide to make your first API call. Authentication, request formatting, and response handling explained.</p>
                    <span class="dp-quickstart__link">View Guide →</span>
                </a>
                <a href="/tools/" class="dp-quickstart__card" data-aos="fade-up" data-aos-delay="300">
                    <div class="dp-quickstart__icon"><i class="fas fa-toolbox"></i></div>
                    <h3>Explore 13,000+ Tools</h3>
                    <p>Browse our complete catalog of AI tools across 22 categories. Legal, education, healthcare, DevOps, security, and more.</p>
                    <span class="dp-quickstart__link">Browse Tools →</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ========== 3. API OVERVIEW ========== -->
    <section class="dp-section dp-section--alt" aria-labelledby="dp-api-title">
        <div class="dp-container">
            <div class="dp-section-header">
                <span class="dp-badge" data-aos="fade-up"><i class="fas fa-cubes"></i> API Reference</span>
                <h2 class="dp-section-title" id="dp-api-title" data-aos="fade-up" data-aos-delay="100">API Overview</h2>
                <p class="dp-section-sub" data-aos="fade-up" data-aos-delay="150">20+ powerful APIs covering every aspect of the Alfred AI platform — from tools and agents to creative AI, deep research, and domain-specific verticals.</p>
            </div>
            <div class="dp-api-grid">
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--chat"><i class="fas fa-comments"></i></div>
                        <h3>Chat API</h3>
                    </div>
                    <ul>
                        <li>Send messages and get AI-powered responses</li>
                        <li>Streaming support with Server-Sent Events</li>
                        <li>Conversation context and memory</li>
                    </ul>
                    <a href="/docs/api-reference#chat" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="150">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--tools"><i class="fas fa-wrench"></i></div>
                        <h3>Tools API</h3>
                    </div>
                    <ul>
                        <li>Execute any of 13,000+ tools across 6 providers</li>
                        <li>Category-based filtering and search</li>
                        <li>Custom tool creation and publishing</li>
                    </ul>
                    <a href="/docs/api-reference#tools" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--agents"><i class="fas fa-robot"></i></div>
                        <h3>Agents API</h3>
                    </div>
                    <ul>
                        <li>Create, configure, and deploy AI agents</li>
                        <li>Define personality, tools, and behaviors</li>
                        <li>Monitor agent performance and logs</li>
                    </ul>
                    <a href="/docs/api-reference#agents" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="250">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--fleet"><i class="fas fa-sitemap"></i></div>
                        <h3>Fleet API</h3>
                    </div>
                    <ul>
                        <li>Manage agent fleets at scale</li>
                        <li>Intelligent routing and load balancing</li>
                        <li>Real-time monitoring and alerting</li>
                    </ul>
                    <a href="/docs/api-reference#fleet" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--voice"><i class="fas fa-phone-alt"></i></div>
                        <h3>Voice API</h3>
                    </div>
                    <ul>
                        <li>Initiate and manage AI-powered calls</li>
                        <li>Conference rooms with live collaboration</li>
                        <li>Call recording and transcription</li>
                    </ul>
                    <a href="/docs/api-reference#voice" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="350">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--market"><i class="fas fa-store"></i></div>
                        <h3>Marketplace API</h3>
                    </div>
                    <ul>
                        <li>Browse and install tools and agents</li>
                        <li>Publish your own tools to the marketplace</li>
                        <li>Revenue sharing and analytics</li>
                    </ul>
                    <a href="/docs/api-reference#marketplace" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--music"><i class="fas fa-music"></i></div>
                        <h3>SSP Music API</h3>
                    </div>
                    <ul>
                        <li>53+ tracks across 10 artists & 8 genres</li>
                        <li>16 world venues with live sync</li>
                        <li>Leaderboards, analytics & DJ profiles</li>
                    </ul>
                    <a href="/docs/api-reference#ssp-music" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="450">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--events"><i class="fas fa-ticket-alt"></i></div>
                        <h3>SSP Events API</h3>
                    </div>
                    <ul>
                        <li>Create events with Solana ticketing (SOL / GSM / USDC)</li>
                        <li>5 ticket tiers with revenue split (85% artist)</li>
                        <li>Live check-in, attendance & revenue dashboards</li>
                    </ul>
                    <a href="/docs/api-reference#ssp-events" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="475">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(0,206,201,.12);color:#00cec9"><i class="fas fa-plug"></i></div>
                        <h3>Tool Providers API</h3>
                    </div>
                    <ul>
                        <li>6 providers: Native (170), MCP (807), External MCP (1,200+), Composio (11,000+), VAPI (85), Marketplace</li>
                        <li>Runtime tool discovery across all providers</li>
                        <li>Provider health checks and statistics</li>
                    </ul>
                    <a href="/docs/api-reference#providers" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="480">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(9,132,227,.12);color:#0984e3"><i class="fas fa-search"></i></div>
                        <h3>Web Search API</h3>
                    </div>
                    <ul>
                        <li>Read any web page with clean markdown extraction</li>
                        <li>Web search via Jina Reader with summarization</li>
                        <li>SSRF protection, rate limiting, caching</li>
                    </ul>
                    <a href="/docs/api-reference#web-search" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="485">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(253,203,110,.12);color:#fdcb6e"><i class="fas fa-book-open"></i></div>
                        <h3>Deep Research API</h3>
                    </div>
                    <ul>
                        <li>Multi-step research pipeline: Plan → Search → Analyze → Synthesize</li>
                        <li>Quick, standard, and deep research modes</li>
                        <li>AI-powered synthesis with source citations</li>
                    </ul>
                    <a href="/docs/api-reference#deep-research" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="487">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(108,92,231,.12);color:#a29bfe"><i class="fas fa-file-alt"></i></div>
                        <h3>Documents API</h3>
                    </div>
                    <ul>
                        <li>Parse PDF, DOCX, CSV, JSON, HTML, XML, images</li>
                        <li>OCR with Tesseract & AI vision models</li>
                        <li>AI summarization and structured data extraction</li>
                    </ul>
                    <a href="/docs/api-reference#documents" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="490">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(253,121,168,.12);color:#fd79a8"><i class="fas fa-palette"></i></div>
                        <h3>Creative AI API</h3>
                    </div>
                    <ul>
                        <li>Image generation: FLUX, DALL-E 3, Stable Diffusion XL</li>
                        <li>Video generation: Kling v1, MiniMax</li>
                        <li>Music (MusicGen) & TTS (F5-TTS, ElevenLabs, OpenAI)</li>
                    </ul>
                    <a href="/docs/api-reference#creative" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="492">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(0,184,148,.12);color:#00b894"><i class="fas fa-paper-plane"></i></div>
                        <h3>Messaging Gateway API</h3>
                    </div>
                    <ul>
                        <li>Unified gateway: Telegram, Discord, Slack, WhatsApp, SMS, Email, Push, Webhook</li>
                        <li>Send and receive across 8 channels with one API</li>
                        <li>Normalized message format with delivery tracking</li>
                    </ul>
                    <a href="/docs/api-reference#messaging" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="494">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(225,112,85,.12);color:#e17055"><i class="fas fa-graduation-cap"></i></div>
                        <h3>Verticals API</h3>
                    </div>
                    <ul>
                        <li>Legal: CanLII (Canadian law), CourtListener (US)</li>
                        <li>Academic: Semantic Scholar papers & citations</li>
                        <li>Translate (DeepL), Math (Wolfram), Finance, Weather</li>
                    </ul>
                    <a href="/docs/api-reference#verticals" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="496">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(162,155,254,.12);color:#a29bfe"><i class="fas fa-project-diagram"></i></div>
                        <h3>MCP Client API</h3>
                    </div>
                    <ul>
                        <li>Connect to 870+ external MCP servers (stdio & SSE)</li>
                        <li>Dynamic server management and tool discovery</li>
                        <li>Resources, prompts, and tool execution proxy</li>
                    </ul>
                    <a href="/docs/api-reference#mcp" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="498">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon" style="background:rgba(116,185,255,.12);color:#74b9ff"><i class="fas fa-link"></i></div>
                        <h3>Composio API</h3>
                    </div>
                    <ul>
                        <li>Bridge to 11,000+ tools across 850+ apps</li>
                        <li>OAuth connection management per user</li>
                        <li>GitHub, Slack, Gmail, Notion, Jira + more</li>
                    </ul>
                    <a href="/docs/api-reference#composio" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="500">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--sanctuary"><i class="fas fa-cross"></i></div>
                        <h3>Sanctuary API</h3>
                    </div>
                    <ul>
                        <li>v4.0 — Brotherhood of Jesus Christ: 60 agents, 50 languages, 13 games connected</li>
                        <li>51 KJV scriptures, 13 Names of Jesus, 41-generation Lineage of Perez</li>
                        <li>12 classrooms with whiteboards, 12 biblical activities, Game Engine SDK</li>
                        <li>Donation Foundation, Gospel Music Studio, prayer ministry, voice commands</li>
                    </ul>
                    <a href="/docs/api-reference#sanctuary" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="525">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--sanctuary"><i class="fas fa-users"></i></div>
                        <h3>Brotherhood API</h3>
                    </div>
                    <ul>
                        <li>60 multilingual agents across 14 roles (apostles, teachers, evangelists…)</li>
                        <li>50 languages with greetings, Jesus name in native script, RTL support</li>
                        <li>13 interconnected games, 12 biblical activities, voice commands</li>
                        <li>Game Engine SDK v2.1, real-time translation, mission statistics</li>
                    </ul>
                    <a href="/docs/api-reference#brotherhood" class="dp-api-card__link">View Docs →</a>
                </article>
                <article class="dp-api-card" data-aos="fade-up" data-aos-delay="550">
                    <div class="dp-api-card__head">
                        <div class="dp-api-card__icon dp-api-card__icon--sanctuary"><i class="fas fa-music"></i></div>
                        <h3>SSP Gospel Music API</h3>
                    </div>
                    <ul>
                        <li>30 gospel tracks, 12 genres, 16 instruments, 16 Psalms</li>
                        <li>12 nature worship environments (Garden of Eden, Sea of Galilee…)</li>
                        <li>DJ mixer, automix presets, track creation with token system</li>
                    </ul>
                    <a href="/docs/api-reference#ssp-gospel" class="dp-api-card__link">View Docs →</a>
                </article>
            </div>
        </div>
    </section>

    <!-- ========== 4. SDKs ========== -->
    <section class="dp-section" aria-labelledby="dp-sdk-title">
        <div class="dp-container">
            <div class="dp-section-header">
                <span class="dp-badge" data-aos="fade-up"><i class="fas fa-laptop-code"></i> SDKs</span>
                <h2 class="dp-section-title" id="dp-sdk-title" data-aos="fade-up" data-aos-delay="100">Official SDKs</h2>
                <p class="dp-section-sub" data-aos="fade-up" data-aos-delay="150">First-class support for Node.js, Python, PHP, and the Game Engine. Install and start building in seconds.</p>
            </div>
            <div class="dp-sdk-grid">
                <!-- Node.js -->
                <div class="dp-sdk-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="dp-sdk-card__header">
                        <span class="dp-sdk-card__lang"><i class="fab fa-node-js" style="color:#68a063"></i> Node.js</span>
                        <span class="dp-sdk-card__install">npm install alfred-ai-sdk</span>
                        <button class="dp-sdk-card__copy" data-copy="sdk-node" aria-label="Copy code"><i class="fas fa-copy"></i></button>
                    </div>
                    <pre id="sdk-node"><span class="c-kw">import</span> { Alfred } <span class="c-kw">from</span> <span class="c-str">'alfred-ai-sdk'</span>;

<span class="c-kw">const</span> <span class="c-var">alfred</span> = <span class="c-kw">new</span> <span class="c-fn">Alfred</span>(<span class="c-str">'your_api_key'</span>);
<span class="c-kw">const</span> <span class="c-var">response</span> = <span class="c-kw">await</span> alfred.<span class="c-fn">chat</span>(<span class="c-str">'Deploy WordPress'</span>);
console.<span class="c-fn">log</span>(response.message);</pre>
                </div>
                <!-- Python -->
                <div class="dp-sdk-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="dp-sdk-card__header">
                        <span class="dp-sdk-card__lang"><i class="fab fa-python" style="color:#3776ab"></i> Python</span>
                        <span class="dp-sdk-card__install">pip install alfred-ai</span>
                        <button class="dp-sdk-card__copy" data-copy="sdk-python" aria-label="Copy code"><i class="fas fa-copy"></i></button>
                    </div>
                    <pre id="sdk-python"><span class="c-kw">from</span> alfred_ai <span class="c-kw">import</span> Alfred

<span class="c-var">client</span> = Alfred(api_key=<span class="c-str">"your_api_key"</span>)
<span class="c-var">response</span> = client.<span class="c-fn">chat</span>(<span class="c-str">"Deploy WordPress"</span>)
<span class="c-fn">print</span>(response.message)</pre>
                </div>
                <!-- PHP -->
                <div class="dp-sdk-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="dp-sdk-card__header">
                        <span class="dp-sdk-card__lang"><i class="fab fa-php" style="color:#777bb4"></i> PHP</span>
                        <span class="dp-sdk-card__install">composer require gositeme/alfred-ai</span>
                        <button class="dp-sdk-card__copy" data-copy="sdk-php" aria-label="Copy code"><i class="fas fa-copy"></i></button>
                    </div>
                    <pre id="sdk-php"><span class="c-kw">use</span> GoSiteMe\Alfred\<span class="c-fn">Client</span>;

<span class="c-var">$alfred</span> = <span class="c-kw">new</span> <span class="c-fn">Client</span>(<span class="c-str">'your_api_key'</span>);
<span class="c-var">$response</span> = <span class="c-var">$alfred</span>-><span class="c-fn">chat</span>(<span class="c-str">'Deploy WordPress'</span>);
<span class="c-fn">echo</span> <span class="c-var">$response</span>->message;</pre>
                </div>
                <!-- Game Engine SDK -->
                <div class="dp-sdk-card" data-aos="fade-up" data-aos-delay="400" style="grid-column:1/-1">
                    <div class="dp-sdk-card__header">
                        <span class="dp-sdk-card__lang"><i class="fas fa-gamepad" style="color:#a29bfe"></i> Game Engine SDK v2.1</span>
                        <span class="dp-sdk-card__install">3D · WebXR · Spatial Audio · AI Negotiation · Gamepad</span>
                        <a href="/sdks/game-engine/" class="dp-sdk-card__copy" style="text-decoration:none"><i class="fas fa-arrow-right"></i> Docs</a>
                    </div>
                    <pre id="sdk-game"><span class="c-cm">// Build immersive 3D WebXR multiplayer games with v2.1</span>
<span class="c-kw">const</span> <span class="c-var">game</span> = <span class="c-kw">new</span> <span class="c-fn">GoSiteMeGame</span>({
    <span class="c-var">name</span>: <span class="c-str">'My 3D Game'</span>,
    <span class="c-var">scene</span>: { <span class="c-var">shadows</span>: <span class="c-kw">true</span>, <span class="c-var">fogDensity</span>: <span class="c-op">0.03</span> },
    <span class="c-var">wsChannel</span>: <span class="c-str">'vr:my-game'</span>,    <span class="c-cm">// multiplayer</span>
    <span class="c-var">proximityAudio</span>: <span class="c-kw">true</span>,       <span class="c-cm">// spatial sound by distance</span>
    <span class="c-var">agentNegotiation</span>: <span class="c-kw">true</span>,     <span class="c-cm">// AI challenge dialogues</span>
    <span class="c-var">gamepad</span>: <span class="c-kw">true</span>,               <span class="c-cm">// USB/Bluetooth gamepad</span>
    <span class="c-var">vr</span>: <span class="c-kw">true</span>,                     <span class="c-cm">// WebXR support</span>
});
<span class="c-var">game</span>.<span class="c-fn">start</span>();  <span class="c-cm">// 18 modules — rendering, camera, audio, AI, negotiation, gamepad…</span></pre>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== 5. API KEY MANAGEMENT ========== -->
    <section class="dp-section dp-section--alt" id="api-keys" aria-labelledby="dp-keys-title">
        <div class="dp-container">
            <div class="dp-section-header">
                <span class="dp-badge" data-aos="fade-up"><i class="fas fa-shield-halved"></i> Authentication</span>
                <h2 class="dp-section-title" id="dp-keys-title" data-aos="fade-up" data-aos-delay="100">API Keys</h2>
            </div>
            <div class="dp-keys" data-aos="fade-up" data-aos-delay="200">
                <?php if ($is_logged_in): ?>
                <div class="dp-keys__table-wrap" id="dp-keys-container">
                    <div class="dp-keys__toolbar">
                        <h3>Your API Keys</h3>
                        <button class="dp-btn dp-btn--sm" id="dp-gen-key-btn"><i class="fas fa-plus"></i> Generate New Key</button>
                    </div>
                    <table class="dp-keys__table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Key</th>
                                <th>Created</th>
                                <th>Last Used</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="dp-keys-tbody">
                            <tr><td colspan="5" style="text-align:center;color:var(--dp-text-muted);padding:40px">Loading keys…</td></tr>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="dp-keys__empty">
                    <i class="fas fa-lock"></i>
                    <p>Sign up to get your API key and start building with Alfred.</p>
                    <a href="/register" class="dp-btn"><i class="fas fa-user-plus"></i> Create Free Account</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ========== 6. RATE LIMITS ========== -->
    <section class="dp-section" aria-labelledby="dp-rates-title">
        <div class="dp-container">
            <div class="dp-section-header">
                <span class="dp-badge" data-aos="fade-up"><i class="fas fa-gauge-high"></i> Rate Limits</span>
                <h2 class="dp-section-title" id="dp-rates-title" data-aos="fade-up" data-aos-delay="100">Rate Limits by Tier</h2>
                <p class="dp-section-sub" data-aos="fade-up" data-aos-delay="150">Generous limits on every tier. Need more? Upgrade anytime or contact sales for custom limits.</p>

                <!-- Founders Free Tier Promo -->
                <div style="margin:1.5rem 0;padding:1.2rem 1.5rem;border-radius:14px;background:linear-gradient(135deg,rgba(0,255,136,0.08),rgba(0,212,255,0.08));border:1px solid rgba(0,255,136,0.2);display:flex;align-items:center;gap:1rem;flex-wrap:wrap;" data-aos="fade-up" data-aos-delay="175">
                    <span style="font-size:2rem;">🎁</span>
                    <div style="flex:1;min-width:200px;">
                        <strong style="color:var(--dp-green,#00FF88);font-size:1rem;">Founders Free Tier — All of 2026</strong>
                        <div style="font-size:0.82rem;color:var(--dp-text-muted,#8a8aa0);margin-top:0.2rem;">All new API keys automatically get <strong>10,000 requests/day</strong> free through December 31, 2026. No credit card required.</div>
                    </div>
                    <a href="/live-demo.php" style="padding:0.5rem 1rem;border-radius:8px;background:linear-gradient(135deg,#00FF88,#00cc66);color:#000;font-weight:700;font-size:0.82rem;text-decoration:none;white-space:nowrap;">Try Live Demo →</a>
                </div>

            </div>
            <div class="dp-rates" data-aos="fade-up" data-aos-delay="200">
                <div class="dp-rates__table-wrap">
                    <table class="dp-rates__table">
                        <thead>
                            <tr>
                                <th>Tier</th>
                                <th>Requests / min</th>
                                <th>Burst / sec</th>
                                <th>Daily Limit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:rgba(0,255,136,0.04);">
                                <td><span class="dp-rates__tier"><span class="dp-rates__tier-dot" style="background:var(--dp-green,#00FF88)"></span> Founders Free <small style="opacity:0.6;">(2026)</small></span></td>
                                <td><span class="dp-rates__val">300</span></td>
                                <td><span class="dp-rates__val">10</span></td>
                                <td><span class="dp-rates__val" style="color:var(--dp-green,#00FF88);">10,000</span></td>
                            </tr>
                            <tr>
                                <td><span class="dp-rates__tier"><span class="dp-rates__tier-dot" style="background:var(--dp-green)"></span> Starter</span></td>
                                <td><span class="dp-rates__val">300</span></td>
                                <td><span class="dp-rates__val">10</span></td>
                                <td><span class="dp-rates__val">10,000</span></td>
                            </tr>
                            <tr>
                                <td><span class="dp-rates__tier"><span class="dp-rates__tier-dot" style="background:var(--dp-blue)"></span> Professional</span></td>
                                <td><span class="dp-rates__val">1,000</span></td>
                                <td><span class="dp-rates__val">30</span></td>
                                <td><span class="dp-rates__val">100,000</span></td>
                            </tr>
                            <tr>
                                <td><span class="dp-rates__tier"><span class="dp-rates__tier-dot" style="background:var(--dp-accent)"></span> Enterprise</span></td>
                                <td><span class="dp-rates__val">5,000</span></td>
                                <td><span class="dp-rates__val">100</span></td>
                                <td><span class="dp-rates__val">500,000</span></td>
                            </tr>
                            <tr>
                                <td><span class="dp-rates__tier"><span class="dp-rates__tier-dot" style="background:var(--dp-orange)"></span> Enterprise+</span></td>
                                <td><span class="dp-rates__val">10,000</span></td>
                                <td><span class="dp-rates__val">200</span></td>
                                <td><span class="dp-rates__val">Unlimited</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== 7. WEBHOOK EVENTS ========== -->
    <section class="dp-section dp-section--alt" aria-labelledby="dp-wh-title">
        <div class="dp-container">
            <div class="dp-section-header">
                <span class="dp-badge" data-aos="fade-up"><i class="fas fa-bolt"></i> Webhooks</span>
                <h2 class="dp-section-title" id="dp-wh-title" data-aos="fade-up" data-aos-delay="100">Webhook Events</h2>
                <p class="dp-section-sub" data-aos="fade-up" data-aos-delay="150">Subscribe to real-time events and build reactive integrations.</p>
            </div>
            <div class="dp-webhooks" data-aos="fade-up" data-aos-delay="200">
                <div class="dp-accordion">
                    <!-- Agent Events -->
                    <div class="dp-accordion__item">
                        <button class="dp-accordion__trigger" aria-expanded="false">
                            <span><i class="fas fa-robot" style="color:var(--dp-blue);margin-right:10px"></i> Agent Events</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dp-accordion__body" role="region">
                            <div class="dp-event">
                                <span class="dp-event__name">agent.created</span>
                                <span class="dp-event__desc">Fired when a new AI agent is created and configured.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">agent.deployed</span>
                                <span class="dp-event__desc">Fired when an agent is deployed and becomes active.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">agent.error</span>
                                <span class="dp-event__desc">Fired when an agent encounters an error during execution.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">agent.status_changed</span>
                                <span class="dp-event__desc">Fired when an agent's status changes (active, paused, stopped).</span>
                            </div>
                        </div>
                    </div>
                    <!-- Call Events -->
                    <div class="dp-accordion__item">
                        <button class="dp-accordion__trigger" aria-expanded="false">
                            <span><i class="fas fa-phone-alt" style="color:var(--dp-green);margin-right:10px"></i> Call Events</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dp-accordion__body" role="region">
                            <div class="dp-event">
                                <span class="dp-event__name">call.started</span>
                                <span class="dp-event__desc">Fired when a voice call is initiated and connected.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">call.ended</span>
                                <span class="dp-event__desc">Fired when a call ends, includes duration and transcript.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">call.transferred</span>
                                <span class="dp-event__desc">Fired when a call is transferred to another agent or human.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">call.recorded</span>
                                <span class="dp-event__desc">Fired when a call recording is available for download.</span>
                            </div>
                        </div>
                    </div>
                    <!-- Fleet Events -->
                    <div class="dp-accordion__item">
                        <button class="dp-accordion__trigger" aria-expanded="false">
                            <span><i class="fas fa-sitemap" style="color:var(--dp-orange);margin-right:10px"></i> Fleet Events</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dp-accordion__body" role="region">
                            <div class="dp-event">
                                <span class="dp-event__name">fleet.deployed</span>
                                <span class="dp-event__desc">Fired when a fleet is deployed with its agent configuration.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">fleet.alert</span>
                                <span class="dp-event__desc">Fired when a fleet health alert or threshold is triggered.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">fleet.agent_joined</span>
                                <span class="dp-event__desc">Fired when a new agent is added to a fleet.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">fleet.agent_left</span>
                                <span class="dp-event__desc">Fired when an agent is removed from a fleet.</span>
                            </div>
                        </div>
                    </div>
                    <!-- Billing Events -->
                    <div class="dp-accordion__item">
                        <button class="dp-accordion__trigger" aria-expanded="false">
                            <span><i class="fas fa-credit-card" style="color:var(--dp-accent-light);margin-right:10px"></i> Billing Events</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dp-accordion__body" role="region">
                            <div class="dp-event">
                                <span class="dp-event__name">billing.payment_succeeded</span>
                                <span class="dp-event__desc">Fired when a payment is successfully processed.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">billing.payment_failed</span>
                                <span class="dp-event__desc">Fired when a payment attempt fails.</span>
                            </div>
                            <div class="dp-event">
                                <span class="dp-event__name">billing.usage_alert</span>
                                <span class="dp-event__desc">Fired when API usage reaches 80% or 100% of plan limits.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== 8. API PLAYGROUND ========== -->
    <section class="dp-section" id="playground" aria-labelledby="dp-play-title">
        <div class="dp-container">
            <div class="dp-section-header">
                <span class="dp-badge" data-aos="fade-up"><i class="fas fa-play"></i> Try It</span>
                <h2 class="dp-section-title" id="dp-play-title" data-aos="fade-up" data-aos-delay="100">API Playground</h2>
                <p class="dp-section-sub" data-aos="fade-up" data-aos-delay="150">Test API endpoints live. Enter your API key, pick an endpoint, and send a request.</p>
            </div>
            <div class="dp-playground" data-aos="fade-up" data-aos-delay="200">
                <div class="dp-playground__wrap">
                    <div class="dp-playground__toolbar">
                        <select class="dp-playground__select" id="dp-pg-endpoint" aria-label="Select endpoint">
                            <option value="GET /v1/tools">GET /v1/tools</option>
                            <option value="POST /v1/chat" selected>POST /v1/chat</option>
                            <option value="GET /v1/agents">GET /v1/agents</option>
                            <option value="POST /v1/agents">POST /v1/agents</option>
                            <option value="GET /v1/fleet">GET /v1/fleet</option>
                            <option value="POST /v1/voice/call">POST /v1/voice/call</option>
                            <option value="GET /v1/tools/providers">GET /v1/tools/providers</option>
                            <option value="POST /v1/research">POST /v1/research</option>
                            <option value="POST /v1/creative/image">POST /v1/creative/image</option>
                            <option value="POST /v1/translate">POST /v1/translate</option>
                            <option value="POST /v1/documents/parse">POST /v1/documents/parse</option>
                        </select>
                        <input type="text" class="dp-playground__input" id="dp-pg-apikey" placeholder="Enter your API key (ak_live_...)" aria-label="API key">
                        <button class="dp-btn dp-btn--sm" id="dp-pg-send"><i class="fas fa-paper-plane"></i> Send Request</button>
                    </div>
                    <div class="dp-playground__body">
                        <div class="dp-playground__req">
                            <div class="dp-playground__req-label">Request Body</div>
                            <textarea class="dp-playground__textarea" id="dp-pg-body" spellcheck="false" aria-label="Request body"></textarea>
                        </div>
                        <div class="dp-playground__res">
                            <div class="dp-playground__res-label">Response</div>
                            <div class="dp-playground__output" id="dp-pg-output">Response will appear here…</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== 9. FOOTER CTA ========== -->
    <section class="dp-cta" aria-labelledby="dp-cta-title">
        <div class="dp-container">
            <h2 id="dp-cta-title" data-aos="fade-up">Ready to build?</h2>
            <p data-aos="fade-up" data-aos-delay="100">Join thousands of developers building with Alfred AI. Start for free today.</p>
            <div data-aos="fade-up" data-aos-delay="200">
                <a href="/register" class="dp-btn"><i class="fas fa-rocket"></i> Get Started Free</a>
            </div>
        </div>
    </section>

</main>

<!-- Generate Key Modal -->
<?php if ($is_logged_in): ?>
<div class="dp-modal-overlay" id="dp-key-modal">
    <div class="dp-modal">
        <button class="dp-modal__close" id="dp-modal-close" aria-label="Close modal"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-key" style="color:var(--dp-accent);margin-right:8px"></i> Generate New API Key</h3>
        <div class="dp-modal__field">
            <label for="dp-key-name">Key Name</label>
            <input type="text" id="dp-key-name" placeholder="e.g. Production, Staging, My App">
        </div>
        <div class="dp-modal__field">
            <label for="dp-key-scope">Scope (optional)</label>
            <input type="text" id="dp-key-scope" placeholder="chat,tools,agents (default: all)">
        </div>
        <button class="dp-btn dp-btn--block" id="dp-key-create"><i class="fas fa-plus"></i> Create Key</button>
        <div id="dp-key-result" style="margin-top:16px;display:none">
            <p style="font-size:.88rem;color:var(--dp-text-muted);margin:0 0 8px">Your new API key (copy it now — it won't be shown again):</p>
            <div style="background:var(--dp-bg);border:1px solid var(--dp-border);border-radius:8px;padding:12px;font-family:'JetBrains Mono',monospace;font-size:.85rem;color:var(--dp-green);word-break:break-all" id="dp-key-value"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/assets/js/developer-portal-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
