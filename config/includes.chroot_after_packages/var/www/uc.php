<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>|DOMAIN| — Powered by GoSiteMe</title>
<meta name="description" content="|DOMAIN| is professionally managed by GoSiteMe.com — premium domain registration, AI-powered hosting, and enterprise web solutions.">
<meta name="author" content="GoSiteMe.com">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#7c3aed">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://|DOMAIN|/">
<meta property="og:title" content="|DOMAIN| — Powered by GoSiteMe">
<meta property="og:description" content="Premium domain services and AI-powered hosting by GoSiteMe.com">
<meta property="og:image" content="https://root.com/logo_small.png">
<meta property="og:site_name" content="|DOMAIN|">
<meta property="twitter:card" content="summary">
<link rel="canonical" href="https://|DOMAIN|/">
<link rel="icon" href="https://root.com/favicon.ico">

<!-- Structured Data -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebSite","name":"|DOMAIN|","url":"https://|DOMAIN|/","publisher":{"@type":"Organization","name":"GoSiteMe.com","url":"https://root.com"}}
</script>

<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#09090f;--surface:rgba(17,17,30,.95);--card:rgba(255,255,255,.03);--border:rgba(255,255,255,.06);--text:#e8ecf4;--muted:rgba(255,255,255,.4);--accent:#7c3aed;--accent2:#06b6d4;--glow:rgba(124,58,237,.15)}

@font-face{font-family:'Inter';font-display:swap;src:local('Inter'),local('Inter-Regular')}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;line-height:1.6}

/* Ambient background */
.bg-glow{position:fixed;inset:0;pointer-events:none;z-index:0}
.bg-glow::before,.bg-glow::after{content:'';position:absolute;border-radius:50%;filter:blur(120px);opacity:.08}
.bg-glow::before{width:600px;height:600px;background:#7c3aed;top:-100px;right:-100px}
.bg-glow::after{width:500px;height:500px;background:#06b6d4;bottom:-100px;left:-100px}

/* Layout */
.page{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem}

/* Logo bar */
.topbar{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:1rem 2rem}
.topbar-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;color:var(--text)}
.topbar-logo img{height:28px;border-radius:6px}
.topbar-logo span{font-size:.8rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;opacity:.6}
.topbar-lang{display:flex;gap:4px}
.lang-btn{background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--muted);padding:4px 10px;border-radius:6px;font-size:.7rem;font-weight:600;cursor:pointer;transition:.2s}
.lang-btn:hover,.lang-btn.active{background:var(--accent);border-color:var(--accent);color:#fff}

/* Main card */
.hero{text-align:center;max-width:680px;width:100%}

.domain-badge{display:inline-flex;align-items:center;gap:.5rem;background:var(--glow);border:1px solid rgba(124,58,237,.2);padding:.5rem 1.2rem;border-radius:40px;font-size:.8rem;font-weight:600;color:var(--accent);margin-bottom:1.5rem;letter-spacing:.5px}
.domain-badge .dot{width:8px;height:8px;background:#10b981;border-radius:50%;animation:pulse 2s infinite}

@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.3)}}

