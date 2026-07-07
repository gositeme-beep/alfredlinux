document.addEventListener('DOMContentLoaded', () => {
    const langBtns = document.querySelectorAll('.lang');
    const allText = document.querySelectorAll('[data-fr][data-en]');
    const menuBtns = document.querySelectorAll('.menu-btn');
    const panels = document.querySelectorAll('.panel');
    
    let currentLang = localStorage.getItem('lang') || 'fr';
    
    function setLang(lang) {
        currentLang = lang;
        localStorage.setItem('lang', lang);
        
        langBtns.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.lang === lang);
        });
        
        allText.forEach(el => {
            const text = el.getAttribute(`data-${lang}`);
            if (text) {
                el.textContent = text;
            }
        });
        
        document.documentElement.lang = lang;
    }
    
    langBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            setLang(btn.dataset.lang);
        });
    });
    
    setLang(currentLang);
    
    menuBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const panelId = btn.dataset.panel;
            
            menuBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            panels.forEach(p => {
                p.classList.remove('active');
            });
            
            setTimeout(() => {
                const target = document.getElementById(panelId);
                if (target) {
                    target.classList.add('active');
                }
            }, 100);
        });
    });
});

