<?php
/**
 * AKJV Bible — Shared Styles
 * ═══════════════════════════
 * Outputs the complete CSS for all Bible pages.
 * Domain-agnostic: works on any domain's layout.
 * Call akjv_styles_reader() for the reader, akjv_styles_prophecies() for prophecies,
 * or akjv_styles_dashboard() for the main Bible dashboard.
 */

function akjv_css_variables(): string {
    return <<<'CSS'
:root {
    --akjv-bg: #0a0a0f;
    --akjv-surface: rgba(255,255,255,.03);
    --akjv-border: rgba(255,215,0,.1);
    --akjv-gold: #ffd700;
    --akjv-gold2: #f59e0b;
    --akjv-red: #dc2626;
    --akjv-blood: #991b1b;
    --akjv-green: #22c55e;
    --akjv-blue: #3b82f6;
    --akjv-purple: #8b5cf6;
    --akjv-white: #f0f0f5;
    --akjv-muted: rgba(240,240,245,.5);
    --akjv-dim: rgba(240,240,245,.3);
}
CSS;
}

function akjv_styles_dashboard(): string {
    return akjv_css_variables() . <<<'CSS'

.akjv-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 4rem; color: var(--akjv-white); }
.akjv-hero { text-align: center; padding: 80px 0 50px; position: relative; }
.akjv-hero::after { content:''; position:absolute; bottom:0; left:15%; right:15%; height:1px; background:linear-gradient(90deg,transparent,var(--akjv-gold),transparent); }
.akjv-mission-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 18px; border-radius:999px; background:rgba(220,38,38,.12); border:1px solid rgba(220,38,38,.3); color:var(--akjv-red); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.15em; margin-bottom:1.5rem; }
.akjv-hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; line-height: 1.15; margin-bottom: .8rem; }
.akjv-hero h1 .gold { color: var(--akjv-gold); }
.akjv-hero h1 .red { color: var(--akjv-red); }
.akjv-hero .subtitle { font-size: 1.15rem; color: var(--akjv-muted); max-width: 700px; margin: 0 auto 2rem; line-height: 1.6; }
.akjv-hero .authority-verse { background: linear-gradient(135deg, rgba(255,215,0,.08), rgba(220,38,38,.08)); border: 1px solid rgba(255,215,0,.2); border-radius: 14px; padding: 1.5rem 2rem; max-width: 750px; margin: 0 auto; text-align: left; }
.akjv-hero .authority-verse .ref { color: var(--akjv-gold); font-weight: 700; font-size: .9rem; margin-bottom: .5rem; }
.akjv-hero .authority-verse .text { color: rgba(255,255,255,.85); font-style: italic; line-height: 1.7; font-size: .95rem; }
.akjv-hero .authority-verse .peres { color: var(--akjv-red); font-weight: 800; font-style: normal; font-size: 1.05rem; }
.akjv-hero .authority-verse .note { color: var(--akjv-gold2); font-size: .82rem; margin-top: .8rem; font-style: normal; font-weight: 600; }
.akjv-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .8rem; margin: 2.5rem 0; }
.akjv-stat { background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 12px; padding: 1rem; text-align: center; }
.akjv-stat .val { font-size: 1.8rem; font-weight: 800; }
.akjv-stat .val.gold { color: var(--akjv-gold); }
.akjv-stat .val.red { color: var(--akjv-red); }
.akjv-stat .val.green { color: var(--akjv-green); }
.akjv-stat .val.purple { color: var(--akjv-purple); }
.akjv-stat .label { font-size: .72rem; color: var(--akjv-dim); text-transform: uppercase; letter-spacing: .5px; margin-top: .2rem; }
.akjv-section { background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 16px; margin-bottom: 2rem; overflow: hidden; }
.akjv-section-head { padding: 1.2rem 1.5rem; display: flex; align-items: center; gap: .8rem; cursor: pointer; user-select: none; }
.akjv-section-head:hover { background: rgba(255,255,255,.02); }
.akjv-section-head .num { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .95rem; flex-shrink: 0; }
.akjv-section-head .num.gold-bg { background: rgba(255,215,0,.12); color: var(--akjv-gold); }
.akjv-section-head .num.red-bg { background: rgba(220,38,38,.12); color: var(--akjv-red); }
.akjv-section-head .num.green-bg { background: rgba(34,197,94,.12); color: var(--akjv-green); }
.akjv-section-head .num.blue-bg { background: rgba(59,130,246,.12); color: var(--akjv-blue); }
.akjv-section-head .num.purple-bg { background: rgba(139,92,246,.12); color: var(--akjv-purple); }
.akjv-section-head h2 { font-size: 1.15rem; font-weight: 700; color: #fff; margin: 0; }
.akjv-section-body { padding: 0 1.5rem 1.5rem; color: rgba(255,255,255,.75); line-height: 1.7; font-size: .92rem; }
.akjv-section-body h3 { color: var(--akjv-gold); font-size: .95rem; margin: 1.3rem 0 .5rem; }
.expose-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.expose-table th { text-align: left; padding: .6rem .8rem; font-size: .72rem; color: var(--akjv-dim); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--akjv-border); }
.expose-table td { padding: .7rem .8rem; border-bottom: 1px solid rgba(255,255,255,.04); font-size: .85rem; vertical-align: top; }
.expose-table tr:hover td { background: rgba(255,255,255,.02); }
.expose-table .original { color: var(--akjv-red); font-weight: 600; text-decoration: line-through; }
.expose-table .restored { color: var(--akjv-green); font-weight: 700; }
.expose-table .book { color: var(--akjv-gold); font-weight: 500; }
.expose-table .testament-badge { font-size: .68rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
.expose-table .badge-ot { background: rgba(59,130,246,.15); color: var(--akjv-blue); }
.expose-table .badge-nt { background: rgba(139,92,246,.15); color: var(--akjv-purple); }
.expose-table .badge-ap { background: rgba(245,158,11,.15); color: var(--akjv-gold2); }
.book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .5rem; margin: 1rem 0; }
.book-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 8px; padding: .5rem .7rem; display: flex; align-items: center; gap: .5rem; font-size: .78rem; transition: .2s; min-width: 0; }
.book-card:hover { border-color: var(--akjv-gold); background: rgba(255,215,0,.04); }
.book-card .bnum { width: 26px; height: 26px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: .68rem; font-weight: 700; flex-shrink: 0; }
.book-card .bnum.ot { background: rgba(59,130,246,.12); color: var(--akjv-blue); }
.book-card .bnum.nt { background: rgba(139,92,246,.12); color: var(--akjv-purple); }
.book-card .bnum.ap { background: rgba(245,158,11,.12); color: var(--akjv-gold2); }
.book-card .bnum.en { background: rgba(220,38,38,.12); color: var(--akjv-red); }
.book-card .bname { color: #fff; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.book-card .bchap { color: var(--akjv-dim); font-size: .68rem; white-space: nowrap; flex-shrink: 0; }
.book-card.has-perez { border-color: rgba(255,215,0,.3); background: rgba(255,215,0,.05); }
.book-card.has-perez .bname { color: var(--akjv-gold); }
.book-card.removed { border-color: rgba(220,38,38,.3); background: rgba(220,38,38,.04); }
.conceal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 1rem 0; }
.conceal-card { background: rgba(220,38,38,.06); border: 1px solid rgba(220,38,38,.2); border-radius: 12px; padding: 1.2rem; }
.conceal-card h4 { color: var(--akjv-red); font-size: .95rem; margin: 0 0 .5rem; }
.conceal-card p { color: rgba(255,255,255,.7); font-size: .85rem; line-height: 1.6; margin: 0; }
.conceal-card .hebrew { font-size: 1.8rem; color: var(--akjv-gold); font-weight: 400; margin: .3rem 0; }
.authority-block { background: linear-gradient(135deg, rgba(255,215,0,.08), rgba(220,38,38,.06)); border: 2px solid rgba(255,215,0,.2); border-radius: 16px; padding: 2rem; margin: 2rem 0; text-align: center; }
.authority-block h3 { color: var(--akjv-gold); font-size: 1.3rem; margin-bottom: 1rem; }
.authority-block .wall-text { font-size: 2.5rem; font-weight: 900; letter-spacing: .15em; margin: 1rem 0; }
.authority-block .wall-text .m { color: var(--akjv-gold); }
.authority-block .wall-text .t { color: var(--akjv-red); }
.authority-block .wall-text .p { color: #fff; text-decoration: underline; text-underline-offset: 6px; }
.authority-block .interp { color: rgba(255,255,255,.7); font-size: .9rem; line-height: 1.8; max-width: 600px; margin: 1rem auto; }
.authority-block .interp strong { color: var(--akjv-gold); }
.trinity-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem; margin: 1.5rem 0; }
.trinity-card { border-radius: 14px; padding: 1.5rem; text-align: center; position: relative; overflow: hidden; }
.trinity-card.father { background: linear-gradient(135deg, rgba(220,38,38,.08), rgba(220,38,38,.03)); border: 1px solid rgba(220,38,38,.25); }
.trinity-card.son { background: linear-gradient(135deg, rgba(255,215,0,.1), rgba(255,215,0,.03)); border: 2px solid rgba(255,215,0,.3); }
.trinity-card.breaker { background: linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); border: 1px solid rgba(255,255,255,.15); }
.trinity-card .t-word { font-size: 1.6rem; font-weight: 900; letter-spacing: .1em; margin-bottom: .3rem; }
.trinity-card .t-hebrew { font-size: 1.3rem; color: var(--akjv-gold); margin-bottom: .5rem; }
.trinity-card .t-role { font-size: .78rem; text-transform: uppercase; letter-spacing: .12em; font-weight: 700; margin-bottom: .8rem; }
.trinity-card.father .t-word { color: var(--akjv-red); }
.trinity-card.father .t-role { color: var(--akjv-red); }
.trinity-card.son .t-word { color: var(--akjv-gold); }
.trinity-card.son .t-role { color: var(--akjv-gold); }
.trinity-card.breaker .t-word { color: #fff; }
.trinity-card.breaker .t-role { color: rgba(255,255,255,.7); }
.trinity-card .t-portion { font-size: 2.5rem; font-weight: 900; margin: .3rem 0; }
.trinity-card.father .t-portion { color: var(--akjv-red); }
.trinity-card.son .t-portion { color: var(--akjv-gold); }
.trinity-card.breaker .t-portion { color: #fff; }
.trinity-card .t-desc { color: rgba(255,255,255,.65); font-size: .85rem; line-height: 1.6; }
.trinity-card .t-verse { color: var(--akjv-dim); font-size: .75rem; margin-top: .8rem; font-style: italic; }
.breaker-block { background: linear-gradient(135deg, rgba(255,215,0,.06), rgba(255,255,255,.02)); border: 2px solid rgba(255,215,0,.15); border-radius: 16px; padding: 2rem; margin: 2rem 0; text-align: center; }
.breaker-block .breaker-hebrew { font-size: 3rem; color: var(--akjv-gold); margin: .5rem 0; letter-spacing: .05em; }
.breaker-block .breaker-trans { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
.breaker-block .breaker-verse { color: rgba(255,255,255,.8); font-style: italic; line-height: 1.8; font-size: .95rem; max-width: 650px; margin: 0 auto; }
.breaker-block .breaker-verse strong { color: var(--akjv-gold); font-style: normal; }
.wordplay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.5rem 0; }
.wordplay-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,215,0,.12); border-radius: 12px; padding: 1.2rem; text-align: center; }
.wordplay-card .wp-hebrew { font-size: 1.8rem; color: var(--akjv-gold); margin-bottom: .3rem; }
.wordplay-card .wp-trans { font-size: .82rem; color: #fff; font-weight: 600; margin-bottom: .3rem; }
.wordplay-card .wp-meaning { font-size: .78rem; color: var(--akjv-dim); line-height: 1.5; }
.daniel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin: 1rem 0; }
.daniel-card { background: rgba(255,215,0,.04); border: 1px solid rgba(255,215,0,.12); border-radius: 12px; padding: 1.3rem; }
.daniel-card h4 { color: var(--akjv-gold); font-size: .95rem; margin: 0 0 .5rem; }
.daniel-card .d-book { color: var(--akjv-red); font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; margin-bottom: .5rem; }
.daniel-card p { color: rgba(255,255,255,.7); font-size: .85rem; line-height: 1.6; margin: 0; }
.daniel-card .d-parallel { color: var(--akjv-gold2); font-size: .8rem; margin-top: .6rem; font-weight: 600; }
@media (max-width: 700px) { .wordplay-grid { grid-template-columns: 1fr; } }
@media (max-width: 600px) {
    .akjv-hero h1 { font-size: 1.8rem; }
    .authority-block .wall-text { font-size: 1.6rem; }
    .book-grid { grid-template-columns: 1fr; }
    .conceal-grid { grid-template-columns: 1fr; }
    .trinity-grid { grid-template-columns: 1fr; }
}
CSS;
}