.domain-name-display{font-size:3rem;font-weight:900;background:linear-gradient(135deg,#e8ecf4 0%,rgba(255,255,255,.5) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:.75rem;line-height:1.15;letter-spacing:-.02em;word-break:break-all}

.hero-sub{font-size:1.05rem;color:var(--muted);max-width:500px;margin:0 auto 2.5rem;line-height:1.7}

/* Search box */
.search-box{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.5rem;margin-bottom:2rem;backdrop-filter:blur(20px)}
.search-box h3{font-size:.85rem;font-weight:600;margin-bottom:.75rem;color:var(--muted)}
.search-row{display:flex;gap:.5rem}
.search-input{flex:1;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;padding:.75rem 1rem;color:var(--text);font-size:.9rem;outline:none;transition:.2s}
.search-input:focus{border-color:var(--accent)}
.search-ext{background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;padding:.75rem .8rem;color:var(--text);font-size:.9rem;cursor:pointer;outline:none}
.search-btn{background:linear-gradient(135deg,var(--accent),#a855f7);border:none;border-radius:10px;padding:.75rem 1.5rem;color:#fff;font-weight:700;font-size:.85rem;cursor:pointer;transition:.2s;white-space:nowrap}
.search-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(124,58,237,.4)}

.search-result{margin-top:1rem;display:none;padding:1rem;border-radius:10px;text-align:center;font-size:.9rem;font-weight:600}
.search-result.available{display:block;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);color:#10b981}
.search-result.taken{display:block;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#ef4444}
.search-result a{color:inherit;text-decoration:underline;margin-left:.5rem}

/* Features */
.features{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:2rem;width:100%}
.feat{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.25rem;text-align:center;transition:.25s}
.feat:hover{border-color:rgba(124,58,237,.2);transform:translateY(-2px)}
.feat-icon{font-size:1.6rem;margin-bottom:.5rem}
.feat h4{font-size:.8rem;font-weight:700;margin-bottom:.25rem}
.feat p{font-size:.68rem;color:var(--muted);line-height:1.5}

/* CTA buttons */
.cta-row{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;margin-bottom:2rem}
.cta{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border-radius:12px;text-decoration:none;font-weight:700;font-size:.85rem;transition:.25s}
.cta-primary{background:linear-gradient(135deg,var(--accent),#a855f7);color:#fff}
.cta-primary:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(124,58,237,.4)}
.cta-secondary{background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text)}
.cta-secondary:hover{border-color:var(--accent);color:#fff}

/* Extension pills */
.ext-row{display:flex;gap:.4rem;flex-wrap:wrap;justify-content:center;margin-bottom:2rem}
.ext-pill{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:.35rem .7rem;font-size:.7rem;color:var(--muted);font-weight:600;cursor:pointer;transition:.2s}
.ext-pill:hover{border-color:var(--accent);color:var(--text)}
.ext-pill .price{color:var(--accent);margin-left:.3rem}

/* Footer */
.foot{text-align:center;margin-top:auto;padding-top:2rem}
.foot-links{display:flex;gap:1rem;justify-content:center;margin-bottom:.75rem}
.foot-links a{color:var(--muted);text-decoration:none;font-size:.75rem;transition:.2s}
.foot-links a:hover{color:var(--accent)}
.foot-copy{font-size:.65rem;color:rgba(255,255,255,.2)}
.foot-copy a{color:rgba(255,255,255,.3);text-decoration:none}

/* Responsive */
@media(max-width:768px){
    .domain-name-display{font-size:2rem}
    .features{grid-template-columns:1fr 1fr}
    .search-row{flex-wrap:wrap}
    .search-input{min-width:0}
    .topbar{padding:.75rem 1rem}
}
@media(max-width:480px){
    .domain-name-display{font-size:1.5rem}
    .features{grid-template-columns:1fr}
    .hero-sub{font-size:.9rem}
}
</style>
</head>
<body>

<div class="bg-glow"></div>

<!-- Top bar -->
<div class="topbar">
    <a href="https://root.com" class="topbar-logo" target="_blank" rel="noopener">
        <img src="https://root.com/logo_small.png" alt="GoSiteMe">
        <span>GoSiteMe</span>
    </a>
    <div class="topbar-lang">
        <button class="lang-btn active" onclick="setLang('en')">EN</button>
        <button class="lang-btn" onclick="setLang('fr')">FR</button>
    </div>
</div>

<div class="page">
    <div class="hero">
        <!-- Domain badge -->
        <div class="domain-badge">
            <span class="dot"></span>
            <span data-t="managed">Managed by GoSiteMe</span>
        </div>

        <!-- Domain name -->
        <h1 class="domain-name-display">|DOMAIN|</h1>
        <p class="hero-sub" data-t="heroDesc">This domain is professionally managed and will be launching soon. Get your own domain and AI-powered hosting from GoSiteMe.</p>

        <!-- Domain search -->
        <div class="search-box">
            <h3 data-t="searchTitle">Find your perfect domain</h3>
            <div class="search-row">
                <input class="search-input" type="text" id="domainInput" placeholder="yourdomain" maxlength="63" autocomplete="off">
                <select class="search-ext" id="extSelect">
                    <option>.com</option><option>.net</option><option>.org</option>
                    <option>.io</option><option>.co</option><option>.ai</option>
                    <option>.dev</option><option>.app</option><option>.ca</option>
                </select>
                <button class="search-btn" id="checkBtn" data-t="checkAvail">Check Availability</button>
            </div>
            <div class="search-result" id="searchResult"></div>
        </div>

        <!-- Extension pills -->
        <div class="ext-row">
            <div class="ext-pill">.com <span class="price">$12.99</span></div>
            <div class="ext-pill">.net <span class="price">$14.99</span></div>
            <div class="ext-pill">.org <span class="price">$11.99</span></div>
            <div class="ext-pill">.io <span class="price">$39.99</span></div>
            <div class="ext-pill">.ai <span class="price">$79.99</span></div>
            <div class="ext-pill">.co <span class="price">$29.99</span></div>
            <div class="ext-pill">.dev <span class="price">$14.99</span></div>
            <div class="ext-pill">.ca <span class="price">$15.99</span></div>
        </div>

        <!-- Features -->
        <div class="features">
            <div class="feat">
                <div class="feat-icon">🤖</div>
                <h4 data-t="featAI">AI-Powered Hosting</h4>
                <p data-t="featAIDesc">Built-in AI assistant, code editor, and automated deployment</p>
            </div>
            <div class="feat">
                <div class="feat-icon">🔒</div>
                <h4 data-t="featSSL">Free SSL & Security</h4>
                <p data-t="featSSLDesc">Enterprise-grade encryption, DDoS protection, daily backups</p>
            </div>
            <div class="feat">
                <div class="feat-icon">⚡</div>
                <h4 data-t="featSpeed">Global CDN</h4>
                <p data-t="featSpeedDesc">99.99% uptime with edge servers worldwide for blazing speed</p>
            </div>
        </div>

        <!-- CTA -->
        <div class="cta-row">
            <a href="https://root.com/store" class="cta cta-primary" target="_blank" rel="noopener">
                🚀 <span data-t="getStarted">Get Started</span>
            </a>
            <a href="mailto:sales@root.com" class="cta cta-secondary">
                ✉️ <span data-t="contact">Contact Sales</span>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="foot">
        <div class="foot-links">
            <a href="https://root.com" target="_blank" rel="noopener">GoSiteMe.com</a>
            <a href="https://root.com/store" target="_blank" rel="noopener" data-t="services">Services</a>
            <a href="mailto:sales@root.com" data-t="contactUs">Contact Us</a>
            <a href="tel:+18334678486" data-t="callUs">1-833-GOSITEME</a>
        </div>
        <div class="foot-copy">&copy; 2025-2026 <a href="https://root.com" target="_blank" rel="noopener">GoSiteMe.com</a> — Professional Domain & Hosting Services</div>
    </div>
</div>

<script>
(function(){
    // i18n
    const T={
        en:{managed:'Managed by GoSiteMe',heroDesc:'This domain is professionally managed and will be launching soon. Get your own domain and AI-powered hosting from GoSiteMe.',searchTitle:'Find your perfect domain',checkAvail:'Check Availability',featAI:'AI-Powered Hosting',featAIDesc:'Built-in AI assistant, code editor, and automated deployment',featSSL:'Free SSL & Security',featSSLDesc:'Enterprise-grade encryption, DDoS protection, daily backups',featSpeed:'Global CDN',featSpeedDesc:'99.99% uptime with edge servers worldwide for blazing speed',getStarted:'Get Started',contact:'Contact Sales',services:'Services',contactUs:'Contact Us',callUs:'1-833-GOSITEME',available:'is available!',taken:'is already taken.',register:'Register Now',domainInvalid:'Please enter a valid domain name'},
        fr:{managed:'Géré par GoSiteMe',heroDesc:'Ce domaine est géré professionnellement et sera bientôt lancé. Obtenez votre propre domaine et hébergement IA chez GoSiteMe.',searchTitle:'Trouvez votre domaine parfait',checkAvail:'Vérifier la disponibilité',featAI:'Hébergement IA',featAIDesc:'Assistant IA intégré, éditeur de code et déploiement automatisé',featSSL:'SSL & Sécurité gratuits',featSSLDesc:'Chiffrement entreprise, protection DDoS, sauvegardes quotidiennes',featSpeed:'CDN mondial',featSpeedDesc:'99,99% de disponibilité avec serveurs partout dans le monde',getStarted:'Commencer',contact:'Contacter les ventes',services:'Services',contactUs:'Nous contacter',callUs:'1-833-GOSITEME',available:'est disponible !',taken:'est déjà pris.',register:'Enregistrer maintenant',domainInvalid:'Veuillez entrer un nom de domaine valide'}
    };
    let lang=(navigator.language||'en').split('-')[0]==='fr'?'fr':'en';

    window.setLang=function(l){
        lang=l;
        document.querySelectorAll('.lang-btn').forEach(b=>b.classList.toggle('active',b.textContent.trim().toLowerCase()===l));
        document.querySelectorAll('[data-t]').forEach(el=>{
            const k=el.getAttribute('data-t');
            if(T[l]&&T[l][k])el.textContent=T[l][k];
        });
    };
    document.addEventListener('DOMContentLoaded',()=>setLang(lang));

    // Domain check
    const checkBtn=document.getElementById('checkBtn');
    const domainInput=document.getElementById('domainInput');
    const extSelect=document.getElementById('extSelect');
    const result=document.getElementById('searchResult');

    checkBtn.addEventListener('click',async()=>{
        const name=domainInput.value.trim().toLowerCase().replace(/[^a-z0-9-]/g,'');
        if(!name||name.length<2){result.className='search-result taken';result.style.display='block';result.textContent=T[lang].domainInvalid;return}
        const full=name+extSelect.value;
        checkBtn.disabled=true;checkBtn.textContent='...';
        try{
            // Simple WHOIS-style check via GoSiteMe API
            const r=await fetch('https://root.com/api/domains.php?action=check&domain='+encodeURIComponent(full));
            const d=await r.json();
            if(d.available){
                result.className='search-result available';
                result.innerHTML=`<strong>${esc(full)}</strong> ${T[lang].available} <a href="https://root.com/store/domain-registration?domain=${encodeURIComponent(full)}" target="_blank" rel="noopener">${T[lang].register}</a>`;
            } else {
                result.className='search-result taken';
                result.innerHTML=`<strong>${esc(full)}</strong> ${T[lang].taken}`;
            }
        }catch{
            // Fallback: redirect to GoSiteMe store
            window.open('https://root.com/store/domain-registration?domain='+encodeURIComponent(full),'_blank');
        }
        checkBtn.disabled=false;checkBtn.textContent=T[lang].checkAvail;
    });

    domainInput.addEventListener('keydown',e=>{if(e.key==='Enter')checkBtn.click()});

    function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML}
})();
</script>
</body>
</html>
