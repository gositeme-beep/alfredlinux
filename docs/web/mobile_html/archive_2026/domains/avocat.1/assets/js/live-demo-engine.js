const LiveDemo = (() => {
    const BASE = '/api/service-governance.php?action=';
    const endpoints = {
        'economy-overview': { method: 'GET', desc: 'Full token economy overview — supply, holders, earnings by type, API marketplace stats' },
        'governance-stats': { method: 'GET', desc: 'Governance pipeline stats — proposals by status, voting summary, job distribution' },
        'proposals': { method: 'GET', desc: 'List all service proposals with votes, departments, and status' },
        'gsm-leaderboard': { method: 'GET', desc: 'Top GSM token earners across all departments' },
        'marketplace': { method: 'GET', desc: 'API marketplace — deployed services with usage and revenue data' }
    };
    let currentEndpoint = 'economy-overview';

    function setEndpoint(ep, btn) {
        currentEndpoint = ep;
        const url = 'https://gositeme.com' + BASE + ep;
        document.getElementById('sandboxUrl').textContent = url;
        document.querySelectorAll('.ld-sandbox-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        const info = endpoints[ep];
        document.getElementById('sandboxRequest').textContent =
            `// ${info.desc}\n\nfetch('${url}')\n  .then(r => r.json())\n  .then(data => console.log(data));`;
        document.getElementById('sandboxResponse').textContent = '// Click Run to see live response...';
    }

    async function runSandbox() {
        const resp = document.getElementById('sandboxResponse');
        resp.textContent = '// Loading...';
        resp.style.color = 'var(--ld-muted)';
        try {
            const r = await fetch(BASE + currentEndpoint);
            const d = await r.json();
            resp.textContent = JSON.stringify(d, null, 2);
            resp.style.color = 'var(--ld-energy)';
        } catch(e) {
            resp.textContent = '// Error: ' + e.message;
            resp.style.color = 'var(--ld-danger)';
        }
    }

    // ── Animate battery fill ──
    function animateBattery() {
        const fill = document.getElementById('batteryFill');
        const label = document.getElementById('batteryLabel');
        let pct = 35;
        const target = 42;
        const step = () => {
            pct += 0.1;
            if (pct > target) pct = target;
            fill.style.height = pct + '%';
            label.textContent = Math.round(pct) + '%';
            if (pct < target) requestAnimationFrame(step);
        };
        setTimeout(step, 1000);
    }

    // ── Counter animation ──
    function animateCounters() {
        document.querySelectorAll('.ld-stat-val[data-count]').forEach(el => {
            const target = parseInt(el.dataset.count);
            if (isNaN(target)) return;
            let current = 0;
            const increment = Math.max(1, Math.floor(target / 60));
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) { current = target; clearInterval(timer); }
                el.textContent = current.toLocaleString();
            }, 16);
        });
    }

    // ── Init ──
    animateBattery();
    // Animate counters when they scroll into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.disconnect();
            }
        });
    });
    const statsEl = document.querySelector('.ld-stats');
    if (statsEl) observer.observe(statsEl);

    return { setEndpoint, runSandbox };
})();