function akjv_styles_reader(): string {
    return akjv_css_variables() . <<<'CSS'

.bible-reader { display: grid; grid-template-columns: 280px 1fr; min-height: 80vh; max-width: 1300px; margin: 0 auto; gap: 0; }
.bible-sidebar { background: rgba(255,255,255,.02); border-right: 1px solid var(--akjv-border); padding: 1rem 0; overflow-y: auto; max-height: 85vh; position: sticky; top: 80px; }
.bible-sidebar h3 { padding: 0 1rem; color: var(--akjv-gold); font-size: .85rem; margin: 0 0 .5rem; }
.sidebar-search { margin: 0 .8rem .8rem; }
.sidebar-search input { width: 100%; padding: 8px 12px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #fff; font-size: .82rem; outline: none; }
.sidebar-search input:focus { border-color: var(--akjv-gold); }
.sidebar-testament { padding: .5rem .8rem .3rem; font-size: .7rem; color: var(--akjv-gold); text-transform: uppercase; letter-spacing: .12em; font-weight: 700; margin-top: .8rem; border-top: 1px solid var(--akjv-border); }
.sidebar-testament:first-of-type { border-top: none; margin-top: 0; }
.sidebar-book { display: block; padding: 6px 1rem; font-size: .82rem; color: rgba(255,255,255,.6); text-decoration: none; transition: .15s; cursor: pointer; border-left: 2px solid transparent; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-book:hover { background: rgba(255,255,255,.04); color: #fff; }
.sidebar-book.active { background: rgba(255,215,0,.06); color: var(--akjv-gold); border-left-color: var(--akjv-gold); font-weight: 600; }
.sidebar-book .sb-num { color: var(--akjv-dim); font-size: .72rem; margin-right: 6px; min-width: 22px; display: inline-block; }
.sidebar-book.has-perez::after { content: '✝'; color: var(--akjv-gold); font-size: .7rem; margin-left: 4px; }
.sidebar-book.no-verses { opacity: .55; }
.bible-main { padding: 2rem 3rem; max-width: 750px; }
.bible-header { margin-bottom: 2rem; }
.bible-header h1 { font-size: 2rem; font-weight: 800; color: #fff; margin: 0 0 .3rem; }
.bible-header .book-meta { color: var(--akjv-dim); font-size: .82rem; }
.bible-header .book-meta .testament { padding: 2px 8px; border-radius: 4px; font-size: .72rem; font-weight: 600; margin-left: 8px; }
.bible-header .book-meta .t-ot { background: rgba(59,130,246,.12); color: var(--akjv-blue); }
.bible-header .book-meta .t-nt { background: rgba(139,92,246,.12); color: var(--akjv-purple); }
.bible-header .book-meta .t-ap { background: rgba(245,158,11,.12); color: #f59e0b; }
.bible-header .book-meta .t-en { background: rgba(220,38,38,.12); color: var(--akjv-red); }
.chapter-selector { display: flex; flex-wrap: wrap; gap: 4px; margin: 1rem 0; }
.chapter-selector a { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 32px; border-radius: 6px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.06); color: rgba(255,255,255,.5); text-decoration: none; font-size: .78rem; font-weight: 500; transition: .15s; }
.chapter-selector a:hover { background: rgba(255,215,0,.08); border-color: rgba(255,215,0,.2); color: var(--akjv-gold); }
.chapter-selector a.active { background: rgba(255,215,0,.12); border-color: var(--akjv-gold); color: var(--akjv-gold); font-weight: 700; }
.verse-container { margin-top: 1.5rem; }
.verse { padding: .6rem 0; line-height: 1.85; color: rgba(240,240,245,.85); font-size: 1.05rem; border-bottom: 1px solid rgba(255,255,255,.03); }
.verse:hover { background: rgba(255,255,255,.02); }
.verse .vnum { color: var(--akjv-gold); font-weight: 700; font-size: .78rem; margin-right: 8px; vertical-align: super; user-select: none; }
.verse.corrected { background: rgba(255,215,0,.04); border-left: 3px solid var(--akjv-gold); padding-left: 12px; }
.verse .correction-badge { display: inline-block; margin-left: 8px; padding: 2px 8px; background: rgba(255,215,0,.1); border: 1px solid rgba(255,215,0,.2); border-radius: 4px; font-size: .7rem; color: var(--akjv-gold); font-weight: 600; cursor: help; vertical-align: middle; }
.bible-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--akjv-border); }
.bible-nav a { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: rgba(255,255,255,.7); text-decoration: none; font-size: .85rem; transition: .15s; }
.bible-nav a:hover { background: rgba(255,215,0,.08); border-color: var(--akjv-gold); color: var(--akjv-gold); }
.kjv-toggle { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: var(--akjv-dim); font-size: .78rem; cursor: pointer; user-select: none; }
.kjv-toggle:hover { border-color: rgba(255,255,255,.2); color: #fff; }
.kjv-original { display: none; color: var(--akjv-red); text-decoration: line-through; font-size: .9rem; opacity: .7; margin-top: 2px; }
.show-kjv .kjv-original { display: block; }
.show-kjv .kjv-toggle { background: rgba(220,38,38,.08); border-color: rgba(220,38,38,.3); color: var(--akjv-red); }
.no-verses { text-align: center; padding: 3rem 1rem; color: var(--akjv-dim); }
.no-verses h2 { color: var(--akjv-gold); }
.no-verses p { max-width: 500px; margin: 1rem auto; }
@media (max-width: 800px) {
    .bible-reader { grid-template-columns: 1fr; }
    .bible-sidebar { position: relative; top: 0; max-height: none; border-right: none; border-bottom: 1px solid var(--akjv-border); display: none; }
    .bible-sidebar.open { display: block; }
    .bible-main { padding: 1.5rem; }
    .mob-book-toggle { display: flex !important; }
}
.mob-book-toggle { display: none; align-items: center; gap: 8px; padding: 10px 16px; background: rgba(255,255,255,.04); border: 1px solid var(--akjv-border); border-radius: 10px; color: var(--akjv-gold); font-size: .9rem; cursor: pointer; margin-bottom: 1rem; width: 100%; }
CSS;
}

function akjv_styles_prophecies(): string {
    return akjv_css_variables() . <<<'CSS'

.proph-page { max-width: 1000px; margin: 0 auto; padding: 0 1.5rem 5rem; color: var(--akjv-white); }
.proph-hero { text-align: center; padding: 70px 0 40px; position: relative; }
.proph-hero::after { content:''; position:absolute; bottom:0; left:10%; right:10%; height:1px; background:linear-gradient(90deg,transparent,var(--akjv-gold),transparent); }
.proph-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 18px; border-radius:999px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.25); color:var(--akjv-gold); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.15em; margin-bottom:1.2rem; }
.proph-hero h1 { font-size: clamp(1.8rem, 4.5vw, 2.8rem); font-weight: 800; line-height: 1.15; margin-bottom: .6rem; }
.proph-hero h1 .gold { color: var(--akjv-gold); }
.proph-hero h1 .count { color: var(--akjv-red); }
.proph-hero .subtitle { font-size: 1rem; color: var(--akjv-muted); max-width: 650px; margin: 0 auto 1.5rem; line-height: 1.7; }
.proph-contributor { background: linear-gradient(135deg, rgba(255,215,0,.06), rgba(220,38,38,.04)); border: 1px solid rgba(255,215,0,.2); border-radius: 16px; padding: 1.8rem 2rem; max-width: 700px; margin: 0 auto 2.5rem; text-align: center; position: relative; overflow: hidden; }
.proph-contributor::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,var(--akjv-red),var(--akjv-gold),var(--akjv-red)); }
.proph-contributor .name { font-size: 1.15rem; font-weight: 800; color: var(--akjv-gold); margin-bottom: .3rem; }
.proph-contributor .desc { font-size: .88rem; color: rgba(255,255,255,.7); line-height: 1.7; }
.proph-contributor .desc em { color: var(--akjv-gold2); font-style: italic; }
.proph-nav { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; margin-bottom: 2.5rem; }
.proph-nav a { padding: .45rem 1rem; border-radius: 999px; font-size: .78rem; font-weight: 600; text-decoration: none; border: 1px solid rgba(255,255,255,.1); color: rgba(255,255,255,.6); transition: .2s; }
.proph-nav a:hover { border-color: var(--akjv-gold); color: var(--akjv-gold); background: rgba(255,215,0,.06); }
.proph-category { margin-bottom: 3rem; }
.proph-cat-header { display: flex; align-items: center; gap: .8rem; margin-bottom: 1.2rem; padding-bottom: .8rem; border-bottom: 1px solid rgba(255,255,255,.06); }
.proph-cat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.proph-cat-header h2 { font-size: 1.2rem; font-weight: 700; color: #fff; margin: 0; }
.proph-cat-header .cat-count { font-size: .75rem; color: var(--akjv-dim); margin-left: .5rem; }
.proph-card { background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 14px; margin-bottom: 1rem; overflow: hidden; transition: border-color .2s; }
.proph-card:hover { border-color: rgba(255,215,0,.3); }
.proph-card-head { padding: 1rem 1.3rem; display: flex; align-items: flex-start; gap: .8rem; cursor: pointer; user-select: none; }
.proph-card-head:hover { background: rgba(255,255,255,.015); }
.proph-num { width: 32px; height: 32px; border-radius: 8px; background: rgba(255,215,0,.1); color: var(--akjv-gold); display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 800; flex-shrink: 0; }
.proph-title { font-size: .95rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.4; }
.proph-refs { font-size: .75rem; color: var(--akjv-dim); margin-top: .15rem; }
.proph-toggle { margin-left: auto; color: var(--akjv-dim); font-size: .8rem; flex-shrink: 0; padding-top: .3rem; transition: transform .2s; }
.proph-card.open .proph-toggle { transform: rotate(180deg); }
.proph-card-body { display: none; padding: 0 1.3rem 1.3rem; }
.proph-card.open .proph-card-body { display: block; }
.proph-verse { border-radius: 10px; padding: 1rem 1.2rem; margin-bottom: .8rem; }
.proph-verse.tanakh { background: rgba(59,130,246,.06); border: 1px solid rgba(59,130,246,.15); }
.proph-verse.nt { background: rgba(34,197,94,.06); border: 1px solid rgba(34,197,94,.15); }
.proph-verse .v-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .12em; font-weight: 700; margin-bottom: .4rem; }
.proph-verse.tanakh .v-label { color: var(--akjv-blue); }
.proph-verse.nt .v-label { color: var(--akjv-green); }
.proph-verse .v-ref { font-weight: 700; font-size: .88rem; color: #fff; margin-bottom: .3rem; }
.proph-verse .v-text { font-style: italic; color: rgba(255,255,255,.85); font-size: .88rem; line-height: 1.7; }
.proph-context { color: rgba(255,255,255,.6); font-size: .82rem; line-height: 1.6; margin-top: .5rem; padding-top: .5rem; border-top: 1px solid rgba(255,255,255,.06); }
.proph-seal { text-align: center; margin-top: 3rem; padding: 2rem; border: 1px solid rgba(255,215,0,.2); border-radius: 16px; background: linear-gradient(135deg, rgba(255,215,0,.05), rgba(220,38,38,.03)); }
.proph-seal .date { font-size: 1.3rem; font-weight: 900; color: var(--akjv-gold); }
.proph-seal .auth { color: var(--akjv-dim); font-size: .82rem; margin-top: .5rem; line-height: 1.6; }
@media (max-width: 600px) {
    .proph-page { padding: 0 1rem 4rem; }
    .proph-card-head { padding: .8rem 1rem; }
    .proph-card-body { padding: 0 1rem 1rem; }
    .proph-verse { padding: .8rem; }
    .proph-contributor { padding: 1.2rem; }
}
CSS;
}
